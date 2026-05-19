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
        Schema::table('patient_diet_plan_meals', function (Blueprint $table) {

            $table->renameColumn(
                'patient_notes',
                'notes'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_diet_plan_meals', function (Blueprint $table) {

            $table->renameColumn(
                'notes',
                'patient_notes'
            );
        });
    }
};