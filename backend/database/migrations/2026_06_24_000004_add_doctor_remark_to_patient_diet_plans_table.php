<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diet_templates', function (Blueprint $table) {
            $table->string('diet_category')->nullable()->after('name');
            $table->string('patient_type')->nullable()->after('diet_category');
            $table->string('daily_calories')->nullable()->after('patient_type');
            $table->string('protein_target')->nullable()->after('daily_calories');
            $table->string('carbs_limit')->nullable()->after('protein_target');
            $table->string('salt_limit')->nullable()->after('carbs_limit');
            $table->text('doctor_remark')->nullable()->after('salt_limit');
            $table->text('allowed_food_notes')->nullable()->after('doctor_remark');
            $table->text('hydration_advice')->nullable()->after('allowed_food_notes');
            $table->text('exercise_advice')->nullable()->after('hydration_advice');
            $table->json('features')->nullable()->after('exercise_advice');
        });

        Schema::table('patient_diet_plans', function (Blueprint $table) {
            $table->string('diet_category')->nullable()->after('template_description');
            $table->string('patient_type')->nullable()->after('diet_category');
            $table->string('daily_calories')->nullable()->after('patient_type');
            $table->string('protein_target')->nullable()->after('daily_calories');
            $table->string('carbs_limit')->nullable()->after('protein_target');
            $table->string('salt_limit')->nullable()->after('carbs_limit');
            $table->text('doctor_remark')->nullable()->after('salt_limit');
            $table->text('allowed_food_notes')->nullable()->after('doctor_remark');
            $table->text('hydration_advice')->nullable()->after('allowed_food_notes');
            $table->text('exercise_advice')->nullable()->after('hydration_advice');
            $table->json('features')->nullable()->after('exercise_advice');
        });
    }

    public function down(): void
    {
        Schema::table('diet_templates', function (Blueprint $table) {
            $table->dropColumn([
                'diet_category',
                'patient_type',
                'daily_calories',
                'protein_target',
                'carbs_limit',
                'salt_limit',
                'doctor_remark',
                'allowed_food_notes',
                'hydration_advice',
                'exercise_advice',
                'features'
            ]);
        });

        Schema::table('patient_diet_plans', function (Blueprint $table) {
            $table->dropColumn([
                'diet_category',
                'patient_type',
                'daily_calories',
                'protein_target',
                'carbs_limit',
                'salt_limit',
                'doctor_remark',
                'allowed_food_notes',
                'hydration_advice',
                'exercise_advice',
                'features'
            ]);
        });
    }
};
