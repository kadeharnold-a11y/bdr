<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventTypeConfig extends Model
{
    public $incrementing = false;
    public $timestamps = false;
    protected $keyType = 'string';
    protected $primaryKey = 'event_type';

    protected $guarded = [];

    protected $casts = [
        'standard_fee' => 'float',
        'express_fee' => 'float',
        'express_enabled' => 'boolean',
        'form_supported' => 'boolean',
    ];

    public function toContract(): array
    {
        return [
            'eventType' => $this->event_type,
            'label' => $this->label,
            'standardFee' => $this->standard_fee,
            'expressFee' => $this->express_fee,
            'standardDurationDays' => $this->standard_duration_days,
            'expressDurationDays' => $this->express_duration_days,
            'expressEnabled' => $this->express_enabled,
            'formSupported' => $this->form_supported,
        ];
    }
}
