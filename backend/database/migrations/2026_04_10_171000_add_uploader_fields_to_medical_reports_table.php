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
        Schema::table('medical_reports', function (Blueprint $table) {
            $table->uuid('uploader_id')->nullable()->after('doctor_id');
            $table->string('uploader_type')->nullable()->after('uploader_id');
            $table->index(['uploader_id', 'uploader_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medical_reports', function (Blueprint $table) {
            $table->dropIndex(['uploader_id', 'uploader_type']);
            $table->dropColumn(['uploader_id', 'uploader_type']);
        });
    }
};
