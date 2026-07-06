<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cron_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_enabled')->default(true);
            $table->string('memory_limit')->default('512M');
            $table->boolean('queue_enabled')->default(true);
            $table->boolean('appointments_reminder_enabled')->default(true);
            $table->boolean('prescriptions_reminder_enabled')->default(true);
            $table->boolean('vaccinations_reminder_enabled')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->string('last_run_status')->nullable();
            $table->text('last_run_output')->nullable();
            $table->timestamps();
        });

        // Seed default row
        DB::table('cron_settings')->insert([
            'id' => 1,
            'is_enabled' => true,
            'memory_limit' => '512M',
            'queue_enabled' => true,
            'appointments_reminder_enabled' => true,
            'prescriptions_reminder_enabled' => true,
            'vaccinations_reminder_enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('cron_settings');
    }
};
