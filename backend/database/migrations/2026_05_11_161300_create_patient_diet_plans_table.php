<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_diet_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id');
            $table->uuid('doctor_id');
            $table->uuid('diet_template_id');
            $table->string('template_name');
            $table->text('template_description')->nullable();
            $table->unsignedInteger('duration_days')->default(7);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['draft', 'active', 'paused', 'completed', 'cancelled'])->default('active');
            $table->text('special_instructions')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('doctor_id')->references('id')->on('doctors')->cascadeOnDelete();
            $table->foreign('diet_template_id')->references('id')->on('diet_templates')->restrictOnDelete();
            $table->index(['patient_id', 'status']);
            $table->index(['doctor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_diet_plans');
    }
};
