<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('availabilities', function (Blueprint $table) {
            $table->dropUnique('doctor_date_time_unique');
            $table->unique(
                ['doctor_id', 'date', 'start_time', 'end_time', 'consultation_type'],
                'doctor_date_time_consultation_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('availabilities', function (Blueprint $table) {
            $table->dropUnique('doctor_date_time_consultation_unique');
            $table->unique(['doctor_id', 'date', 'start_time', 'end_time'], 'doctor_date_time_unique');
        });
    }
};
