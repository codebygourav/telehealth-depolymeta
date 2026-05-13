<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_diet_plan_days', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_diet_plan_id');
            $table->unsignedTinyInteger('day_number');
            $table->string('week_day', 20);
            $table->date('date');
            $table->timestamps();

            $table->foreign('patient_diet_plan_id')->references('id')->on('patient_diet_plans')->cascadeOnDelete();
            $table->index(['patient_diet_plan_id', 'date']);
            $table->unique(['patient_diet_plan_id', 'day_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_diet_plan_days');
    }
};
