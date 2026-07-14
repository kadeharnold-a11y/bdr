<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Domain tables ported from the Express prototype's schema.sql (see the
// feature/backend-scaffold branch). Field lists stay deliberately generic
// where the PRD itself was incomplete - PRD sections 5A-5F never shipped
// their promised field-list attachment.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citizens', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('phone')->unique();
            $table->string('email')->nullable();
            $table->string('full_name');
            $table->string('ghana_card_number')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('pin_hash');
            $table->string('nia_status')->default('UNVERIFIED');
            $table->timestamps();
        });

        // Multi-step registration (PRD 4.1) and login (PRD 4.2) flows before a
        // citizen record exists / a session is fully authenticated.
        Schema::create('auth_sessions', function (Blueprint $table) {
            $table->string('token')->primary();
            $table->string('purpose'); // register | login
            $table->string('phone');
            $table->string('citizen_id')->nullable();
            $table->string('otp_code');
            $table->dateTime('otp_expires_at');
            $table->boolean('otp_verified')->default(false);
            $table->json('profile')->nullable();
            $table->timestamps();
        });

        Schema::create('event_type_configs', function (Blueprint $table) {
            $table->string('event_type')->primary();
            $table->string('label');
            $table->decimal('standard_fee', 10, 2);
            $table->decimal('express_fee', 10, 2);
            $table->unsignedInteger('standard_duration_days');
            $table->unsignedInteger('express_duration_days');
            $table->boolean('express_enabled')->default(true);
            // Whether POST /api/applications accepts this event type yet; code
            // capability, not admin config (only early_birth + death so far).
            $table->boolean('form_supported')->default(false);
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('tracking_id')->unique()->nullable();
            $table->string('citizen_id')->index();
            $table->string('event_type');
            $table->string('tier'); // standard | express
            $table->string('status')->default('DRAFT');
            $table->json('form_data');
            $table->decimal('fee_amount', 10, 2)->nullable();
            $table->string('fee_currency')->default('GHS');
            $table->dateTime('sla_deadline')->nullable();
            $table->string('assigned_staff_id')->nullable();
            $table->dateTime('last_saved_at');
            $table->dateTime('submitted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('application_id')->index();
            $table->string('field_name');
            $table->string('original_name');
            $table->string('stored_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('application_id')->index();
            $table->string('method')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('GHS');
            $table->string('provider_ref')->nullable();
            $table->string('status')->default('PENDING'); // PENDING | SUCCESS | FAILED | REFUNDED
            $table->timestamps();
        });

        Schema::create('staff_users', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('staff_id')->unique();
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('role'); // ADMIN | REGISTRATION_OFFICER | SUPERVISOR | FINANCE_OFFICER
            $table->string('password');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Per-year, per-event-type counters for tracking IDs (PRD 8.1:
        // BDR-{YYYY}-{EVENT_CODE}-{6-DIGIT-SEQUENCE}, resets annually).
        Schema::create('sequences', function (Blueprint $table) {
            $table->string('seq_key')->primary();
            $table->unsignedBigInteger('value')->default(0);
        });

        // Minimal audit log (PRD 13: actor, timestamp, action, before/after).
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('actor_type');
            $table->string('actor_id')->nullable();
            $table->string('action');
            $table->string('entity_type')->nullable();
            $table->string('entity_id')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('sequences');
        Schema::dropIfExists('staff_users');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('event_type_configs');
        Schema::dropIfExists('auth_sessions');
        Schema::dropIfExists('citizens');
    }
};
