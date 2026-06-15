<?php

use App\Enums\BloodGroupOption;
use App\Enums\GenderOption;
use App\Enums\MaritalStatus;
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
        Schema::create('patients', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // If each patient belongs to a user in your system
            $table->uuid('user_id')->unique()->nullable();
            // Patient Info
            $table->string('slug')->nullable();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->enum('gender', array_map(fn($case) => $case->value, GenderOption::cases()))->nullable();
            $table->date('date_of_birth')->nullable();
            $table->integer('age')->nullable();
            $table->text('bio')->nullable();

            // Family
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();

            // Contact
            $table->string('mobile_no', 20);
            $table->string('alternate_no', 20)->nullable();
            $table->string('email')->nullable();

            // Address
            $table->string('address')->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('area')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('landmark')->nullable();
            $table->string('nationality')->nullable();
            // Other info
            $table->enum('marital_status', array_map(fn($case) => $case->value, MaritalStatus::cases()))->nullable();

            $table->enum('blood_group', array_column(BloodGroupOption::cases(), 'value'))->nullable();
            // Existing / Manual Patient
            $table->boolean('is_existing_patient')->default(false);
            $table->string('existing_patient_id')->nullable();
            $table->boolean('create_user_account')->default(false);

            // Source tracking (important for mobile/web)
            $table->enum('source', ['app', 'website', 'internal'])->default('website');

            $table->uuid('created_by')->nullable()->index();
            $table->uuid('updated_by')->nullable()->index();
            $table->uuid('deleted_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            // Performance indexes for API queries
            $table->index('user_id');
            $table->index('source');
            $table->index('create_user_account');
            $table->index(['source', 'create_user_account']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
