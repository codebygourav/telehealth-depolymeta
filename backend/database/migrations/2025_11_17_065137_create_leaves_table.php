<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaves', function (Blueprint $table) {
            $table->uuid('id')->primary(); // UUID
            $table->uuid('user_id'); // Who applied for leave (can be doctor, patient, staff, etc.)
            $table->date('start_date');
            $table->date('end_date');
            $table->string('type'); // e.g., sick, casual, annual
            $table->string('slug')->unique();
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancel'])->default('pending');
            $table->text('status_comment')->nullable();

            $table->timestamps();
            $table->softDeletes(); // Soft delete
            $table->uuid('created_by')->nullable()->index();
            $table->uuid('updated_by')->nullable()->index();
            $table->uuid('deleted_by')->nullable()->index();

            // Foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
