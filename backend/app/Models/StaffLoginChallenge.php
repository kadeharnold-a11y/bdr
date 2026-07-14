<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffLoginChallenge extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'token';

    protected $guarded = [];
    protected $casts = ['expires_at' => 'datetime', 'is_new_setup' => 'boolean'];

    public function staff()
    {
        return $this->belongsTo(StaffUser::class, 'staff_user_id');
    }
}
