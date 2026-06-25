<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diet_template_meals', function (Blueprint $table) {
            $table->longText('meal_image')->nullable()->after('instructions');
            $table->json('helpful_links')->nullable()->after('meal_image');
        });

        Schema::table('patient_diet_plan_meals', function (Blueprint $table) {
            $table->longText('meal_image')->nullable()->after('instructions');
            $table->json('helpful_links')->nullable()->after('meal_image');
        });
    }

    public function down(): void
    {
        Schema::table('diet_template_meals', function (Blueprint $table) {
            $table->dropColumn(['meal_image', 'helpful_links']);
        });

        Schema::table('patient_diet_plan_meals', function (Blueprint $table) {
            $table->dropColumn(['meal_image', 'helpful_links']);
        });
    }
};
