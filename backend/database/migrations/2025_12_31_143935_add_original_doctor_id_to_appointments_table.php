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
        Schema::table('appointments', function (Blueprint $table) {
            $table->uuid('replaced_by_id')->nullable()->after('doctor_id')->comment('Doctor ID who replaced the original doctor');
            $table->foreign('replaced_by_id')->references('id')->on('doctors')->nullOnDelete();
            $table->index('replaced_by_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['replaced_by_id']);
            $table->dropIndex(['replaced_by_id']);
            $table->dropColumn('replaced_by_id');
        });
    }
};
