<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auth_sessions', function (Blueprint $table) {
            $table->string('otp_channel')->nullable()->after('citizen_id');
            $table->string('delivery_target')->nullable()->after('otp_channel');
            $table->timestamp('otp_used_at')->nullable()->after('otp_verified');
        });

        // Existing rows store plaintext codes — clear them so all OTPs are
        // re-issued under the hardened hashed format.
        \DB::table('auth_sessions')->delete();
    }

    public function down(): void
    {
        Schema::table('auth_sessions', function (Blueprint $table) {
            $table->dropColumn(['otp_channel', 'delivery_target', 'otp_used_at']);
        });
    }
};
