<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additional patient profile fields collected at app/website registration completion.
     */
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->text('allergies')->nullable()->after('blood_group');
            $table->text('existing_conditions')->nullable()->after('allergies');
            $table->text('current_medications')->nullable()->after('existing_conditions');
            $table->text('past_medical_history')->nullable()->after('current_medications');

            $table->string('emergency_contact_name')->nullable()->after('past_medical_history');
            $table->string('emergency_contact_relationship')->nullable()->after('emergency_contact_name');
            $table->string('emergency_contact_phone', 32)->nullable()->after('emergency_contact_relationship');

            $table->string('insurance_provider')->nullable()->after('emergency_contact_phone');
            $table->string('insurance_policy_number')->nullable()->after('insurance_provider');
            $table->date('insurance_policy_expiry')->nullable()->after('insurance_policy_number');
            $table->string('insurance_tpa_details')->nullable()->after('insurance_policy_expiry');
            $table->boolean('treatment_consent_accepted')->nullable()->after('insurance_tpa_details');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'allergies',
                'existing_conditions',
                'current_medications',
                'past_medical_history',
                'emergency_contact_name',
                'emergency_contact_relationship',
                'emergency_contact_phone',
                'insurance_provider',
                'insurance_policy_number',
                'insurance_policy_expiry',
                'insurance_tpa_details',
                'treatment_consent_accepted',
            ]);
        });
    }
};
