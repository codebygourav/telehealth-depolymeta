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
        Schema::create('patient_vaccinations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('patient_id');

            $table->uuid('doctor_id');

            $table->uuid('vaccination_id');

            $table->uuid('vaccination_template_id')->nullable();

            $table->integer('dose_no')->nullable();

            $table->date('scheduled_date')->nullable();

            $table->date('completed_date')->nullable();

            $table->enum('status', [
                'pending',
                'scheduled',
                'completed',
                'missed',
                'cancelled'
            ])->default('pending');

            $table->string('batch_number')->nullable();

            $table->string('manufacturer')->nullable();

            $table->string('given_at')->nullable();

            $table->string('given_by')->nullable();

            $table->text('doctor_notes')->nullable();

            $table->text('side_effect_observed')->nullable();

            $table->text('patient_reaction')->nullable();

            $table->boolean('reminder_sent')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('doctor_id')->references('id')->on('doctors')->cascadeOnDelete();
            $table->foreign('vaccination_id')->references('id')->on('vaccinations')->restrictOnDelete();
            $table->foreign('vaccination_template_id')->references('id')->on('vaccination_templates')->nullOnDelete();
            $table->index(['patient_id', 'status', 'scheduled_date']);
            $table->index(['doctor_id', 'status', 'scheduled_date']);
            $table->index(['scheduled_date', 'reminder_sent']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_vaccinations');
    }
};
