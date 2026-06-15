<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('external_bookings', 'opd_type')) {
                $table->string('opd_type')->default('private')->after('consultation_type');
            }

            $table->index(['doctor_id', 'appointment_date', 'start_time', 'consultation_type', 'opd_type'], 'external_booking_slot_type_idx');
        });
    }

    public function down(): void
    {
        Schema::table('external_bookings', function (Blueprint $table) {
            $table->dropIndex('external_booking_slot_type_idx');

            if (Schema::hasColumn('external_bookings', 'opd_type')) {
                $table->dropColumn('opd_type');
            }
        });
    }
};
