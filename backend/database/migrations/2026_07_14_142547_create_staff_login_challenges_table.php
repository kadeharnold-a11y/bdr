<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Bridges password-verified but not-yet-2FA-verified staff logins, the same
// role auth_sessions plays for citizen OTP - a short-lived opaque token
// instead of immediately issuing a real Sanctum access token.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_login_challenges', function (Blueprint $table) {
            $table->string('token')->primary();
            $table->string('staff_user_id')->index();
            $table->boolean('is_new_setup')->default(false);
            $table->dateTime('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_login_challenges');
    }
};
