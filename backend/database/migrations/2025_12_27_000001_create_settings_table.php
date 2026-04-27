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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group')->index(); // e.g., 'app', 'mail', 'sms', 'payment', 'social', 'seo', 'security'
            $table->string('key')->index();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, json, file
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false); // Whether setting can be exposed to frontend
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable()->index();
            $table->uuid('updated_by')->nullable()->index();
            $table->uuid('deleted_by')->nullable()->index();

            $table->unique(['group', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};