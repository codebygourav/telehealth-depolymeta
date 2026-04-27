<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\AppointmentStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id');
            $table->uuid('doctor_id');
            $table->uuid('availability_id')->nullable(); // Link to availabilities
            $table->date('appointment_date')->nullable();
            $table->time('appointment_time')->nullable(); // Start time from availability
            $table->time('appointment_end_time'); // End time from availability
            $table->string('stamp_preference')->default('only_department');
            $table->string('slug')->unique();
            $table->text('instructions_by_doctor')->nullable();
            $table->date('next_visit_date')->nullable();
            $table->enum('status', array_map(fn($case) => $case->value, AppointmentStatus::cases()))->default(AppointmentStatus::default());
            $table->string('consultation_type');
            $table->json('notes')->nullable();
            $table->decimal('fee_amount', 10, 2)->nullable();
            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('doctor_id')->references('id')->on('doctors')->cascadeOnDelete();
            $table->foreign('availability_id')->references('id')->on('availabilities')->cascadeOnDelete();
            $table->uuid('created_by')->nullable()->index();
            $table->uuid('updated_by')->nullable()->index();
            $table->uuid('deleted_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index('doctor_id');
            $table->index('patient_id');
            $table->index('appointment_date');
            $table->index(['doctor_id', 'appointment_date']);
            $table->index(['patient_id', 'appointment_date']);
            $table->index('status');
            $table->index('availability_id');
            $table->index(['doctor_id', 'status']);
            $table->index(['patient_id', 'status']);
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
