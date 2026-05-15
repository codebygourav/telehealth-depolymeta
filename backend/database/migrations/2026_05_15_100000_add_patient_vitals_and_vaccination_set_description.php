<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->decimal('weight', 8, 2)->nullable()->after('blood_group');
            $table->decimal('height', 8, 2)->nullable()->after('weight');
        });

        Schema::table('patient_vaccinations', function (Blueprint $table) {
            $table->text('set_description')->nullable()->after('set_name');
        });
    }

    public function down(): void
    {
        Schema::table('patient_vaccinations', function (Blueprint $table) {
            $table->dropColumn('set_description');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn(['weight', 'height']);
        });
    }
};
