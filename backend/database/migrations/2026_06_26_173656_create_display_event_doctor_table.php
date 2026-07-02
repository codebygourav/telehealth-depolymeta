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
        Schema::create('display_event_doctor', function (Blueprint $table) {
            $table->uuid('display_event_id');
            $table->uuid('doctor_id');
            $table->timestamps();

            $table->primary(['display_event_id', 'doctor_id'], 'display_event_doctor_primary');

            $table->foreign('display_event_id')
                ->references('id')
                ->on('display_events')
                ->cascadeOnDelete();

            $table->foreign('doctor_id')
                ->references('id')
                ->on('doctors')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('display_event_doctor');
    }
};
