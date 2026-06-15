# Paid Appointment External Sync

This module treats `paid_appointment` as a client-managed table in the separate `paid_appointments` database connection.

The app does not insert, update, delete, truncate, or backfill rows in `paid_appointment` during normal booking/payment flows. The client owns that table. The app only reads rows from it and syncs them into the local `external_bookings` table.

## Code

- Migration: `database/migrations/2026_06_04_000002_ensure_paid_appointment_table_on_paid_appointments_connection.php`
- External model: `app/Models/PaidAppointment.php`
- Pull sync service: `app/Services/PaidAppointmentSyncService.php`
- Pull sync command: `php artisan paid-appointments:sync`

Only one paid appointment migration is needed. It creates `paid_appointment` on the separate `paid_appointments` connection, not in the main app database.

## Required External Table Columns

The external `paid_appointment` table is shaped to map into `external_bookings`:

```sql
id
doctor_id
source_row_id
doctor_name
patient_name
patient_unit_number
patient_email
mobile
appointment_date
start_time
end_time
consultation_type
opd_type
track_upload_status
stack_upload_status
source_created_at
payment_id
created_at
updated_at
```

`doctor_id` should be the platform doctor UUID from the local `doctors.id` column. The sync can also fall back to doctor name matching, but UUID is the reliable option.

## Environment Variables

Add these values to production `.env`:

```dotenv
PAID_APPOINTMENT_DB_HOST=your_hostinger_mysql_host
PAID_APPOINTMENT_DB_PORT=3306
PAID_APPOINTMENT_DB_DATABASE=your_paid_appointments_database
PAID_APPOINTMENT_DB_USERNAME=your_paid_appointments_user
PAID_APPOINTMENT_DB_PASSWORD='your_paid_appointments_password'
```

If config is cached on production, clear and rebuild Laravel config after changing `.env`.

## Hostinger Steps

1. Create the separate Hostinger MySQL database for `paid_appointments`.
2. Create/assign the database user that the app will use to read that database.
3. Add the `PAID_APPOINTMENT_DB_*` values in `.env`.
4. Deploy the code.
5. Run the normal Laravel migration command if this migration has not been applied yet.
6. Run the sync command when you want to import client rows into `external_bookings`.

Commands to run on Hostinger:

```bash
cd /path/to/project/src
php artisan migrate --force
php artisan paid-appointments:sync
```

For scheduled sync, add a Hostinger cron job:

```bash
cd /path/to/project/src && php artisan paid-appointments:sync
```

Do not use any clear, truncate, fresh, reset, or database wipe command for this flow.

## Verify External Data

In phpMyAdmin, check the separate paid appointment database:

```sql
SELECT *
FROM paid_appointment
ORDER BY appointment_date DESC, start_time DESC;
```

Then run:

```bash
php artisan paid-appointments:sync
```

Synced rows appear in the main app database table `external_bookings` with `source = paid_appointment`.
