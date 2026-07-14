<?php

namespace Tests\Feature;

use App\Mail\OtpMail;
use App\Mail\StaffInviteMail;
use App\Models\AuthSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Mirrors the Express prototype's test suite: the full citizen journey
// (register -> apply -> pay -> track) plus staff processing and admin config,
// against the contract in shared/api-contract.md.
class ApiContractTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function registerCitizen(string $phone = '244111222', string $pin = '135790', ?string $email = 'test@example.com'): array
    {
        $send = $this->postJson('/api/auth/register/send-otp', ['phone' => $phone, 'email' => $email]);
        $send->assertOk();
        $token = $send->json('registrationToken');

        $this->postJson('/api/auth/register/verify-otp', [
            'registrationToken' => $token,
            'otp' => $send->json('devOtp'),
        ])->assertOk();

        $this->postJson('/api/auth/register/profile', [
            'profileToken' => $token,
            'fullName' => 'Kwame Mensah',
            'ghanaCardNumber' => 'GHA-000111222-3',
        ])->assertOk()->assertJsonPath('nia.status', 'UNAVAILABLE');

        $pinResponse = $this->postJson('/api/auth/register/pin', ['profileToken' => $token, 'pin' => $pin]);
        $pinResponse->assertCreated();

        return $pinResponse->json();
    }

    // Staff login is always two steps now (password, then TOTP) - this
    // drives both legs so the rest of the suite can keep asking for "a
    // staff token" without caring about the 2FA mechanics.
    private function staffToken(string $staffId = 'OFF-001'): string
    {
        $login = $this->postJson('/api/staff/login', ['staffId' => $staffId, 'password' => 'changeme123']);
        $login->assertOk();

        $secret = $login->json('secret'); // only present on first-ever login (setup)
        if ($secret) {
            $code = (new \PragmaRX\Google2FA\Google2FA())->getCurrentOtp($secret);
            $verify = $this->postJson('/api/staff/login/verify-2fa', [
                'challengeToken' => $login->json('challengeToken'),
                'code' => $code,
            ]);
            $verify->assertOk();

            return $verify->json('accessToken');
        }

        // Already enrolled - fetch the secret directly (test-only shortcut;
        // real clients never see it again after the first setup).
        $staff = \App\Models\StaffUser::where('staff_id', $staffId)->first();
        $code = (new \PragmaRX\Google2FA\Google2FA())->getCurrentOtp($staff->two_factor_secret);
        $verify = $this->postJson('/api/staff/login/verify-2fa', [
            'challengeToken' => $login->json('challengeToken'),
            'code' => $code,
        ]);
        $verify->assertOk();

        return $verify->json('accessToken');
    }

    private function submittedApplication(string $accessToken, string $eventType = 'early_birth', array $formData = [], array $documents = []): string
    {
        Storage::fake('local');
        $headers = ['Authorization' => "Bearer {$accessToken}"];

        $created = $this->postJson('/api/applications', ['eventType' => $eventType, 'tier' => 'standard'], $headers);
        $created->assertCreated();
        $id = $created->json('id');

        $this->patchJson("/api/applications/{$id}", ['formData' => $formData], $headers)->assertOk();

        foreach ($documents as $fieldName) {
            $this->post("/api/applications/{$id}/documents", [
                'fieldName' => $fieldName,
                'file' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
            ], $headers)->assertCreated();
        }

        $this->postJson("/api/applications/{$id}/submit", [], $headers)
            ->assertOk()
            ->assertJsonPath('status', 'PAYMENT_PENDING');

        return $id;
    }

    private function payFor(string $applicationId, string $accessToken): string
    {
        $headers = ['Authorization' => "Bearer {$accessToken}"];
        $initiate = $this->postJson('/api/payments/initiate', ['applicationId' => $applicationId, 'method' => 'momo'], $headers);
        $initiate->assertOk();

        $confirm = $this->postJson('/api/payments/mock-confirm', ['paymentId' => $initiate->json('paymentId')], $headers);
        $confirm->assertOk();

        return $confirm->json('trackingId');
    }

    private const EARLY_BIRTH_FORM = [
        'childFullName' => 'Baby Mensah',
        'childSex' => 'M',
        'childDateOfBirth' => '2026-05-01',
        'placeOfBirth' => '37 Military Hospital',
        'motherFullName' => 'Ama Mensah',
        'motherGhanaCardNumber' => 'GHA-000111222-3',
        'informantFullName' => 'Kwame Mensah',
        'informantRelationshipToChild' => 'Father',
        'informantPhone' => '244111222',
    ];

    private const EARLY_BIRTH_DOCS = ['hospitalBirthNotification', 'parentGhanaCardCopy'];

    public function test_health_check(): void
    {
        $this->getJson('/api/health')->assertOk()->assertJson(['status' => 'ok']);
    }

    public function test_registration_rejects_invalid_phone(): void
    {
        $this->postJson('/api/auth/register/send-otp', ['phone' => '123'])
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'INVALID_PHONE');
    }

    public function test_registration_email_channel_requires_valid_email_and_actually_sends(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/register/send-otp', ['phone' => '244111222', 'channel' => 'email'])
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'INVALID_EMAIL');

        $send = $this->postJson('/api/auth/register/send-otp', [
            'phone' => '244111222',
            'email' => 'kwame@example.com',
            'channel' => 'email',
        ]);
        $send->assertOk()
            ->assertJsonPath('otpChannel', 'email')
            ->assertJsonPath('otpSentTo', 'kwame@example.com');

        Mail::assertSent(OtpMail::class, function (OtpMail $mail) use ($send) {
            return $mail->code === $send->json('devOtp') && $mail->hasTo('kwame@example.com');
        });
    }

    public function test_registration_rejects_invalid_channel(): void
    {
        $this->postJson('/api/auth/register/send-otp', ['phone' => '244111222', 'channel' => 'carrier-pigeon'])
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'INVALID_CHANNEL');
    }

    public function test_registration_rejects_wrong_otp(): void
    {
        $send = $this->postJson('/api/auth/register/send-otp', ['phone' => '244111222']);
        $this->postJson('/api/auth/register/verify-otp', [
            'registrationToken' => $send->json('registrationToken'),
            'otp' => '000000',
        ])->assertStatus(400)->assertJsonPath('error.code', 'OTP_INCORRECT');
    }

    public function test_registration_rejects_expired_otp(): void
    {
        $send = $this->postJson('/api/auth/register/send-otp', ['phone' => '244111222']);
        $token = $send->json('registrationToken');
        AuthSession::find($token)->update(['otp_expires_at' => now()->subMinute()]);

        $this->postJson('/api/auth/register/verify-otp', [
            'registrationToken' => $token,
            'otp' => $send->json('devOtp'),
        ])->assertStatus(400)->assertJsonPath('error.code', 'OTP_EXPIRED');
    }

    public function test_full_registration_creates_citizen_and_duplicate_phone_is_rejected(): void
    {
        $result = $this->registerCitizen();
        $this->assertNotEmpty($result['citizenId']);
        $this->assertNotEmpty($result['accessToken']);
        $this->assertNotEmpty($result['refreshToken']);

        $this->postJson('/api/auth/register/send-otp', ['phone' => '244111222'])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'PHONE_ALREADY_REGISTERED');
    }

    public function test_login_rejects_wrong_pin_and_accepts_correct_flow(): void
    {
        $this->registerCitizen();

        $this->postJson('/api/auth/login/send-otp', ['phone' => '244111222', 'pin' => '000000'])
            ->assertStatus(401);

        $send = $this->postJson('/api/auth/login/send-otp', ['phone' => '244111222', 'pin' => '135790']);
        $send->assertOk();

        $this->postJson('/api/auth/login/verify-otp', [
            'loginToken' => $send->json('loginToken'),
            'otp' => $send->json('devOtp'),
        ])->assertOk()->assertJsonStructure(['citizenId', 'accessToken', 'refreshToken', 'expiresIn']);
    }

    public function test_login_email_channel_sends_to_email_on_file_or_rejects_when_missing(): void
    {
        Mail::fake();
        $this->registerCitizen(); // registerCitizen() sets email to test@example.com

        $send = $this->postJson('/api/auth/login/send-otp', ['phone' => '244111222', 'pin' => '135790', 'channel' => 'email']);
        $send->assertOk()->assertJsonPath('otpSentTo', 'test@example.com');
        Mail::assertSent(OtpMail::class);

        $this->registerCitizen(phone: '244222333', email: null); // no email set this time

        $this->postJson('/api/auth/login/send-otp', ['phone' => '244222333', 'pin' => '135790', 'channel' => 'email'])
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'NO_EMAIL_ON_FILE');
    }

    public function test_refresh_issues_new_tokens(): void
    {
        $result = $this->registerCitizen();

        $this->postJson('/api/auth/refresh', ['refreshToken' => $result['refreshToken']])
            ->assertOk()
            ->assertJsonStructure(['accessToken', 'refreshToken', 'expiresIn']);

        $this->postJson('/api/auth/refresh', ['refreshToken' => 'bogus'])
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'INVALID_REFRESH_TOKEN');
    }

    public function test_access_token_cannot_be_used_as_refresh_token_and_vice_versa(): void
    {
        $result = $this->registerCitizen();

        $this->postJson('/api/auth/refresh', ['refreshToken' => $result['accessToken']])
            ->assertStatus(401);

        $this->getJson('/api/citizens/me', ['Authorization' => "Bearer {$result['refreshToken']}"])
            ->assertStatus(401);
    }

    public function test_logout_revokes_both_access_and_refresh_tokens(): void
    {
        $result = $this->registerCitizen();
        $headers = ['Authorization' => "Bearer {$result['accessToken']}"];

        $this->getJson('/api/citizens/me', $headers)->assertOk();
        $this->postJson('/api/auth/logout', [], $headers)->assertOk()->assertJsonPath('loggedOut', true);

        $this->getJson('/api/citizens/me', $headers)->assertStatus(401);
        $this->postJson('/api/auth/refresh', ['refreshToken' => $result['refreshToken']])
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'INVALID_REFRESH_TOKEN');
    }

    public function test_staff_logout_revokes_only_the_current_token(): void
    {
        $headers = ['Authorization' => 'Bearer '.$this->staffToken('OFF-001')];

        $this->getJson('/api/staff/queue', $headers)->assertOk();
        $this->postJson('/api/staff/logout', [], $headers)->assertOk();
        $this->getJson('/api/staff/queue', $headers)->assertStatus(401);
    }

    public function test_event_type_catalogue_lists_all_six(): void
    {
        $response = $this->getJson('/api/applications/event-types');
        $response->assertOk();
        $this->assertCount(6, $response->json());

        $earlyBirth = collect($response->json())->firstWhere('eventType', 'early_birth');
        $this->assertTrue($earlyBirth['formSupported']);
    }

    public function test_submit_requires_fields_and_documents(): void
    {
        $result = $this->registerCitizen();
        $headers = ['Authorization' => "Bearer {$result['accessToken']}"];

        $created = $this->postJson('/api/applications', ['eventType' => 'early_birth', 'tier' => 'standard'], $headers);
        $id = $created->json('id');

        $this->postJson("/api/applications/{$id}/submit", [], $headers)
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'INCOMPLETE_FORM');

        $this->patchJson("/api/applications/{$id}", ['formData' => self::EARLY_BIRTH_FORM], $headers)->assertOk();

        $this->postJson("/api/applications/{$id}/submit", [], $headers)
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'MISSING_DOCUMENTS');
    }

    public function test_creating_an_application_for_an_unknown_event_type_is_rejected(): void
    {
        $result = $this->registerCitizen();
        $headers = ['Authorization' => "Bearer {$result['accessToken']}"];

        $this->postJson('/api/applications', ['eventType' => 'time_travel', 'tier' => 'standard'], $headers)
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'INVALID_EVENT_TYPE');
    }

    // Every event type currently marked form_supported must have a matching
    // config/form_schemas.php entry, or submit() would 400 with
    // UNSUPPORTED_EVENT_TYPE despite the catalogue promising a real form.
    public function test_every_form_supported_event_type_has_a_form_schema(): void
    {
        $catalogue = collect($this->getJson('/api/applications/event-types')->json());
        foreach ($catalogue->where('formSupported', true) as $eventType) {
            $this->assertNotNull(
                config("form_schemas.{$eventType['eventType']}"),
                "{$eventType['eventType']} is form_supported but has no form_schemas entry"
            );
        }
    }

    public function test_full_flow_generates_prd_tracking_id_and_public_tracking_minimizes_data(): void
    {
        $result = $this->registerCitizen();
        $id = $this->submittedApplication($result['accessToken'], 'early_birth', self::EARLY_BIRTH_FORM, self::EARLY_BIRTH_DOCS);
        $trackingId = $this->payFor($id, $result['accessToken']);

        $this->assertMatchesRegularExpression('/^BDR-\d{4}-EB-\d{6}$/', $trackingId);

        $tracking = $this->getJson("/api/tracking/{$trackingId}");
        $tracking->assertOk()
            ->assertJsonPath('status', 'SUBMITTED')
            ->assertJsonPath('citizenFirstName', 'Kwame')
            ->assertJsonMissingPath('citizenName');
    }

    public function test_death_registration_works_through_same_generic_routes(): void
    {
        $result = $this->registerCitizen();
        $id = $this->submittedApplication($result['accessToken'], 'death', [
            'deceasedFullName' => 'Yaw Boateng',
            'dateOfDeath' => '2026-06-20',
            'placeOfDeath' => 'Komfo Anokye Teaching Hospital',
            'causeOfDeath' => 'Certified by attending physician',
            'informantFullName' => 'Kwame Mensah',
            'informantRelationshipToDeceased' => 'Son',
            'informantPhone' => '244111222',
        ], ['medicalCertificateOfCause', 'deceasedIdCopy']);

        $trackingId = $this->payFor($id, $result['accessToken']);
        $this->assertMatchesRegularExpression('/^BDR-\d{4}-DR-\d{6}$/', $trackingId);
    }

    public function test_all_six_event_types_are_form_supported_and_each_produces_the_right_tracking_prefix(): void
    {
        $catalogue = collect($this->getJson('/api/applications/event-types')->json());
        $this->assertTrue($catalogue->every(fn ($e) => $e['formSupported']));

        $cases = [
            'late_birth' => ['code' => 'LB', 'form' => [
                'childFullName' => 'Baby Owusu', 'childSex' => 'F', 'childDateOfBirth' => '2020-01-01',
                'placeOfBirth' => 'Home birth', 'motherFullName' => 'Abena Owusu', 'fatherFullName' => 'Kojo Owusu',
                'reasonForLateRegistration' => 'Family relocated', 'informantFullName' => 'Abena Owusu',
                'informantRelationshipToChild' => 'Mother', 'informantPhone' => '244111222',
            ], 'docs' => ['swornDeclarationOfLateBirth', 'proofOfBirthRecord', 'parentGhanaCardCopy']],
            'foetal_death' => ['code' => 'FD', 'form' => [
                'motherFullName' => 'Adjoa Boateng', 'motherGhanaCardNumber' => 'GHA-000111222-3',
                'gestationalAgeWeeks' => '30', 'dateOfFoetalDeath' => '2026-05-10', 'facilityName' => 'Ridge Hospital',
                'informantFullName' => 'Adjoa Boateng', 'informantPhone' => '244111222',
            ], 'docs' => ['medicalCertificateFoetalDeath', 'motherIdCopy']],
            'adoption' => ['code' => 'AD', 'form' => [
                'childFullName' => 'Nana Yeboah', 'childDateOfBirth' => '2022-03-15',
                'adoptiveMotherFullName' => 'Efua Yeboah', 'adoptiveFatherFullName' => 'Kwesi Yeboah',
                'courtOrderReference' => 'CO-2026-00123', 'courtName' => 'Accra High Court',
                'informantFullName' => 'Efua Yeboah', 'informantPhone' => '244111222',
            ], 'docs' => ['courtAdoptionOrder', 'adoptiveParentGhanaCardCopy']],
            'surrogacy' => ['code' => 'SR', 'form' => [
                'childFullName' => 'Kojo Asante', 'childDateOfBirth' => '2026-04-01',
                'intendedMotherFullName' => 'Akosua Asante', 'intendedFatherFullName' => 'Yaw Asante',
                'surrogacyAgreementReference' => 'SA-2026-0099', 'facilityName' => 'Lister Hospital',
                'informantFullName' => 'Akosua Asante', 'informantPhone' => '244111222',
            ], 'docs' => ['surrogacyAgreementDocument', 'hospitalBirthNotification', 'intendedParentGhanaCardCopy']],
        ];

        $result = $this->registerCitizen();
        foreach ($cases as $eventType => $case) {
            $id = $this->submittedApplication($result['accessToken'], $eventType, $case['form'], $case['docs']);
            $trackingId = $this->payFor($id, $result['accessToken']);
            $this->assertMatchesRegularExpression("/^BDR-\d{4}-{$case['code']}-\d{6}$/", $trackingId);
        }
    }

    public function test_citizen_can_download_own_document_but_not_via_other_application(): void
    {
        Storage::fake('local');
        $result = $this->registerCitizen();
        $headers = ['Authorization' => "Bearer {$result['accessToken']}"];

        $created = $this->postJson('/api/applications', ['eventType' => 'early_birth', 'tier' => 'standard'], $headers);
        $id = $created->json('id');

        $upload = $this->post("/api/applications/{$id}/documents", [
            'fieldName' => 'hospitalBirthNotification',
            'file' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
        ], $headers);
        $documentId = $upload->json('id');

        $this->get("/api/applications/{$id}/documents/{$documentId}", $headers)->assertOk();
        $this->get("/api/applications/does-not-exist/documents/{$documentId}", $headers)->assertNotFound();
    }

    public function test_staff_processes_application_through_to_completion(): void
    {
        $result = $this->registerCitizen();
        $id = $this->submittedApplication($result['accessToken'], 'early_birth', self::EARLY_BIRTH_FORM, self::EARLY_BIRTH_DOCS);
        $this->payFor($id, $result['accessToken']);

        $headers = ['Authorization' => 'Bearer '.$this->staffToken()];

        $queue = $this->getJson('/api/staff/queue', $headers);
        $queue->assertOk();
        $this->assertCount(1, $queue->json());

        $this->postJson("/api/staff/applications/{$id}/claim", [], $headers)
            ->assertOk()
            ->assertJsonPath('status', 'UNDER_REVIEW');

        $this->postJson("/api/staff/applications/{$id}/approve", [], $headers)->assertOk();
        $complete = $this->postJson("/api/staff/applications/{$id}/complete", [], $headers);
        $complete->assertOk()->assertJsonPath('status', 'COMPLETED');

        $serial = $complete->json('certificate.serial');
        $this->assertMatchesRegularExpression('/^CERT-\d{4}-EB-\d{6}$/', $serial);
        Storage::disk('local')->assertExists("certificates/{$serial}.pdf");

        // Citizen can download their own certificate PDF.
        $citizenHeaders = ['Authorization' => "Bearer {$result['accessToken']}"];
        $download = $this->get("/api/applications/{$id}/certificate", $citizenHeaders);
        $download->assertOk();
        $this->assertStringStartsWith('%PDF', $download->streamedContent());

        // Staff can download it too.
        $this->get("/api/staff/applications/{$id}/certificate", $headers)->assertOk();

        // Public verification page exposes only registration type/name/dates.
        $verify = $this->getJson("/api/certificates/verify/{$serial}");
        $verify->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('registrationType', 'early_birth')
            ->assertJsonPath('registeredName', self::EARLY_BIRTH_FORM['childFullName'])
            ->assertJsonMissingPath('formData');

        $this->getJson('/api/certificates/verify/CERT-BOGUS')->assertNotFound();
    }

    public function test_corrections_round_trip(): void
    {
        $result = $this->registerCitizen();
        $citizenHeaders = ['Authorization' => "Bearer {$result['accessToken']}"];
        $id = $this->submittedApplication($result['accessToken'], 'early_birth', self::EARLY_BIRTH_FORM, self::EARLY_BIRTH_DOCS);
        $this->payFor($id, $result['accessToken']);

        $staffHeaders = ['Authorization' => 'Bearer '.$this->staffToken()];
        $this->postJson("/api/staff/applications/{$id}/claim", [], $staffHeaders)->assertOk();
        $this->postJson("/api/staff/applications/{$id}/request-corrections", [
            'fields' => ['childFullName'],
            'notes' => 'Name does not match Ghana Card',
        ], $staffHeaders)->assertOk()->assertJsonPath('status', 'CORRECTIONS_REQUIRED');

        $this->patchJson("/api/applications/{$id}", ['formData' => ['childFullName' => 'Corrected Name']], $citizenHeaders)->assertOk();
        $this->postJson("/api/applications/{$id}/resubmit", [], $citizenHeaders)
            ->assertOk()
            ->assertJsonPath('status', 'UNDER_REVIEW');
    }

    public function test_notifications_are_created_through_the_application_lifecycle(): void
    {
        $result = $this->registerCitizen();
        $citizenHeaders = ['Authorization' => "Bearer {$result['accessToken']}"];
        $id = $this->submittedApplication($result['accessToken'], 'early_birth', self::EARLY_BIRTH_FORM, self::EARLY_BIRTH_DOCS);
        $this->payFor($id, $result['accessToken']);

        // Submitted (from payment confirmation) shows up unread on the dashboard.
        $dashboard = $this->getJson('/api/citizens/me/dashboard', $citizenHeaders);
        $dashboard->assertOk();
        $this->assertCount(1, $dashboard->json('notifications'));
        $this->assertEquals('APPLICATION_SUBMITTED', $dashboard->json('notifications.0.type'));
        $this->assertFalse($dashboard->json('notifications.0.read'));

        $staffHeaders = ['Authorization' => 'Bearer '.$this->staffToken()];
        $this->postJson("/api/staff/applications/{$id}/claim", [], $staffHeaders)->assertOk();
        $this->postJson("/api/staff/applications/{$id}/approve", [], $staffHeaders)->assertOk();
        $this->postJson("/api/staff/applications/{$id}/complete", [], $staffHeaders)->assertOk();

        $all = $this->getJson('/api/citizens/me/notifications', $citizenHeaders);
        $all->assertOk();
        $types = collect($all->json())->pluck('type')->all();
        $this->assertEquals(['CERTIFICATE_READY', 'APPLICATION_APPROVED', 'APPLICATION_SUBMITTED'], $types);

        $firstId = $all->json('0.id');
        $this->postJson("/api/citizens/me/notifications/{$firstId}/read", [], $citizenHeaders)
            ->assertOk()
            ->assertJsonPath('read', true);

        // Marking one read only drops it from the dashboard's unread feed, not from the full list.
        $dashboardAfter = $this->getJson('/api/citizens/me/dashboard', $citizenHeaders);
        $this->assertCount(2, $dashboardAfter->json('notifications'));
    }

    public function test_staff_cannot_use_citizen_routes_and_vice_versa(): void
    {
        $result = $this->registerCitizen();
        $staffHeaders = ['Authorization' => 'Bearer '.$this->staffToken()];
        $citizenHeaders = ['Authorization' => "Bearer {$result['accessToken']}"];

        $this->getJson('/api/citizens/me/dashboard', $staffHeaders)->assertStatus(401);
        $this->getJson('/api/staff/queue', $citizenHeaders)->assertStatus(401);
    }

    public function test_staff_first_login_requires_setting_up_2fa_and_enforces_it_after(): void
    {
        $login = $this->postJson('/api/staff/login', ['staffId' => 'OFF-001', 'password' => 'changeme123']);
        $login->assertOk()->assertJsonPath('twoFactorSetupRequired', true)->assertJsonStructure(['challengeToken', 'secret', 'otpauthUrl']);

        $wrongCode = $this->postJson('/api/staff/login/verify-2fa', [
            'challengeToken' => $login->json('challengeToken'),
            'code' => '000000',
        ]);
        $wrongCode->assertStatus(400)->assertJsonPath('error.code', 'INVALID_2FA_CODE');

        $secret = $login->json('secret');
        $code = (new \PragmaRX\Google2FA\Google2FA())->getCurrentOtp($secret);
        $this->postJson('/api/staff/login/verify-2fa', [
            'challengeToken' => $login->json('challengeToken'),
            'code' => $code,
        ])->assertOk()->assertJsonStructure(['accessToken']);

        // Second login onward: password succeeds, but a code is still required
        // (no more "secret" in the response - already enrolled).
        $secondLogin = $this->postJson('/api/staff/login', ['staffId' => 'OFF-001', 'password' => 'changeme123']);
        $secondLogin->assertOk()->assertJsonPath('twoFactorRequired', true)->assertJsonMissingPath('secret');

        $secondCode = (new \PragmaRX\Google2FA\Google2FA())->getCurrentOtp($secret);
        $this->postJson('/api/staff/login/verify-2fa', [
            'challengeToken' => $secondLogin->json('challengeToken'),
            'code' => $secondCode,
        ])->assertOk()->assertJsonStructure(['accessToken']);
    }

    public function test_expired_2fa_challenge_is_rejected(): void
    {
        $login = $this->postJson('/api/staff/login', ['staffId' => 'OFF-001', 'password' => 'changeme123']);
        \App\Models\StaffLoginChallenge::find($login->json('challengeToken'))->update(['expires_at' => now()->subMinute()]);

        $code = (new \PragmaRX\Google2FA\Google2FA())->getCurrentOtp($login->json('secret'));
        $this->postJson('/api/staff/login/verify-2fa', [
            'challengeToken' => $login->json('challengeToken'),
            'code' => $code,
        ])->assertStatus(400)->assertJsonPath('error.code', 'INVALID_CHALLENGE');
    }

    public function test_admin_config_requires_admin_role_reason_and_valid_fees(): void
    {
        $officerHeaders = ['Authorization' => 'Bearer '.$this->staffToken('OFF-001')];
        $this->getJson('/api/staff/admin/event-types', $officerHeaders)->assertStatus(403);

        $adminHeaders = ['Authorization' => 'Bearer '.$this->staffToken('ADM-001')];
        $this->getJson('/api/staff/admin/event-types', $adminHeaders)->assertOk();

        $this->patchJson('/api/staff/admin/event-types/early_birth', ['standardFee' => 8], $adminHeaders)
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'REASON_REQUIRED');

        $this->patchJson('/api/staff/admin/event-types/early_birth', ['expressFee' => 1, 'reason' => 'test'], $adminHeaders)
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'INVALID_FEE');

        $this->patchJson('/api/staff/admin/event-types/early_birth', ['standardFee' => 8, 'reason' => 'test adjustment'], $adminHeaders)
            ->assertOk()
            ->assertJsonPath('standardFee', 8);
    }

    public function test_admin_can_create_a_staff_user_and_the_invite_email_actually_sends(): void
    {
        Mail::fake();
        $adminHeaders = ['Authorization' => 'Bearer '.$this->staffToken('ADM-001')];

        $this->postJson('/api/staff/admin/users', [
            'staffId' => 'OFF-777',
            'fullName' => 'New Officer',
            'role' => 'REGISTRATION_OFFICER',
            'email' => 'new.officer@example.com',
        ], $adminHeaders)
            ->assertCreated()
            ->assertJsonPath('staffId', 'OFF-777')
            ->assertJsonStructure(['temporaryPassword']);

        Mail::assertSent(StaffInviteMail::class, fn (StaffInviteMail $mail) => $mail->hasTo('new.officer@example.com'));

        $this->postJson('/api/staff/admin/users', [
            'staffId' => 'OFF-777', 'fullName' => 'Dupe', 'role' => 'REGISTRATION_OFFICER',
        ], $adminHeaders)->assertStatus(409)->assertJsonPath('error.code', 'STAFF_ID_TAKEN');
    }

    public function test_non_admin_cannot_manage_staff_users(): void
    {
        $officerHeaders = ['Authorization' => 'Bearer '.$this->staffToken('OFF-001')];
        $this->getJson('/api/staff/admin/users', $officerHeaders)->assertStatus(403);
    }

    public function test_deactivating_staff_reassigns_their_in_progress_applications_to_a_supervisor(): void
    {
        $adminHeaders = ['Authorization' => 'Bearer '.$this->staffToken('ADM-001')];
        $officerHeaders = ['Authorization' => 'Bearer '.$this->staffToken('OFF-001')];

        $result = $this->registerCitizen();
        $id = $this->submittedApplication($result['accessToken'], 'early_birth', self::EARLY_BIRTH_FORM, self::EARLY_BIRTH_DOCS);
        $this->payFor($id, $result['accessToken']);
        $this->postJson("/api/staff/applications/{$id}/claim", [], $officerHeaders)->assertOk();

        $officerId = \App\Models\StaffUser::where('staff_id', 'OFF-001')->value('id');
        $supervisorId = \App\Models\StaffUser::where('staff_id', 'SUP-001')->value('id');

        $this->patchJson("/api/staff/admin/users/{$officerId}", ['active' => false], $adminHeaders)
            ->assertOk()
            ->assertJsonPath('active', false);

        $this->assertEquals($supervisorId, \App\Models\Application::find($id)->assigned_staff_id);

        // Deactivated officer can no longer authenticate.
        $this->postJson('/api/staff/login', ['staffId' => 'OFF-001', 'password' => 'changeme123'])
            ->assertStatus(401);
    }

    public function test_otp_send_is_rate_limited_per_phone(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/register/send-otp', ['phone' => '200000099'])->assertOk();
        }
        $this->postJson('/api/auth/register/send-otp', ['phone' => '200000099'])
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'TOO_MANY_REQUESTS');
    }
}
