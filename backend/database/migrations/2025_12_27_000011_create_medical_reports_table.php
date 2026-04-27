<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\MedicalReportStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('appointment_id')->nullable();
            $table->uuid('patient_id');
            $table->uuid('doctor_id')->nullable();
            $table->string('name'); // e.g., "Blood Test Results", "X-Ray Analysis"
            $table->string('type'); // lab_report, radiology, prescription, other
            $table->text('description')->nullable();
            $table->date('report_date');
            $table->string('file_path')->nullable(); // Path to uploaded file
            $table->string('file_name')->nullable();
            $table->string('file_type')->nullable(); // pdf, image, etc.
            $table->boolean('is_shared')->default(false);
            $table->boolean('is_public')->default(false);
            $table->json('results')->nullable(); // Store test results as JSON
            $table->text('notes')->nullable();
            $table->string('status')->default(MedicalReportStatus::default()); // active, archived, reviewed
            $table->uuid('created_by')->nullable()->index();
            $table->uuid('updated_by')->nullable()->index();
            $table->uuid('deleted_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('set null');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_reports');
    }
};
