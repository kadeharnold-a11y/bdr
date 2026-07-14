<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class TrackingId
{
    // PRD 8.1 event codes.
    public const EVENT_CODES = [
        'early_birth' => 'EB',
        'late_birth' => 'LB',
        'death' => 'DR',
        'foetal_death' => 'FD',
        'adoption' => 'AD',
        'surrogacy' => 'SR',
    ];

    // PRD 8.1: BDR-{YYYY}-{EVENT_CODE}-{6-DIGIT-SEQUENCE}, resets annually.
    public static function generate(string $eventType): string
    {
        $code = self::EVENT_CODES[$eventType] ?? throw new \InvalidArgumentException("Unknown event type: {$eventType}");
        $year = now()->year;
        $key = "tracking:{$year}:{$eventType}";

        return DB::transaction(function () use ($code, $year, $key) {
            DB::statement(
                'INSERT INTO sequences (seq_key, value) VALUES (?, 1)
                 ON CONFLICT(seq_key) DO UPDATE SET value = value + 1',
                [$key]
            );
            $seq = DB::table('sequences')->where('seq_key', $key)->value('value');

            return sprintf('BDR-%d-%s-%06d', $year, $code, $seq);
        });
    }
}
