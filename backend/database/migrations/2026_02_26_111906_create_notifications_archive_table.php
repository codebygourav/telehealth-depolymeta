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
        Schema::create('notifications_archive', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->string('category')->nullable()->index();
            $table->string('event_type')->nullable()->index();
            $table->uuidMorphs('notifiable');
            $table->uuid('entity_id')->nullable()->index();
            $table->string('entity_type')->nullable()->index();
            $table->text('data');
            $table->boolean('is_push_sent')->default(false);
            $table->timestamp('push_sent_at')->nullable();
            $table->string('push_status')->nullable();
            $table->boolean('is_archived')->default(true)->index();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Performance indexes matching notifications
            $table->index(['notifiable_id', 'notifiable_type', 'created_at'], 'notif_arch_notifiable_created_at_idx');
            $table->index(['notifiable_id', 'notifiable_type', 'is_archived', 'created_at'], 'notif_arch_notifiable_archived_created_at_idx');
            $table->index(['notifiable_id', 'notifiable_type', 'read_at'], 'notif_arch_notifiable_read_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications_archive');
    }
};
