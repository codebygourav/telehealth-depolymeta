<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('availabilities', function (Blueprint $table) {
            $table->json('booking_cutoff_rules')
                ->nullable()
                ->after('blocked_dates')
                ->comment('Lead-time rules: block booking within X before slot start');
        });
    }

    public function down(): void
    {
        Schema::table('availabilities', function (Blueprint $table) {
            $table->dropColumn('booking_cutoff_rules');
        });
    }
};
