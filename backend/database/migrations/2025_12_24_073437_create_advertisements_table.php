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
        Schema::create('advertisements', function (Blueprint $table) {
            $table->uuid('id')->primary(); // UUID instead of auto-increment
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('link')->nullable();
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by')->nullable()->index();
            $table->uuid('updated_by')->nullable()->index();
            $table->uuid('deleted_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            // Performance indexes for API queries
            $table->index('is_active');
            $table->index('created_at');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};
