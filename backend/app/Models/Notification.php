<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Notification extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];
    protected $casts = ['read_at' => 'datetime'];

    // Eloquent's default 'Y-m-d H:i:s' format drops microseconds even though
    // the column can store them (see the migration), which made ordering by
    // created_at nondeterministic for notifications created within the same
    // second (e.g. approve -> complete back-to-back).
    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected static function booted(): void
    {
        static::creating(function (Notification $notification) {
            $notification->id ??= 'ntf_'.Str::uuid();
        });
    }

    public function citizen()
    {
        return $this->belongsTo(Citizen::class);
    }

    public function toContract(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'applicationId' => $this->application_id,
            'read' => $this->read_at !== null,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
