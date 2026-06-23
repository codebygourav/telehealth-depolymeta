<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicine_template_items', function (Blueprint $table) {
            $table->unsignedTinyInteger('doses_per_day')->default(1)->after('dosage');
            $table->time('first_dose_time')->nullable()->after('doses_per_day');
            $table->unsignedTinyInteger('dose_interval_hours')->default(8)->after('first_dose_time');
        });
    }

    public function down(): void
    {
        Schema::table('medicine_template_items', function (Blueprint $table) {
            $table->dropColumn(['doses_per_day', 'first_dose_time', 'dose_interval_hours']);
        });
    }
};
