<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('appointment_id');
            $table->uuid('doctor_id');
            $table->uuid('patient_id');
            $table->uuid('medicine_id')->nullable();
            // Medicine details (can be custom or from medicine table)
            $table->string('medicine_name');
            $table->string('medicine_type')->nullable(); // Oral Pill, Injection, Syrup, etc.
            $table->string('dosage')->nullable(); // e.g., "500mg", "10ml"
            $table->string('frequency')->nullable(); // e.g., "3 times a day"
            $table->json('frequency_times')->nullable(); // e.g., ["07:00", "12:00", "18:00"]
            $table->string('duration')->nullable(); // e.g., "7 days", "ongoing"
            $table->string('duration_type')->default('days'); // days, weeks, months, ongoing
            $table->integer('duration_value')->nullable();
            $table->text('instructions')->nullable(); // e.g., "Take after meals"
            $table->integer('quantity')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_ongoing')->default(false);

            $table->integer('order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable()->index();
            $table->uuid('updated_by')->nullable()->index();
            $table->uuid('deleted_by')->nullable()->index();
            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('cascade');
            $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('cascade');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('medicine_id')->references('id')->on('medicines')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
