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
        Schema::table('patients', function (Blueprint $table) {
            if (!Schema::hasColumn('patients', 'wife_name')) {
                $table->string('wife_name')->nullable()->after('father_name');
            }

            if (!Schema::hasColumn('patients', 'husband_name')) {
                $table->string('husband_name')->nullable()->after('wife_name');
            }

            if (Schema::hasColumn('patients', 'mother_name')) {
                $table->dropColumn('mother_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (!Schema::hasColumn('patients', 'mother_name')) {
                $table->string('mother_name')->nullable()->after('father_name');
            }

            if (Schema::hasColumn('patients', 'husband_name')) {
                $table->dropColumn('husband_name');
            }

            if (Schema::hasColumn('patients', 'wife_name')) {
                $table->dropColumn('wife_name');
            }
        });
    }
};
