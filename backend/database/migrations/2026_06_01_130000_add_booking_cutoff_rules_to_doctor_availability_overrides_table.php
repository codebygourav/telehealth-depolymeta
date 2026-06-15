<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_availability_overrides', function (Blueprint $table) {
            $table->json('booking_cutoff_rules')
                ->nullable()
                ->after('note')
                ->comment('Date-specific cut-off rules; null inherits from parent availability');
        });
    }

    public function down(): void
    {
        Schema::table('doctor_availability_overrides', function (Blueprint $table) {
            $table->dropColumn('booking_cutoff_rules');
        });
    }
};
