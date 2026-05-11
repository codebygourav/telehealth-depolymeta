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
        Schema::create('vaccination_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('doctor_id');

            $table->string('name');

            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('doctor_id')->references('id')->on('doctors')->cascadeOnDelete();
            $table->index(['doctor_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vaccination_templates');
    }
};
