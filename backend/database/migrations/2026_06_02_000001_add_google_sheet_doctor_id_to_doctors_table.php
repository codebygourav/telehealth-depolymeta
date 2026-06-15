<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->string('google_sheet_doctor_id')->nullable()->after('doctor_code');
            $table->index('google_sheet_doctor_id');
        });
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropIndex(['google_sheet_doctor_id']);
            $table->dropColumn('google_sheet_doctor_id');
        });
    }
};
