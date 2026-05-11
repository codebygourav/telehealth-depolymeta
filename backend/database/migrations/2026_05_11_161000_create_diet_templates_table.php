<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diet_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('doctor_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('duration_days')->default(7);
            $table->text('restrictions')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('doctor_id')->references('id')->on('doctors')->cascadeOnDelete();
            $table->index(['doctor_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diet_templates');
    }
};
