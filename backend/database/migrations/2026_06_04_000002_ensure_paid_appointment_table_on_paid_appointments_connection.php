<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('paid_appointments')->hasTable('paid_appointment')) {
            Schema::connection('paid_appointments')->create('paid_appointment', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('doctor_id');
                $table->string('source_row_id')->nullable();
                $table->string('doctor_name')->nullable();
                $table->string('patient_name')->nullable();
                $table->string('patient_unit_number')->nullable();
                $table->string('patient_email')->nullable();
                $table->string('mobile', 30)->nullable();
                $table->date('appointment_date');
                $table->time('start_time');
                $table->time('end_time')->nullable();
                $table->string('consultation_type')->default('in-person');
                $table->string('opd_type')->default('private');
                $table->string('track_upload_status')->nullable();
                $table->string('stack_upload_status')->nullable();
                $table->timestamp('source_created_at')->nullable();
                $table->string('payment_id')->nullable();
                $table->timestamps();

                $table->unique(['doctor_id', 'source_row_id'], 'paid_appointment_source_unique');
                $table->index(['doctor_id', 'appointment_date', 'start_time'], 'paid_appointment_slot_idx');
                $table->index('payment_id');
            });

            return;
        }

        $this->ensureColumns();
    }

    public function down(): void
    {
        Schema::connection('paid_appointments')->dropIfExists('paid_appointment');
    }

    private function ensureColumns(): void
    {
        $columns = [
            'doctor_id' => fn (Blueprint $table) => $table->uuid('doctor_id')->nullable(),
            'source_row_id' => fn (Blueprint $table) => $table->string('source_row_id')->nullable(),
            'doctor_name' => fn (Blueprint $table) => $table->string('doctor_name')->nullable(),
            'patient_name' => fn (Blueprint $table) => $table->string('patient_name')->nullable(),
            'patient_unit_number' => fn (Blueprint $table) => $table->string('patient_unit_number')->nullable(),
            'patient_email' => fn (Blueprint $table) => $table->string('patient_email')->nullable(),
            'mobile' => fn (Blueprint $table) => $table->string('mobile', 30)->nullable(),
            'appointment_date' => fn (Blueprint $table) => $table->date('appointment_date')->nullable(),
            'start_time' => fn (Blueprint $table) => $table->time('start_time')->nullable(),
            'end_time' => fn (Blueprint $table) => $table->time('end_time')->nullable(),
            'consultation_type' => fn (Blueprint $table) => $table->string('consultation_type')->default('in-person'),
            'opd_type' => fn (Blueprint $table) => $table->string('opd_type')->default('private'),
            'track_upload_status' => fn (Blueprint $table) => $table->string('track_upload_status')->nullable(),
            'stack_upload_status' => fn (Blueprint $table) => $table->string('stack_upload_status')->nullable(),
            'source_created_at' => fn (Blueprint $table) => $table->timestamp('source_created_at')->nullable(),
            'payment_id' => fn (Blueprint $table) => $table->string('payment_id')->nullable(),
        ];

        foreach ($columns as $column => $definition) {
            if (Schema::connection('paid_appointments')->hasColumn('paid_appointment', $column)) {
                continue;
            }

            Schema::connection('paid_appointments')->table('paid_appointment', function (Blueprint $table) use ($definition): void {
                $definition($table);
            });
        }
    }
};
