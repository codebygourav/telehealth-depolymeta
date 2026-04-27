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
        Schema::table('notifications', function (Blueprint $table) {
            // Core classification
            $table->string('category')->nullable()->index()->after('type'); // appointment / review / report / system
            $table->string('event_type')->nullable()->index()->after('category'); // appointment_booked / cancelled / etc

            // Entity reference
            $table->uuid('entity_id')->nullable()->index()->after('event_type');
            $table->string('entity_type')->nullable()->index()->after('entity_id');

            // Delivery tracking
            $table->boolean('is_push_sent')->default(false)->after('data');
            $table->timestamp('push_sent_at')->nullable()->after('is_push_sent');
            $table->string('push_status')->nullable()->after('push_sent_at'); // success / failed / device_not_registered

            // Soft archive flag
            $table->boolean('is_archived')->default(false)->index()->after('read_at');

            // Performance indexes
            $table->index(['notifiable_id', 'notifiable_type', 'created_at'], 'notifications_notifiable_created_at_index');
            $table->index(['notifiable_id', 'notifiable_type', 'is_archived', 'created_at'], 'notifications_notifiable_archived_created_at_index');
            $table->index(['notifiable_id', 'notifiable_type', 'read_at'], 'notifications_notifiable_read_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_notifiable_created_at_index');
            $table->dropIndex('notifications_notifiable_archived_created_at_index');
            $table->dropIndex('notifications_notifiable_read_at_index');
            
            $table->dropColumn([
                'category',
                'event_type',
                'entity_id',
                'entity_type',
                'is_push_sent',
                'push_sent_at',
                'push_status',
                'is_archived',
            ]);
        });
    }
};
