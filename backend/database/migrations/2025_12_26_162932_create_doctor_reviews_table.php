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
        Schema::create('doctor_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();
            $table->enum('review_type', ['original', 'fake'])->default('original');
            $table->uuid('patient_id')->nullable();
            $table->uuid('faker_patient_id')->nullable();
            $table->uuid('doctor_id')->nullable();
            $table->uuid('appointment_id')->nullable();
            $table->string('title');
            $table->text('content');
            $table->integer('rating')->default(5);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('patient_id')->references('id')->on('patients')->nullOnDelete();
            // Note: faker_patient_id foreign key will be added in fake_reviewers migration
            $table->foreign('doctor_id')->references('id')->on('doctors')->nullOnDelete();
            $table->foreign('appointment_id')->references('id')->on('appointments')->nullOnDelete();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();

            $table->index('doctor_id');
            $table->index('patient_id');
            $table->index('appointment_id');
            $table->index('review_type');
            $table->index('is_active');
            $table->index('is_featured');
            $table->index(['doctor_id', 'is_active']);
            $table->index(['doctor_id', 'is_featured']);
            $table->index('rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_reviews');
    }
};