<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescription_drafts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('appointment_id');
            $table->uuid('doctor_id');
            $table->uuid('patient_id');
            $table->string('source_type', 32)->default('text');
            $table->string('status', 32)->default('parsed');
            $table->longText('input_text');
            $table->json('parsed_payload')->nullable();
            $table->json('warnings')->nullable();
            $table->json('missing_fields')->nullable();
            $table->unsignedTinyInteger('confidence_score')->nullable();
            $table->json('submitted_payload')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
            $table->uuid('created_by')->nullable()->index();
            $table->uuid('updated_by')->nullable()->index();

            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('cascade');
            $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('cascade');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_drafts');
    }
};
