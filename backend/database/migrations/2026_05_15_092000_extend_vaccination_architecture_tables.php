<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {


        Schema::table('vaccination_templates', function (Blueprint $table) {
            $table->uuid('vaccination_program_id')->nullable()->after('id');
            $table->foreign('vaccination_program_id')->references('id')->on('vaccination_programs')->nullOnDelete();
            $table->index(['vaccination_program_id', 'is_active'], 'vt_program_active_idx');
        });

        Schema::table('vaccination_template_items', function (Blueprint $table) {
            $table->boolean('depends_on_previous_dose')->default(false)->after('dose_no');
            $table->integer('interval_days')->default(0)->after('depends_on_previous_dose');
            $table->integer('interval_months')->default(0)->after('interval_days');
            $table->integer('minimum_age_days')->nullable()->after('interval_months');
            $table->integer('maximum_age_days')->nullable()->after('minimum_age_days');
        });

        Schema::table('patient_vaccinations', function (Blueprint $table) {
            $table->uuid('patient_vaccination_program_id')->nullable()->after('vaccination_template_id');
            $table->string('route')->nullable()->after('manufacturer');
            $table->string('site')->nullable()->after('route');
            $table->string('dose_amount')->nullable()->after('site');
            $table->timestamp('last_reminder_sent_at')->nullable()->after('reminder_sent');
            $table->integer('reminder_count')->default(0)->after('last_reminder_sent_at');
            $table->timestamp('next_reminder_at')->nullable()->after('reminder_count');

            $table->index(['next_reminder_at', 'status'], 'pv_next_reminder_status_idx');
        });

        Schema::table('vaccination_documents', function (Blueprint $table) {
            $table->string('document_type')->default('certificate')->after('document');
        });
    }

    public function down(): void
    {
        Schema::table('vaccination_documents', function (Blueprint $table) {
            $table->dropColumn('document_type');
        });

        Schema::table('patient_vaccinations', function (Blueprint $table) {
            $table->dropIndex('pv_next_reminder_status_idx');
            $table->dropColumn([
                'patient_vaccination_program_id',
                'route',
                'site',
                'dose_amount',
                'last_reminder_sent_at',
                'reminder_count',
                'next_reminder_at',
            ]);
        });

        Schema::table('vaccination_template_items', function (Blueprint $table) {
            $table->dropColumn([
                'depends_on_previous_dose',
                'interval_days',
                'interval_months',
                'minimum_age_days',
                'maximum_age_days',
            ]);
        });

        Schema::table('vaccination_templates', function (Blueprint $table) {
            $table->dropForeign(['vaccination_program_id']);
            $table->dropIndex('vt_program_active_idx');
            $table->dropColumn('vaccination_program_id');
        });
    }
};
