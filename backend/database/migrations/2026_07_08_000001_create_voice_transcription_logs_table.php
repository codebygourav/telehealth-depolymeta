<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voice_transcription_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Context
            $table->string('module')->default('prescription'); // prescription, consultation, etc.
            $table->uuid('module_record_id')->nullable();       // e.g. prescription_draft_id
            $table->uuid('appointment_id')->nullable();
            $table->uuid('doctor_id')->nullable();
            $table->uuid('patient_id')->nullable();

            // Transcription
            $table->text('transcript')->nullable();
            $table->float('audio_duration_seconds')->default(0);
            $table->string('language', 20)->default('en');
            $table->string('model', 50)->default('nova-2');
            $table->string('audio_mime_type', 80)->nullable();
            $table->float('confidence')->nullable();    // 0–100

            // Billing
            $table->decimal('credits_used', 10, 6)->default(0); // USD cost estimate

            // Deepgram metadata
            $table->string('deepgram_request_id')->nullable();
            $table->json('deepgram_response')->nullable();

            // Status
            $table->enum('status', ['success', 'failed'])->default('success');
            $table->text('error_message')->nullable();

            // Audit
            $table->uuid('created_by')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('module');
            $table->index('appointment_id');
            $table->index('doctor_id');
            $table->index('patient_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_transcription_logs');
    }
};
