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
        Schema::table('medicine_template_items', function (Blueprint $table) {
            $table->string('use_type')->default('regular')->after('dosage');
            $table->string('take_when')->nullable()->after('use_type');
            $table->string('min_gap')->nullable()->after('take_when');
            $table->string('max_doses_per_day')->nullable()->after('min_gap');
            $table->text('patient_instruction')->nullable()->after('max_doses_per_day');
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->string('use_type')->default('regular')->after('dosage');
            $table->string('take_when')->nullable()->after('use_type');
            $table->string('min_gap')->nullable()->after('take_when');
            $table->string('max_doses_per_day')->nullable()->after('min_gap');
            $table->text('patient_instruction')->nullable()->after('max_doses_per_day');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medicine_template_items', function (Blueprint $table) {
            $table->dropColumn(['use_type', 'take_when', 'min_gap', 'max_doses_per_day', 'patient_instruction']);
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn(['use_type', 'take_when', 'min_gap', 'max_doses_per_day', 'patient_instruction']);
        });
    }
};
