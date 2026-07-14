<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditLog extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];
    protected $casts = ['details' => 'array'];

    protected static function booted(): void
    {
        static::creating(function (AuditLog $log) {
            $log->id ??= (string) Str::uuid();
        });
    }

    // PRD 13 audit logging. Immutability/retention isn't enforced at the DB
    // layer yet - see shared/api-contract.md known gaps.
    public static function record(
        string $actorType,
        ?string $actorId,
        string $action,
        ?string $entityType = null,
        ?string $entityId = null,
        ?array $details = null,
    ): void {
        static::create([
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details' => $details,
        ]);
    }
}
