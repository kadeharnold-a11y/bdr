<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthSession extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'token';

    protected $guarded = [];

    protected $casts = [
        'profile' => 'array',
        'otp_verified' => 'boolean',
        'otp_expires_at' => 'datetime',
    ];
}
