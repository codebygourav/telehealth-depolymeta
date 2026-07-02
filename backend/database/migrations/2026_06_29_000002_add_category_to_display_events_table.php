<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('display_events', function (Blueprint $table) {
            if (! Schema::hasColumn('display_events', 'category')) {
                $table->string('category')->default('advertisement')->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('display_events', function (Blueprint $table) {
            if (Schema::hasColumn('display_events', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};
