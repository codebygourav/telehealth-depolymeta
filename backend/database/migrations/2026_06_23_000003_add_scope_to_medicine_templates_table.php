<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicine_templates', function (Blueprint $table) {
            $table->string('scope_type')->default('global')->after('id');
            $table->uuid('department_id')->nullable()->after('scope_type');

            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            $table->index(['scope_type', 'department_id', 'doctor_id'], 'medicine_templates_scope_idx');
        });

        DB::table('medicine_templates')
            ->whereNotNull('doctor_id')
            ->update(['scope_type' => 'doctor']);
    }

    public function down(): void
    {
        Schema::table('medicine_templates', function (Blueprint $table) {
            $table->dropIndex('medicine_templates_scope_idx');
            $table->dropForeign(['department_id']);
            $table->dropColumn(['scope_type', 'department_id']);
        });
    }
};
