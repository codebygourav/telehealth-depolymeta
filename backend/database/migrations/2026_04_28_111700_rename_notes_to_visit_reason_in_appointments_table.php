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
        if (! Schema::hasColumn('appointments', 'notes')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table) {
            $table->renameColumn('notes', 'visit_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('appointments', 'visit_reason')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table) {
            $table->renameColumn('visit_reason', 'notes');
        });
    }
};
