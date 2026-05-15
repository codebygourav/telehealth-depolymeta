<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vaccination_faqs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vaccination_id')->constrained('vaccinations')->cascadeOnDelete();
            $table->string('question');
            $table->text('answer');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['vaccination_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vaccination_faqs');
    }
};
