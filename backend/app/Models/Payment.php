<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Payment extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = ['amount' => 'float'];

    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            $payment->id ??= 'pay_'.Str::uuid();
        });
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
