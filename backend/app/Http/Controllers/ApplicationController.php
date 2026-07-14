<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\EventTypeConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApplicationController extends Controller
{
    private function error(string $code, string $message, int $status, array $extra = []): JsonResponse
    {
        return response()->json(['error' => ['code' => $code, 'message' => $message, ...$extra]], $status);
    }

    private function ownApplication(Request $request, string $id): ?Application
    {
        $application = Application::find($id);

        return ($application && $application->citizen_id === $request->user()->id) ? $application : null;
    }

    // Public catalogue - PRD 6.1 tier selection needs fees/SLA before
    // login-gated steps, and the event picker (PRD 6.2 step 2) lists all six.
    public function eventTypes(): JsonResponse
    {
        return response()->json(EventTypeConfig::all()->map(fn ($config) => [
            'eventType' => $config->event_type,
            'label' => $config->label,
            'formSupported' => $config->form_supported,
            'tiers' => [
                'standard' => ['fee' => $config->standard_fee, 'durationDays' => $config->standard_duration_days],
                'express' => $config->express_enabled
                    ? ['fee' => $config->express_fee, 'durationDays' => $config->express_duration_days]
                    : null,
            ],
        ]));
    }

    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->applications()->orderByDesc('last_saved_at');
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return response()->json($query->get()->map->toContract());
    }

    // PRD 6.2 steps 2-3: pick event type + tier before any form fields appear.
    public function store(Request $request): JsonResponse
    {
        $config = EventTypeConfig::find($request->input('eventType'));
        if (! $config) {
            return $this->error('INVALID_EVENT_TYPE', 'Unknown event type', 400);
        }
        $tier = $request->input('tier');
        if (! in_array($tier, ['standard', 'express'], true)) {
            return $this->error('INVALID_TIER', "Tier must be 'standard' or 'express'", 400);
        }
        if ($tier === 'express' && ! $config->express_enabled) {
            return $this->error('EXPRESS_UNAVAILABLE', 'Express service is not available for this event type', 400);
        }

        $application = Application::create([
            'citizen_id' => $request->user()->id,
            'event_type' => $config->event_type,
            'tier' => $tier,
            'status' => 'DRAFT',
            'form_data' => [],
            'fee_amount' => $tier === 'express' ? $config->express_fee : $config->standard_fee,
            'fee_currency' => 'GHS',
            'last_saved_at' => now(),
        ]);

        AuditLog::record('citizen', $request->user()->id, 'APPLICATION_CREATED', 'application', $application->id);

        return response()->json($application->toContract(), 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $application = $this->ownApplication($request, $id);
        if (! $application) {
            return $this->error('NOT_FOUND', 'Application not found', 404);
        }

        $documents = $application->documents->map(fn ($d) => [
            'id' => $d->id,
            'field_name' => $d->field_name,
            'original_name' => $d->original_name,
            'mime_type' => $d->mime_type,
            'size_bytes' => $d->size_bytes,
            'created_at' => $d->created_at?->toISOString(),
        ]);

        return response()->json([...$application->toContract(), 'documents' => $documents]);
    }

    // PRD 6.3 Save for Later: autosave and manual save both land here as a
    // form_data merge. Also used to edit a CORRECTIONS_REQUIRED application
    // before calling /resubmit (PRD 11.3 step 6).
    public function update(Request $request, string $id): JsonResponse
    {
        $application = $this->ownApplication($request, $id);
        if (! $application) {
            return $this->error('NOT_FOUND', 'Application not found', 404);
        }
        if (! in_array($application->status, ['DRAFT', 'CORRECTIONS_REQUIRED'], true)) {
            return $this->error('NOT_EDITABLE', 'Only draft or corrections-required applications can be edited', 409);
        }

        $updates = [
            'form_data' => [...$application->form_data ?? [], ...(array) $request->input('formData', [])],
            'last_saved_at' => now(),
        ];

        // PRD 6.3 Tier Lock: tier can only change pre-payment (DRAFT), never
        // while an already-paid application is back for corrections.
        $tier = $request->input('tier');
        if ($application->status === 'DRAFT' && $tier && $tier !== $application->tier) {
            $config = EventTypeConfig::find($application->event_type);
            if ($tier === 'express' && ! $config->express_enabled) {
                return $this->error('EXPRESS_UNAVAILABLE', 'Express service is not available for this event type', 400);
            }
            $updates['tier'] = $tier;
            $updates['fee_amount'] = $tier === 'express' ? $config->express_fee : $config->standard_fee;
        }

        $application->update($updates);

        return response()->json($application->fresh()->toContract());
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $application = $this->ownApplication($request, $id);
        if (! $application) {
            return $this->error('NOT_FOUND', 'Application not found', 404);
        }
        if ($application->status !== 'DRAFT') {
            return $this->error('NOT_EDITABLE', 'Only draft applications can be deleted', 409);
        }

        $application->documents()->delete();
        $application->delete();

        return response()->json(null, 204);
    }

    // PRD 6.2 step 6: document upload, one file per named slot.
    public function uploadDocument(Request $request, string $id): JsonResponse
    {
        $application = $this->ownApplication($request, $id);
        if (! $application) {
            return $this->error('NOT_FOUND', 'Application not found', 404);
        }
        if ($application->status !== 'DRAFT') {
            return $this->error('NOT_EDITABLE', 'Only draft applications accept document uploads', 409);
        }

        $fieldName = $request->input('fieldName');
        $file = $request->file('file');
        if (! $fieldName || ! $file) {
            return $this->error('MISSING_FILE', 'fieldName and file are required', 400);
        }
        if ($file->getSize() > 5 * 1024 * 1024) { // PRD 6.2 step 6: 5MB max.
            return $this->error('FILE_TOO_LARGE', 'Documents must be 5MB or smaller', 400);
        }

        $document = Document::create([
            'application_id' => $application->id,
            'field_name' => $fieldName,
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $file->store('documents'),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
        ]);

        return response()->json([
            'id' => $document->id,
            'fieldName' => $fieldName,
            'originalName' => $document->original_name,
            'sizeBytes' => $document->size_bytes,
        ], 201);
    }

    // Citizen re-downloading their own upload. Scoped through the owning
    // application so document IDs can't be probed across applications.
    public function downloadDocument(Request $request, string $id, string $documentId)
    {
        $application = $this->ownApplication($request, $id);
        $document = $application?->documents()->where('id', $documentId)->first();
        if (! $document) {
            return $this->error('NOT_FOUND', 'Document not found', 404);
        }

        return Storage::download($document->stored_path, $document->original_name);
    }

    // PRD 12: citizen downloads their own certificate PDF once the
    // application is COMPLETED.
    public function downloadCertificate(Request $request, string $id)
    {
        $application = $this->ownApplication($request, $id);
        $certificate = $application?->certificate;
        if (! $certificate) {
            return $this->error('NOT_FOUND', 'No certificate available for this application', 404);
        }

        return Storage::download($certificate->pdf_path, "{$certificate->serial}.pdf");
    }

    // PRD 6.2 steps 7-9: review + declaration + move to payment. Tracking ID
    // isn't assigned yet - that happens on payment success (PRD 7.2 step 6).
    public function submit(Request $request, string $id): JsonResponse
    {
        $application = $this->ownApplication($request, $id);
        if (! $application) {
            return $this->error('NOT_FOUND', 'Application not found', 404);
        }
        if ($application->status !== 'DRAFT') {
            return $this->error('NOT_SUBMITTABLE', 'Application already submitted', 409);
        }

        $schema = config('form_schemas.'.$application->event_type);
        if (! $schema) {
            return $this->error('UNSUPPORTED_EVENT_TYPE', "This event type isn't accepting online applications yet", 400);
        }

        $formData = $application->form_data ?? [];
        $missingFields = array_values(array_filter(
            $schema['required_fields'],
            fn ($field) => ! isset($formData[$field]) || $formData[$field] === ''
        ));
        if ($missingFields) {
            return $this->error('INCOMPLETE_FORM', 'Required fields are missing', 400, ['missingFields' => $missingFields]);
        }

        $uploadedFields = $application->documents()->pluck('field_name')->unique()->all();
        $missingDocuments = array_values(array_diff($schema['required_documents'], $uploadedFields));
        if ($missingDocuments) {
            return $this->error('MISSING_DOCUMENTS', 'Required documents are missing', 400, ['missingDocuments' => $missingDocuments]);
        }

        $application->update(['status' => 'PAYMENT_PENDING', 'last_saved_at' => now()]);
        AuditLog::record('citizen', $request->user()->id, 'APPLICATION_READY_FOR_PAYMENT', 'application', $application->id);

        return response()->json([
            'applicationId' => $application->id,
            'status' => 'PAYMENT_PENDING',
            'feeAmount' => $application->fee_amount,
            'feeCurrency' => $application->fee_currency,
        ]);
    }

    // PRD 11.3 steps 6-7: citizen edits (via PATCH) after a corrections
    // request, then resubmits into the officer queue.
    public function resubmit(Request $request, string $id): JsonResponse
    {
        $application = $this->ownApplication($request, $id);
        if (! $application) {
            return $this->error('NOT_FOUND', 'Application not found', 404);
        }
        if ($application->status !== 'CORRECTIONS_REQUIRED') {
            return $this->error('NOT_RESUBMITTABLE', 'Application is not awaiting corrections', 409);
        }

        $application->update(['status' => 'UNDER_REVIEW', 'last_saved_at' => now()]);
        AuditLog::record('citizen', $request->user()->id, 'APPLICATION_RESUBMITTED', 'application', $application->id);

        return response()->json($application->fresh()->toContract());
    }
}
