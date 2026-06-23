<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_template_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('medicine_template_id');
            $table->uuid('medicine_id')->nullable();
            $table->string('medicine_name');
            $table->string('medicine_type')->nullable();
            $table->string('dosage')->nullable();
            $table->string('frequency');
            $table->json('frequency_times')->nullable();
            $table->string('meal_timing')->nullable();
            $table->string('duration_type')->default('days');
            $table->integer('duration_value')->nullable();
            $table->text('instructions')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('medicine_template_id')->references('id')->on('medicine_templates')->cascadeOnDelete();
            $table->foreign('medicine_id')->references('id')->on('medicines')->nullOnDelete();
            $table->index(['medicine_template_id', 'sort_order'], 'mti_template_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_template_items');
    }
};
