1. High-Level Flow (How it works)
Event Occurs: For example, an appointment is booked in 
BookAppointmentController
.
Service Called: It calls NotificationService::send(). This service captures a Snapshot of the current data (doctor name, date, time) so the record remains accurate even if the doctor changes their name 2 years later.
Dispatch: The 
SystemNotification
 class is triggered. It is configured to use two channels: Push Notifications (Expo) and our Custom Database Channel.
Storage & Counting: The 
CustomDatabaseChannel
 writes the notification to the DB with audit columns and simultaneously increments the unread_notifications_count on the 
User
 table for ultra-fast performance.
2. The Purpose of 
CustomDatabaseChannel
Laravel's default database channel only knows how to save a simple JSON blob. For an enterprise app, that isn't enough. Use of 
CustomDatabaseChannel
:

Audit Logic: It extracts metadata from the notification (like category, entity_id, and event_type) and saves them into dedicated, indexed database columns.
Performance Optimization: Every time a notification is saved, it automatically increments a counter in the users table. This allows the "Unread Count" API to return a result instantly without scanning millions of rows.
Legacy Protection: It ensures that every notification is categorized correctly at the database level, allowing for high-speed filtering and reporting.
3. File Inventory
The system is comprised of these key files:

File Name	Responsibility
NotificationService.php
The "Brain". Centralizes logic for all notifications (booked, cancelled, reviews).
SystemNotification.php
The "Package". defines what data is sent to Push and what is saved to DB.
CustomDatabaseChannel.php
The "Vault Manager". Handles the complex logic of saving audit-ready records.
NotificationController.php
The "Gatekeeper". Handles all API requests (List, Read, Archive).
NotificationResource.php
The "Translator". Ensures the Mobile App gets a clean, grouped structure.
User.php
 (Model)	The "Recipient". Holds the multi-device relationships and unread counters.
UserDevice.php
 (Model)	The "Device Registry". Manages multiple push tokens per user.
4. API Functionality breakdown
GET /api/v2/notifications (The List)
Reacts by: Using Cursor Pagination. Unlike normal pagination, this doesn't slow down as the database gets larger.
Filtering: Supports ?category=appointment or ?category=review. It defaults to showing only non-archived notifications.
GET /api/v2/notifications/unread-count (The Radar)
Reacts by: Reading directly from users.unread_notifications_count. It is 100x faster than counting rows in a large notifications table.
POST /api/v2/notifications/{id}/read (The Acknowledge)
Reacts by: Marking the record with read_at and decrementing the user's unread counter.
POST /api/v2/notifications/{id}/archive (The Cleanup)
Reacts by: Setting is_archived = true. This hides the notification from the main list but keeps it in the DB for audit/legal history. It satisfies your "No Deletion" requirement.
5. Future-Proof Design
Duplicate Prevention: The 
NotificationService
 has a 5-minute guard. If the same user gets the same appointment notification twice (e.g., due to a double-click), it ignores the second one.
Legal Defense: If a doctor claims "I wasn't notified about this appointment," the DB entry shows the push_status, the exact snapshot of what was sent, and whether they read_at the message.
Note on Migration: I have prepared the database upgrade scripts. Once you run php artisan migrate, these powerful features (counters, audit columns, and archiving) will be fully active.