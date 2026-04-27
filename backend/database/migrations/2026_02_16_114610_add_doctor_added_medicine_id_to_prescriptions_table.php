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
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->uuid('doctor_added_medicine_id')->nullable()->after('medicine_id');
            $table->foreign('doctor_added_medicine_id')
                ->references('id')
                ->on('doctor_added_medicines')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropForeign(['doctor_added_medicine_id']);
            $table->dropColumn('doctor_added_medicine_id');
        });
    }
};
