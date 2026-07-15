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
            $table->string('voice_name')->nullable()->after('sub_title');
            $table->double('speech_rate')->default(1.0)->after('voice_name');
            $table->double('speech_pitch')->default(1.0)->after('speech_rate');
            $table->string('speech_locale')->nullable()->after('speech_pitch');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn(['voice_name', 'speech_rate', 'speech_pitch', 'speech_locale']);
        });
    }
};
