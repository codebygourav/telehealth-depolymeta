<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure the parent table exists before creating the FK
        if (!Schema::hasTable('diet_templates')) {
            throw new \Exception('Table "diet_templates" must be created before "diet_template_days".');
        }

        Schema::create('diet_template_days', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('diet_template_id');
            $table->unsignedTinyInteger('day_number');
            $table->string('week_day', 20);
            $table->timestamps();

            // It's important that "diet_templates" table exists before this FK is added.
            $table->foreign('diet_template_id')->references('id')->on('diet_templates')->onDelete('cascade');
            $table->unique(['diet_template_id', 'day_number']);
            $table->index(['diet_template_id', 'week_day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diet_template_days');
    }
};
