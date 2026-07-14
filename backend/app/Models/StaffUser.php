<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class StaffUser extends Model
{
    use HasApiTokens;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];
    protected $hidden = ['password', 'two_factor_secret'];
    protected $casts = [
        'two_factor_secret' => 'encrypted',
        'two_factor_confirmed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (StaffUser $staff) {
            $staff->id ??= (string) Str::uuid();
        });
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }
}
