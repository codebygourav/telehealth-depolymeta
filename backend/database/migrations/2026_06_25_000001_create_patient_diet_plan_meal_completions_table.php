<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_diet_plan_meal_completions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_diet_plan_meal_id');
            $table->date('occurrence_date');
            $table->enum('status', ['completed', 'missed', 'skipped'])->default('completed');
            $table->string('completed_by_role', 30)->nullable();
            $table->string('completed_by_name')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('patient_diet_plan_meal_id', 'pdpm_completion_meal_fk')
                ->references('id')
                ->on('patient_diet_plan_meals')
                ->cascadeOnDelete();
            $table->unique(['patient_diet_plan_meal_id', 'occurrence_date'], 'pdpm_completion_unique');
            $table->index(['occurrence_date', 'status'], 'pdpm_completion_date_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_diet_plan_meal_completions');
    }
};
