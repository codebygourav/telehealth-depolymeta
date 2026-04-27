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
        Schema::create('medicines', function (Blueprint $table) {
            $table->uuid('id')->primary();                  // UUID for medicine
            $table->string('name');
            $table->string('slug')->unique();

            $table->uuid('category_id');                    // UUID FK
            $table->uuid('type_id');                        // UUID FK

            $table->integer('hospital_stock')->default(0);
            $table->integer('quantity')->default(1);
            $table->string('batch_number')->nullable();
            $table->date('manufactured_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('manufacturer')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable()->index();
            $table->uuid('updated_by')->nullable()->index();
            $table->uuid('deleted_by')->nullable()->index();

            // Foreign keys
            $table->foreign('category_id')->references('id')->on('medicine_categories')->cascadeOnDelete();
            $table->foreign('type_id')->references('id')->on('medicine_types')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicines');
    }
};