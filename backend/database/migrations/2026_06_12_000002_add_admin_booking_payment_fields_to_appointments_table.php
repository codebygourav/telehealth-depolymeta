<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('booking_source')
                ->default('patient')
                ->after('fee_amount')
                ->comment('patient, admin, wordpress, app, or other booking source');
            $table->string('admin_payment_type')
                ->nullable()
                ->after('booking_source')
                ->comment('with_payment or without_payment for admin-created bookings');
            $table->uuid('payment_waived_by')
                ->nullable()
                ->after('admin_payment_type');
            $table->timestamp('payment_waived_at')
                ->nullable()
                ->after('payment_waived_by');

            $table->index(['booking_source', 'admin_payment_type'], 'appointments_booking_source_payment_idx');
            $table->foreign('payment_waived_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['payment_waived_by']);
            $table->dropIndex('appointments_booking_source_payment_idx');
            $table->dropColumn([
                'booking_source',
                'admin_payment_type',
                'payment_waived_by',
                'payment_waived_at',
            ]);
        });
    }
};
