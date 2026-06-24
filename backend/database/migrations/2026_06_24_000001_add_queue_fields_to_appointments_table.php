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
        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'queue_number')) {
                $table->string('queue_number')->nullable()->change();
            } else {
                $table->string('queue_number')->nullable()->after('status');
            }

            if (!Schema::hasColumn('appointments', 'queue_status')) {
                $table->string('queue_status')->default('waiting')->after('status');
                $table->index('queue_status');
            }

            $table->unique(['doctor_id', 'appointment_date', 'queue_number'], 'appointments_doctor_date_queue_unique');
        });
    }

    public function down(): void
    {
        try {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropUnique('appointments_doctor_date_queue_unique');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropIndex(['queue_status']);
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropColumn(['queue_status']);
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('appointments', function (Blueprint $table) {
                $table->integer('queue_number')->unsigned()->nullable()->change();
            });
        } catch (\Exception $e) {}
    }
};
