<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Application extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'form_data' => 'array',
        'sla_deadline' => 'datetime',
        'last_saved_at' => 'datetime',
        'submitted_at' => 'datetime',
        'fee_amount' => 'float',
    ];

    protected static function booted(): void
    {
        static::creating(function (Application $application) {
            $application->id ??= 'app_'.Str::uuid();
        });
    }

    public function citizen()
    {
        return $this->belongsTo(Citizen::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function certificate()
    {
        return $this->hasOne(Certificate::class);
    }

    // Response shape shared/api-contract.md documents for application objects.
    public function toContract(): array
    {
        return [
            'id' => $this->id,
            'trackingId' => $this->tracking_id,
            'eventType' => $this->event_type,
            'tier' => $this->tier,
            'status' => $this->status,
            'formData' => $this->form_data ?: (object) [],
            'feeAmount' => $this->fee_amount,
            'feeCurrency' => $this->fee_currency,
            'slaDeadline' => $this->sla_deadline?->toISOString(),
            'lastSavedAt' => $this->last_saved_at?->toISOString(),
            'createdAt' => $this->created_at?->toISOString(),
            'submittedAt' => $this->submitted_at?->toISOString(),
        ];
    }
}
