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
        // 1. Alter patient_vaccinations status to a string column and add new date/gap columns
        Schema::table('patient_vaccinations', function (Blueprint $table) {
            // Change enum to string
            $table->string('status', 50)->default('upcoming')->change();

            // Add planning dates and rules
            $table->date('expected_date')->nullable()->after('dose_no');
            $table->date('assigned_date')->nullable()->after('expected_date');
            $table->date('due_date')->nullable()->after('assigned_date');
            $table->date('changed_date')->nullable()->after('due_date');
            $table->date('missed_date')->nullable()->after('changed_date');
            $table->date('overdue_date')->nullable()->after('missed_date');

            $table->integer('grace_period_before_days')->default(0)->after('overdue_date');
            $table->integer('grace_period_after_days')->default(0)->after('grace_period_before_days');

            $table->string('skipped_reason')->nullable()->after('doctor_notes');
            $table->string('on_hold_reason')->nullable()->after('skipped_reason');
        });

        // 2. Add grace periods to vaccination_template_items
        Schema::table('vaccination_template_items', function (Blueprint $table) {
            $table->integer('grace_period_before_days')->default(0)->after('maximum_age_days');
            $table->integer('grace_period_after_days')->default(0)->after('grace_period_before_days');
            $table->string('timing_type', 50)->default('base_date')->after('depends_on_previous_dose');
            $table->integer('offset_value')->default(0)->after('due_after_months');
            $table->string('offset_unit', 20)->default('days')->after('offset_value');
            $table->integer('interval_value')->default(0)->after('interval_months');
            $table->string('interval_unit', 20)->default('days')->after('interval_value');
            $table->boolean('doctor_manual_date')->default(false)->after('interval_unit');
        });

        // 3. Create the patient_vaccination_logs table
        Schema::create('patient_vaccination_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_vaccination_id');
            $table->uuid('performed_by_id')->nullable();
            $table->string('action');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->foreign('patient_vaccination_id')
                ->references('id')
                ->on('patient_vaccinations')
                ->cascadeOnDelete();

            $table->foreign('performed_by_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index('patient_vaccination_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_vaccination_logs');

        Schema::table('vaccination_template_items', function (Blueprint $table) {
            $table->dropColumn([
                'grace_period_before_days',
                'grace_period_after_days',
                'timing_type',
                'offset_value',
                'offset_unit',
                'interval_value',
                'interval_unit',
                'doctor_manual_date',
            ]);
        });

        Schema::table('patient_vaccinations', function (Blueprint $table) {
            $table->dropColumn([
                'expected_date',
                'assigned_date',
                'due_date',
                'changed_date',
                'missed_date',
                'overdue_date',
                'grace_period_before_days',
                'grace_period_after_days',
                'skipped_reason',
                'on_hold_reason',
            ]);
            // Revert status to enum if possible (note: SQLite might not support reverting string back to enum easily)
            // To ensure compatibility, we'll keep it as string in down or drop if table is refreshed
        });
    }
};
