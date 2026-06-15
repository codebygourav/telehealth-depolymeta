<?php

use App\Enums\DoctorStatus;
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
        Schema::create('doctors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->boolean('email_sent')->default(false);
            // 🔹 Relation
            $table->uuid('user_id')->unique(); // FK to users table
            $table->string('slug')->nullable();
            $table->string('doctor_code', 20)->unique()->nullable();
            // 🔹 Personal Information
            $table->string('first_name');
            $table->string('last_name');
            $table->date('dob')->nullable();
            $table->enum('gender', array_map(fn($case) => $case->value, GenderOption::cases()))->nullable();
            $table->enum('marital_status', array_map(fn($case) => $case->value, MaritalStatus::cases()))->nullable();
            $table->string('blood_group')->nullable();
            $table->json('social_links')->nullable();

            // 🔹 Professional Info
            $table->integer('years_experience')->default(0);
            $table->string('medical_license_number')->unique()->nullable();

            // 🔹 Contact & Address Info
            $table->text('address_line1')->nullable();
            $table->text('address_line2')->nullable();
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('area')->nullable();
            $table->string('city')->nullable();
            $table->string('pincode')->nullable();
            $table->string('landmark')->nullable();
            $table->string('specializations_info')->nullable();
            $table->string('key_procedures_info')->nullable();
            $table->string('expertise_info')->nullable();

            $table->string('signature')->nullable(); // 🔹 Phone
            // 🔹 About Doctor
            $table->text('bio')->nullable();
            $table->longText('description')->nullable();
            $table->json('languages_known')->nullable(); // ✅ FIXED — JSON, not ENUM

            // 🔹 Repeater Fields (stored as JSON)
            $table->json('education_info')->nullable(); // degree, university, from, to
            $table->json('awards_info')->nullable(); // name, from
            $table->json('professional_experience_info')->nullable(); // name, from
            $table->json('certifications_info')->nullable(); // name, from
            $table->json('fellowships_info')->nullable(); // title, institution, from, to

            // 🔹 Additional Information (HTML from rich text editor)
            $table->longText('special_interests')->nullable();
            $table->longText('availability_info')->nullable();
            $table->longText('memberships_info')->nullable();

            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();

            // 🔹 Verification & Status
            $table->enum('status', array_map(fn($case) => $case->value, DoctorStatus::cases()))->default(DoctorStatus::default());

            $table->timestamps();
            $table->softDeletes();

            // 🔹 Foreign Keys & Indexes
            $table->index('user_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
