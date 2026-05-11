<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diet_template_meals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('diet_template_day_id');
            $table->string('meal_type', 30);
            $table->string('meal_name');
            $table->text('instructions')->nullable();
            $table->unsignedInteger('calories')->nullable();
            $table->unsignedInteger('protein_grams')->nullable();
            $table->unsignedInteger('carbs_grams')->nullable();
            $table->unsignedInteger('fat_grams')->nullable();
            $table->time('start_time')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('diet_template_day_id')->references('id')->on('diet_template_days')->cascadeOnDelete();
            $table->index(['diet_template_day_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diet_template_meals');
    }
};
