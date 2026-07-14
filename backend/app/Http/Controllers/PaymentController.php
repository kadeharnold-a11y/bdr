<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\AuditLog;
use App\Models\EventTypeConfig;
use App\Models\Payment;
use App\Support\Notifier;
use App\Support\TrackingId;
use App\Support\WorkingDays;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

// PRD 7: Npontu Pay integration. No sandbox credentials exist yet (PRD 15.2
// assumption), so mock mode lets the whole flow run locally: "initiate"
// creates a PENDING payment and /mock-confirm simulates the provider webhook.
class PaymentController extends Controller
{
    private function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json(['error' => ['code' => $code, 'message' => $message]], $status);
    }

    private function isMockMode(): bool
    {
        return config('hbdrp.npontu_pay_mode') !== 'live';
    }

    private function confirmPayment(Payment $payment, string $providerRef): array
    {
        $application = $payment->application;
        $payment->update(['status' => 'SUCCESS', 'provider_ref' => $providerRef]);

        $config = EventTypeConfig::find($application->event_type);
        $durationDays = $application->tier === 'express'
            ? $config->express_duration_days
            : $config->standard_duration_days;

        $application->update([
            'status' => 'SUBMITTED',
            'tracking_id' => TrackingId::generate($application->event_type),
            'sla_deadline' => WorkingDays::addTo(now(), $durationDays),
            'submitted_at' => now(),
        ]);

        AuditLog::record('system', null, 'PAYMENT_CONFIRMED', 'application', $application->id, [
            'trackingId' => $application->tracking_id,
            'providerRef' => $providerRef,
        ]);
        Notifier::applicationSubmitted($application);

        return [
            'trackingId' => $application->tracking_id,
            'slaDeadline' => $application->sla_deadline->toISOString(),
        ];
    }

    public function initiate(Request $request): JsonResponse
    {
        $application = Application::where('id', $request->input('applicationId'))
            ->where('citizen_id', $request->user()->id)
            ->first();
        if (! $application) {
            return $this->error('NOT_FOUND', 'Application not found', 404);
        }
        if ($application->status !== 'PAYMENT_PENDING') {
            return $this->error('NOT_PAYABLE', 'Application is not awaiting payment', 409);
        }

        $payment = $application->payments()->where('status', 'PENDING')->first()
            ?? Payment::create([
                'application_id' => $application->id,
                'method' => $request->input('method'),
                'amount' => $application->fee_amount,
                'currency' => $application->fee_currency,
                'status' => 'PENDING',
            ]);

        return response()->json([
            'paymentId' => $payment->id,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'mode' => $this->isMockMode() ? 'mock' : 'live',
            'mockConfirmAvailable' => $this->isMockMode(),
        ]);
    }

    // Dev-only convenience standing in for the real Npontu Pay webhook while
    // no sandbox credentials exist. Not part of the real product surface.
    public function mockConfirm(Request $request): JsonResponse
    {
        if (! $this->isMockMode()) {
            return $this->error('MOCK_DISABLED', 'NPONTU_PAY_MODE is live; use the real webhook', 403);
        }

        $payment = Payment::find($request->input('paymentId'));
        if (! $payment || $payment->application->citizen_id !== $request->user()->id) {
            return $this->error('NOT_FOUND', 'Payment not found', 404);
        }
        if ($payment->status !== 'PENDING') {
            return $this->error('ALREADY_PROCESSED', 'Payment already processed', 409);
        }

        return response()->json(['status' => 'SUCCESS', ...$this->confirmPayment($payment, 'MOCK-txn_'.Str::uuid())]);
    }

    // PRD 7.2 step 5: real Npontu Pay webhook -
    // {status, transaction_id, amount, currency, timestamp, application_ref}.
    // TODO: verify webhook signature once Npontu Pay's signing scheme is
    // documented (not available yet - PRD 15.2).
    public function webhook(Request $request): JsonResponse
    {
        $payment = Payment::where('application_id', $request->input('application_ref'))
            ->where('status', 'PENDING')
            ->first();
        if (! $payment) {
            return $this->error('NOT_FOUND', 'No pending payment for this application', 404);
        }

        if ($request->input('status') !== 'SUCCESS') {
            $payment->update(['status' => 'FAILED', 'provider_ref' => $request->input('transaction_id')]);

            return response()->json(['received' => true]);
        }

        $this->confirmPayment($payment, (string) $request->input('transaction_id'));

        return response()->json(['received' => true]);
    }

    // PRD 7.2 step 7: e-receipt content. Actual PDF rendering isn't built in
    // this v1 slice - returns the structured data a PDF template would use.
    public function receipt(Request $request, string $applicationId): JsonResponse
    {
        $application = Application::where('id', $applicationId)
            ->where('citizen_id', $request->user()->id)
            ->first();
        if (! $application) {
            return $this->error('NOT_FOUND', 'Application not found', 404);
        }

        $payment = $application->payments()->where('status', 'SUCCESS')->latest('updated_at')->first();
        if (! $payment) {
            return $this->error('NOT_FOUND', 'No completed payment for this application', 404);
        }

        return response()->json([
            'trackingId' => $application->tracking_id,
            'eventType' => $application->event_type,
            'tier' => $application->tier,
            'amountPaid' => $payment->amount,
            'currency' => $payment->currency,
            'providerRef' => $payment->provider_ref,
            'paidAt' => $payment->updated_at?->toISOString(),
            'citizenName' => $request->user()->full_name,
        ]);
    }
}
