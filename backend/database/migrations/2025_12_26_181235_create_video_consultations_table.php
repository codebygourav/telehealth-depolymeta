<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('video_consultations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('appointment_id');
            $table->uuid('patient_id');
            $table->uuid('doctor_id');

            // Video consultation URLs
            $table->longText('room_url')->nullable(); // General room URL
            $table->longText('host_url')->nullable(); // URL for doctor (host)
            $table->longText('participate_url')->nullable(); // URL for patient (participant)
            $table->string('room_id')->unique()->nullable(); // Unique room identifier

            // Status tracking
            $table->string('status')->default('pending'); // pending, active, completed, cancelled
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            // Additional metadata
            $table->json('metadata')->nullable(); // Store additional video service data

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('appointment_id')->references('id')->on('appointments')->cascadeOnDelete();
            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('doctor_id')->references('id')->on('doctors')->cascadeOnDelete();

            $table->uuid('created_by')->nullable()->index();
            $table->uuid('updated_by')->nullable()->index();
            $table->uuid('deleted_by')->nullable()->index();
            // Indexes
            $table->index('appointment_id');
            $table->index('patient_id');
            $table->index('doctor_id');
            $table->index('status');
            $table->index('room_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_consultations');
    }
};