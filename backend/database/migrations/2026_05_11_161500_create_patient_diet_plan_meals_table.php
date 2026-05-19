<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_diet_plan_meals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_diet_plan_day_id');
            $table->string('meal_type', 30);
            $table->string('meal_name');
            $table->text('instructions')->nullable();
            $table->unsignedInteger('calories')->nullable();
            $table->unsignedInteger('protein_grams')->nullable();
            $table->unsignedInteger('carbs_grams')->nullable();
            $table->unsignedInteger('fat_grams')->nullable();
            $table->time('meal_time')->nullable();
            $table->enum('status', ['pending', 'completed', 'missed', 'skipped'])->default('pending');
            $table->text('patient_notes')->nullable(); //Using a anotehr migratiion this field update patient_notes to notes only
            $table->dateTime('completed_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('patient_diet_plan_day_id')->references('id')->on('patient_diet_plan_days')->cascadeOnDelete();
            $table->index(['patient_diet_plan_day_id', 'status'], 'pdpm_day_status_idx');
            $table->index(['patient_diet_plan_day_id', 'sort_order'], 'pdpm_day_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_diet_plan_meals');
    }
};
