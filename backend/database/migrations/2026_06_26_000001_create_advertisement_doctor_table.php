<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advertisement_doctor', function (Blueprint $table) {
            $table->uuid('advertisement_id');
            $table->uuid('doctor_id');
            $table->timestamps();

            $table->primary(['advertisement_id', 'doctor_id'], 'advertisement_doctor_primary');

            $table->foreign('advertisement_id')
                ->references('id')
                ->on('advertisements')
                ->cascadeOnDelete();

            $table->foreign('doctor_id')
                ->references('id')
                ->on('doctors')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advertisement_doctor');
    }
};
