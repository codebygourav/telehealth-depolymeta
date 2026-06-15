<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('doctor_id');
            $table->uuid('availability_id')->nullable();
            $table->uuid('availability_override_id')->nullable();
            $table->string('source')->default('manual_sheet');
            $table->string('import_batch_id')->nullable();
            $table->string('source_row_id')->nullable();
            $table->string('source_doctor_id')->nullable();
            $table->string('doctor_name')->nullable();
            $table->string('patient_name')->nullable();
            $table->string('patient_unit_number')->nullable();
            $table->string('patient_email')->nullable();
            $table->string('mobile')->nullable();
            $table->date('appointment_date');
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->string('consultation_type')->default('in-person');
            $table->string('opd_type')->default('private');
            $table->string('track_upload_status')->nullable();
            $table->string('stack_upload_status')->nullable();
            $table->timestamp('source_created_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('doctor_id')->references('id')->on('doctors')->cascadeOnDelete();
            $table->foreign('availability_id')->references('id')->on('availabilities')->nullOnDelete();
            $table->foreign('availability_override_id')->references('id')->on('doctor_availability_overrides')->nullOnDelete();

            $table->unique(['doctor_id', 'source', 'source_row_id'], 'external_booking_source_unique');
            $table->index(['doctor_id', 'appointment_date', 'start_time'], 'external_booking_slot_idx');
            $table->index(['availability_id', 'appointment_date'], 'external_booking_availability_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_bookings');
    }
};
