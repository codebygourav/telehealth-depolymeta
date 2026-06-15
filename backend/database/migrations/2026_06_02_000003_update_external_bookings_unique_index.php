<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_bookings', function (Blueprint $table) {
            $table->dropUnique('external_booking_source_unique');
            $table->unique(['doctor_id', 'source', 'source_row_id'], 'external_booking_source_unique');
        });
    }

    public function down(): void
    {
        Schema::table('external_bookings', function (Blueprint $table) {
            $table->dropUnique('external_booking_source_unique');
            $table->unique(['doctor_id', 'source', 'source_row_id', 'appointment_date', 'start_time'], 'external_booking_source_unique');
        });
    }
};
