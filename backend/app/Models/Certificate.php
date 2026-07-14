<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Certificate extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];
    protected $casts = ['issued_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(function (Certificate $certificate) {
            $certificate->id ??= 'cert_'.Str::uuid();
        });
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function toContract(): array
    {
        return [
            'id' => $this->id,
            'serial' => $this->serial,
            'applicationId' => $this->application_id,
            'issuedAt' => $this->issued_at?->toISOString(),
        ];
    }
}
