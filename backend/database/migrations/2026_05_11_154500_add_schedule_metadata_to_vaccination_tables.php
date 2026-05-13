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
        Schema::table('vaccination_template_items', function (Blueprint $table) {
            $table->string('set_name')->nullable()->after('vaccination_id');
            $table->text('set_description')->nullable()->after('set_name');
            $table->integer('set_sort_order')->default(0)->after('set_description');
            $table->string('recommended_age_label')->nullable()->after('dose_no');
            $table->integer('due_after_months')->default(0)->after('due_after_days');
        });

        Schema::table('patient_vaccinations', function (Blueprint $table) {
            $table->date('first_dose_date')->nullable()->after('dose_no');
            $table->string('set_name')->nullable()->after('vaccination_template_id');
            $table->integer('set_sort_order')->default(0)->after('set_name');
            $table->string('recommended_age_label')->nullable()->after('set_sort_order');
            $table->integer('due_after_days')->default(0)->after('recommended_age_label');
            $table->integer('due_after_months')->default(0)->after('due_after_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_vaccinations', function (Blueprint $table) {
            $table->dropColumn([
                'first_dose_date',
                'set_name',
                'set_sort_order',
                'recommended_age_label',
                'due_after_days',
                'due_after_months',
            ]);
        });

        Schema::table('vaccination_template_items', function (Blueprint $table) {
            $table->dropColumn([
                'set_name',
                'set_description',
                'set_sort_order',
                'recommended_age_label',
                'due_after_months',
            ]);
        });
    }
};
