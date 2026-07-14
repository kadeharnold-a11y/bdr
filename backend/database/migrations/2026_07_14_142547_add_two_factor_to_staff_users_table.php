<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// PRD 9.2 step 6: 2FA is mandatory for all back-office users. TOTP secret is
// generated on first login attempt (not at account creation) and only takes
// effect once confirmed with a valid code, so a staff member who never logs
// in doesn't have a dangling unconfirmed secret treated as active.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->after('password');
            $table->dateTime('two_factor_confirmed_at')->nullable()->after('two_factor_secret');
        });
    }

    public function down(): void
    {
        Schema::table('staff_users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_secret', 'two_factor_confirmed_at']);
        });
    }
};
