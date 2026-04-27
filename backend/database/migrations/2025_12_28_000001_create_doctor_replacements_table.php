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
        Schema::create('doctor_replacements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('original_doctor_id');
            $table->uuid('replacement_doctor_id');
            $table->uuid('replaced_by')->nullable()->comment('Admin user who made the replacement');

            $table->enum('replacement_type', [
                'single',      // Replace for one specific appointment/schedule
                'selected',    // Replace for selected schedules
                'all',         // Replace for all schedules
                'permanent'    // Permanent replacement
            ])->default('single');

            $table->date('start_date')->nullable()->comment('Start date for replacement period');
            $table->date('end_date')->nullable()->comment('End date for replacement period');
            $table->string('reason')->nullable()->comment('Reason for replacement (leave, unavailable, etc.)');
            $table->text('notes')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('original_doctor_id')->references('id')->on('doctors')->cascadeOnDelete();
            $table->foreign('replacement_doctor_id')->references('id')->on('doctors')->cascadeOnDelete();
            $table->foreign('replaced_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['original_doctor_id', 'is_active']);
            $table->index(['replacement_doctor_id', 'is_active']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_replacements');
    }
};
