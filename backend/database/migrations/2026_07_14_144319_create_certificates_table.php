<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// PRD 12: digitally-sealed certificate PDF with an embedded QR code linking
// to a public verification page. One certificate per application, generated
// when staff mark it COMPLETED (StaffController::complete).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('application_id')->unique();
            $table->string('serial')->unique();
            $table->string('pdf_path');
            $table->dateTime('issued_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
