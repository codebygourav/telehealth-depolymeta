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
        Schema::create('vaccination_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('patient_vaccination_id');

            $table->string('document');

            $table->string('certificate_number')->nullable();

            $table->timestamps();

            $table->foreign('patient_vaccination_id')->references('id')->on('patient_vaccinations')->cascadeOnDelete();
            $table->index('certificate_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vaccination_documents');
    }
};
