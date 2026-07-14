<?php

namespace Tests\Feature;

use App\Mail\OtpMail;
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

    private function staffToken(string $staffId = 'OFF-001'): string
    {
        $login = $this->postJson('/api/staff/login', ['staffId' => $staffId, 'password' => 'changeme123']);
        $login->assertOk();

        return $login->json('accessToken');
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

    public function test_unsupported_event_type_is_rejected_on_submit(): void
    {
        $result = $this->registerCitizen();
        $headers = ['Authorization' => "Bearer {$result['accessToken']}"];

        $created = $this->postJson('/api/applications', ['eventType' => 'adoption', 'tier' => 'standard'], $headers);
        $created->assertCreated();

        $this->postJson("/api/applications/{$created->json('id')}/submit", [], $headers)
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'UNSUPPORTED_EVENT_TYPE');
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
        $this->postJson("/api/staff/applications/{$id}/complete", [], $headers)
            ->assertOk()
            ->assertJsonPath('status', 'COMPLETED');
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

    public function test_staff_cannot_use_citizen_routes_and_vice_versa(): void
    {
        $result = $this->registerCitizen();
        $staffHeaders = ['Authorization' => 'Bearer '.$this->staffToken()];
        $citizenHeaders = ['Authorization' => "Bearer {$result['accessToken']}"];

        $this->getJson('/api/citizens/me/dashboard', $staffHeaders)->assertStatus(401);
        $this->getJson('/api/staff/queue', $citizenHeaders)->assertStatus(401);
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
