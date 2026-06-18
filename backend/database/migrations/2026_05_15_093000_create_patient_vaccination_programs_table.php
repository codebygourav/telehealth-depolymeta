<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_vaccination_programs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id');
            $table->uuid('vaccination_program_id');
            $table->uuid('vaccination_template_id');
            $table->uuid('doctor_id');
            $table->date('start_date');
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('vaccination_program_id')->references('id')->on('vaccination_programs')->cascadeOnDelete();
            $table->foreign('vaccination_template_id')->references('id')->on('vaccination_templates')->cascadeOnDelete();
            $table->foreign('doctor_id')->references('id')->on('doctors')->cascadeOnDelete();
            $table->index(['patient_id', 'status'], 'pvp_patient_status_idx');
            $table->index(['doctor_id', 'status'], 'pvp_doctor_status_idx');
        });

        Schema::table('patient_vaccinations', function (Blueprint $table) {
            $table->foreign('patient_vaccination_program_id', 'pv_program_fk')
                ->references('id')
                ->on('patient_vaccination_programs')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('patient_vaccinations', function (Blueprint $table) {
            $table->dropForeign('pv_program_fk');
        });

        Schema::dropIfExists('patient_vaccination_programs');
    }
};
