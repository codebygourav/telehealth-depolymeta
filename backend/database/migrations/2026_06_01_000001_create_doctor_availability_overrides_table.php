<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_availability_overrides', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('doctor_availability_id');
            $table->uuid('doctor_id');
            $table->date('override_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->decimal('consultation_fee', 10, 2)->nullable();
            $table->string('doctor_room')->nullable();
            $table->enum('status', ['active', 'blocked', 'cancelled'])->default('active');
            $table->text('note')->nullable();
            $table->uuid('created_by')->nullable()->index();
            $table->uuid('updated_by')->nullable()->index();
            $table->uuid('deleted_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('doctor_availability_id')->references('id')->on('availabilities')->cascadeOnDelete();
            $table->foreign('doctor_id')->references('id')->on('doctors')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();

            $table->unique(['doctor_availability_id', 'override_date'], 'dao_availability_date_unique');
            $table->index(['doctor_id', 'override_date'], 'dao_doctor_date_idx');
            $table->index(['doctor_availability_id', 'status'], 'dao_availability_status_idx');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->uuid('availability_override_id')->nullable()->after('availability_id');
            $table->foreign('availability_override_id')
                ->references('id')
                ->on('doctor_availability_overrides')
                ->nullOnDelete();
            $table->index('availability_override_id');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['availability_override_id']);
            $table->dropIndex(['availability_override_id']);
            $table->dropColumn('availability_override_id');
        });

        Schema::dropIfExists('doctor_availability_overrides');
    }
};
