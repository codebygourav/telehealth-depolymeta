<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->string('strength')->nullable()->after('medicine_type');
            $table->string('route')->nullable()->after('meal_timing');
            $table->string('application_area')->nullable()->after('route');
            $table->boolean('is_sos')->default(false)->after('application_area');
            $table->text('sos_instruction')->nullable()->after('is_sos');
            $table->text('remarks')->nullable()->after('sos_instruction');
        });
    }

    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn([
                'strength',
                'route',
                'application_area',
                'is_sos',
                'sos_instruction',
                'remarks',
            ]);
        });
    }
};
