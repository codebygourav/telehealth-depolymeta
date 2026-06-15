<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();

            // What type of email was sent
            $table->string('type', 100)->index();          // e.g. PatientBookingConfirmationMail
            $table->string('to_email', 255)->index();
            $table->string('subject', 500)->nullable();

            // Linked records (nullable since not all emails relate to appointments)
            $table->uuid('appointment_id')->nullable()->index();
            $table->uuid('payment_id')->nullable()->index();

            // Result
            $table->enum('status', ['sent', 'failed'])->default('sent')->index();
            $table->text('error_message')->nullable();

            // Attempt number (1, 2, 3 for retries)
            $table->unsignedTinyInteger('attempt')->default(1);

            // Timestamps
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            // Index for daily stats queries
            $table->index(['status', 'created_at']);
            $table->index(['type', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
