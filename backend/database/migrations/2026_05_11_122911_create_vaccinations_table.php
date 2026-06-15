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
        Schema::create('vaccinations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name');

            $table->string('short_name')->nullable();

            $table->string('disease_for')->nullable();

            $table->text('description')->nullable();

            $table->text('side_effects')->nullable();

            $table->text('contraindications')->nullable();

            $table->text('precautions')->nullable();

            $table->text('dosage_information')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vaccinations');
    }
};
