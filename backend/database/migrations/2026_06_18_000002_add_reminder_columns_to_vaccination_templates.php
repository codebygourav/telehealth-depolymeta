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
        Schema::table('vaccination_templates', function (Blueprint $table) {
            $table->unsignedSmallInteger('reminder_1_days_before')->default(7)->after('is_active');
            $table->unsignedSmallInteger('reminder_2_days_before')->default(3)->after('reminder_1_days_before');
            $table->unsignedSmallInteger('reminder_3_days_before')->default(1)->after('reminder_2_days_before');
            $table->unsignedSmallInteger('overdue_alert_days_after')->default(1)->after('reminder_3_days_before');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vaccination_templates', function (Blueprint $table) {
            $table->dropColumn([
                'reminder_1_days_before',
                'reminder_2_days_before',
                'reminder_3_days_before',
                'overdue_alert_days_after',
            ]);
        });
    }
};
