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
        Schema::create('vaccination_template_items', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('vaccination_template_id');

            $table->uuid('vaccination_id');

            $table->integer('dose_no')->default(1);

            $table->integer('due_after_days')->nullable();

            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->foreign('vaccination_template_id')->references('id')->on('vaccination_templates')->cascadeOnDelete();
            $table->foreign('vaccination_id')->references('id')->on('vaccinations')->restrictOnDelete();
            $table->index(['vaccination_template_id', 'sort_order'], 'vti_tpl_sort_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vaccination_template_items');
    }
};
