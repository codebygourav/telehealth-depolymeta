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
        Schema::create('fake_reviewers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->integer('age');
            $table->text('address')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Add foreign key constraint to doctor_reviews table
        // This is done here because fake_reviewers table must exist first
        if (Schema::hasTable('doctor_reviews') && Schema::hasColumn('doctor_reviews', 'faker_patient_id')) {
            Schema::table('doctor_reviews', function (Blueprint $table) {
                try {
                    $table->foreign('faker_patient_id')
                        ->references('id')
                        ->on('fake_reviewers')
                        ->nullOnDelete();
                } catch (\Exception $e) {
                    // Foreign key might already exist (e.g., if migration was run before)
                    // This is safe to ignore
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key first if it exists
        if (Schema::hasTable('doctor_reviews')) {
            Schema::table('doctor_reviews', function (Blueprint $table) {
                try {
                    $table->dropForeign(['faker_patient_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist, ignore
                }
            });
        }

        Schema::dropIfExists('fake_reviewers');
    }
};
