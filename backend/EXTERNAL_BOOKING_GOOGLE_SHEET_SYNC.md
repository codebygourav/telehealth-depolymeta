# External Booking Google Sheet Sync

This module syncs client appointment rows from Google Sheets into `external_bookings`.

## Sheet Columns

The sync expects these headers. Extra columns are ignored.

```text
id
patient_name
appointment_date
doctor_name
created_at
track_upload_status
patient_unit_number
time_slot
mobile
doctor_id
patient_email
stack_upload_status
```

Required fields per row:

- `appointment_date`
- `time_slot`
- either a mapped `doctor_id` or a doctor name matching the platform doctor

The Google Sheet `id` column is used as the stable external row ID. Google Sheet sync creates rows that are not already in `external_bookings` and skips rows that already exist locally.

## Google Cloud Setup

1. Enable the Google Sheets API in your Google Cloud project.
2. Create a service account.
3. Download its JSON key file.
4. Share the Google Sheet with the service account email from the JSON file, for example `name@project.iam.gserviceaccount.com`.
5. Give it Editor access if the app should mark synced rows as `Uploaded` in the sheet. Viewer access is enough only for dry-run/read-only testing.

Important: use a service account JSON key, not an OAuth client secret JSON. The correct JSON has top-level fields like:

```json
{
  "type": "service_account",
  "client_email": "...",
  "private_key": "..."
}
```

An OAuth file named like `client_secret_...apps.googleusercontent.com.json` usually contains a top-level `web` or `installed` key and will not work for this server-side automated sync.

## Environment Setup

Place the JSON key file somewhere readable by Laravel, for example:

```text
storage/app/google/service-account.json
```

Add these values to `.env`:

```dotenv
GOOGLE_SHEETS_SPREADSHEET_ID=1ucaQwP5stk3tY8-p5uaw22Y_YJnXCQgn18o7LYsbIc8
GOOGLE_SHEETS_RANGE=A:L
GOOGLE_SHEETS_SERVICE_ACCOUNT_JSON_PATH=storage/app/google/service-account.json
GOOGLE_SHEETS_EXTERNAL_BOOKINGS_SYNC_ENABLED=false
GOOGLE_SHEETS_EXTERNAL_BOOKINGS_SYNC_SCHEDULE="*/15 * * * *"
```

If the tab name contains spaces or you want to target a specific tab, set:

```dotenv
GOOGLE_SHEETS_RANGE="'Tab Name'!A:L"
```

You can also use `GOOGLE_SHEETS_SERVICE_ACCOUNT_JSON` instead of a file path, but a file path is easier to maintain.

## Doctor Mapping

The sync resolves doctors in this order:

1. Admin-selected doctor override during manual sync.
2. Sheet `doctor_id` matched against `doctors.google_sheet_doctor_id`.
3. Sheet `doctor_name` matched against platform doctor first and last name after removing `Dr`.

No migration is needed for this module because `google_sheet_doctor_id` already exists on doctors and `external_bookings` already has the required columns.

Set each platform doctor's `google_sheet_doctor_id` to the value used in the sheet's `doctor_id` column. You can do this from the Doctor form field labeled for Google Sheet mapping, or by asking your DB admin to update that column directly.

## Manual Sync

In Filament:

1. Open External Bookings from the Appointments page action.
2. Click `Sync Google Sheet`.
3. Confirm the spreadsheet ID and range.
4. Optionally choose a doctor override if the sheet belongs to one doctor.
5. Run the sync.

## Automated Sync

Enable the schedule in `.env`:

```dotenv
GOOGLE_SHEETS_EXTERNAL_BOOKINGS_SYNC_ENABLED=true
GOOGLE_SHEETS_EXTERNAL_BOOKINGS_SYNC_SCHEDULE="*/15 * * * *"
```

The Laravel scheduler must be running on the server. The scheduled command is:

```bash
php artisan external-bookings:sync-google-sheet
```

For a one-off sync with overrides:

```bash
php artisan external-bookings:sync-google-sheet --spreadsheet-id=1ucaQwP5stk3tY8-p5uaw22Y_YJnXCQgn18o7LYsbIc8 --range=A:L
```

To check access without saving anything:

```bash
php artisan external-bookings:sync-google-sheet --dry-run
```

Google Sheet sync never deletes rows from Google Sheets and keeps existing local external bookings even if they are missing from the latest sheet fetch.

After a Google Sheet row is confirmed in the local database, the app writes `Uploaded` to that row's `track_upload_status` column. Existing local rows are skipped for DB updates, but their sheet status can still be marked `Uploaded`.

## Data Resource Needed

This feature uses the existing `ExternalBooking` resource/table. You do not need a new database resource unless the client later asks to store multiple sheet configurations in the admin UI. For the current requirement, configuration through `.env` plus an admin sync action is enough.
