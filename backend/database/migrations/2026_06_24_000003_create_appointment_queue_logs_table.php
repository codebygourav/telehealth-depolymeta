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
        Schema::create('appointment_queue_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('doctor_id');
            $table->uuid('appointment_id')->nullable();
            $table->string('action'); // check_in, check_out, break_start, break_end, start, complete, not_complete, skip, revert
            $table->string('queue_status')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('doctor_id')->references('id')->on('doctors')->cascadeOnDelete();
            $table->foreign('appointment_id')->references('id')->on('appointments')->cascadeOnDelete();
            
            $table->index('doctor_id');
            $table->index('appointment_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_queue_logs');
    }
};
