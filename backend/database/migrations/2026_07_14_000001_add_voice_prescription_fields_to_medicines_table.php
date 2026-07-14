<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->json('strength_options')->nullable()->after('description');
            $table->json('dosage_options')->nullable()->after('strength_options');
            $table->json('frequency_options')->nullable()->after('dosage_options');
            $table->json('timing_options')->nullable()->after('frequency_options');
            $table->json('meal_options')->nullable()->after('timing_options');
            $table->json('route_options')->nullable()->after('meal_options');
            $table->json('duration_options')->nullable()->after('route_options');
            $table->json('application_area_options')->nullable()->after('duration_options');
            $table->json('field_rules')->nullable()->after('application_area_options');
            $table->json('spoken_aliases')->nullable()->after('field_rules');
            $table->string('default_strength')->nullable()->after('spoken_aliases');
            $table->string('default_dosage')->nullable()->after('default_strength');
            $table->string('default_frequency')->nullable()->after('default_dosage');
            $table->string('default_timing')->nullable()->after('default_frequency');
            $table->string('default_meal')->nullable()->after('default_timing');
            $table->string('default_duration')->nullable()->after('default_meal');
            $table->string('default_route')->nullable()->after('default_duration');
            $table->text('default_instructions')->nullable()->after('default_route');
            $table->boolean('speech_enabled')->default(true)->after('default_instructions');
            $table->boolean('is_active')->default(true)->after('speech_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->dropColumn([
                'strength_options',
                'dosage_options',
                'frequency_options',
                'timing_options',
                'meal_options',
                'route_options',
                'duration_options',
                'application_area_options',
                'field_rules',
                'spoken_aliases',
                'default_strength',
                'default_dosage',
                'default_frequency',
                'default_timing',
                'default_meal',
                'default_duration',
                'default_route',
                'default_instructions',
                'speech_enabled',
                'is_active',
            ]);
        });
    }
};
