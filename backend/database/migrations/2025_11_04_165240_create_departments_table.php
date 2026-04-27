<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Department basic columns
            $table->string('code')->nullable();
            $table->string('name');
            $table->longText('description')->nullable();
            $table->json('additional_information')->nullable();
            $table->string('slug')->nullable();
            $table->json('faqs')->nullable();
            $table->json('publications')->nullable();
            $table->json('symptom_ids')->nullable();
            $table->boolean('is_tab_layout')->default(false);
            $table->string('department_featured')->nullable();
            $table->string('stamp')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};