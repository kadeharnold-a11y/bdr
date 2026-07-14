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
    protected $hidden = ['password'];

    protected static function booted(): void
    {
        static::creating(function (StaffUser $staff) {
            $staff->id ??= (string) Str::uuid();
        });
    }
}
