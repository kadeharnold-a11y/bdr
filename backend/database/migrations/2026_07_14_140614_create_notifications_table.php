<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// PRD 10.4's notification matrix: citizen-facing rows for Application
// Submitted, Corrections Requested, Application Approved, Certificate Ready.
// SMS delivery isn't wired up (no gateway - see shared/api-contract.md), so
// these are dashboard/in-app notifications only for now.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('citizen_id')->index();
            $table->string('application_id')->nullable()->index();
            $table->string('type');
            $table->string('title');
            $table->text('body');
            $table->dateTime('read_at')->nullable();
            // Microsecond precision: several notifications can legitimately
            // be created within the same second (e.g. approve then complete
            // back-to-back), and second-precision timestamps made dashboard
            // ordering nondeterministic.
            $table->timestamps(6);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
