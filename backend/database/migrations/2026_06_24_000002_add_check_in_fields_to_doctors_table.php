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
        Schema::table('doctors', function (Blueprint $table) {
            $table->boolean('is_checked_in')->default(false)->after('status');
            $table->timestamp('checked_in_at')->nullable()->after('is_checked_in');
            $table->boolean('is_on_break')->default(false)->after('checked_in_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn(['is_checked_in', 'checked_in_at', 'is_on_break']);
        });
    }
};
