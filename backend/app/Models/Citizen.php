<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class Citizen extends Model
{
    use HasApiTokens;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];
    protected $hidden = ['pin_hash'];

    protected static function booted(): void
    {
        static::creating(function (Citizen $citizen) {
            $citizen->id ??= 'cit_'.Str::uuid();
        });
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }
}
