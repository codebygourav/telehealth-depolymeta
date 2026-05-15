# Vaccination Module - Admin + API Guide

This module is admin-first for master data. Doctors use API to view their existing templates, update those templates if needed, assign templates to patients, and manage patient vaccine doses.

## Admin Sidebar Modules

The admin sidebar shows the working vaccination modules under `Clinical`.

1. `Vaccine Master`
   Create vaccine records such as BCG, Hepatitis B, Polio, Tdap, Influenza.

2. `Schedule Templates`
   Create reusable vaccination schedules for a doctor. The schedule program is created or selected inside this form, so there is no separate Program menu.

3. `Patient Family Profiles`
   Create patient-owned profiles such as Self, Baby Aryan, Wife Pregnancy, Father.

4. `Assigned Vaccine Programs`
   Assign a schedule template to a patient profile. The program comes from the selected template. Saving creates all patient vaccine dose rows.

5. `Patient Vaccine Doses`
   Track scheduled doses, completion, batch number, route, site, side effects, and reminders.

6. `Vaccination Documents`
   Attach certificates, prescriptions, scans, or consent forms to a patient vaccine dose.

## Admin Data Flow

1. Admin/data-entry creates vaccines in `Vaccine Master`.
2. Admin/data-entry creates a `Schedule Template` for a doctor.
3. Inside the template, admin selects or creates the `Schedule Program`, for example Baby Immunization.
4. Admin adds template dose rows with timing rules.
5. Admin or doctor creates/selects a `Patient Family Profile`.
6. Admin or doctor assigns the template to that profile.
7. System auto-generates `Patient Vaccine Doses`.
8. Doctor/admin marks doses completed and adds administration details.

## Dynamic Scheduling

Each template dose supports two timing modes.

### Count From Start Date

Use this when the dose is based on the assignment start date.

```text
start_date = 2026-05-01
due_after_months = 1
due_after_days = 15
scheduled_date = 2026-06-16
```

### Depends On Previous Dose

Use this when the dose date should be counted from the previous generated dose.

```text
Dose 1 scheduled_date = 2026-05-01
Dose 2 depends_on_previous_dose = true
interval_days = 30
Dose 2 scheduled_date = 2026-05-31
```

## Admin Field Meaning

### Vaccine Master

- `Name`: Full vaccine name.
- `Short Name`: Small display name, such as HepB.
- `Disease For`: Disease protected against.
- `Is Multi Dose`: Turn on if the vaccine needs multiple doses.
- `Total Doses`: Normal total doses.
- `Minimum Age Days`: Minimum patient age in days.
- `Maximum Age Days`: Maximum patient age in days.
- `Gender Restriction`: All Patients, Male Only, or Female Only.

### Schedule Templates

- `Doctor`: Doctor this template belongs to.
- `Schedule Program`: Program/category for this template. Create it here if it does not exist.
- `Template Name`: Name of the reusable schedule.
- `Template Doses`: Actual vaccine dose rows.
- `Dose Number`: Dose number for this vaccine.
- `Set Name`: Group shown in patient UI, for example Set 1 or 6 Weeks.
- `Depends On Previous Dose`: Turn on when this row should be counted from the previous dose date.
- `Interval Months/Days`: Gap from previous dose when dependency is on.
- `Due After Months/Days From Start`: Gap from assignment start date when dependency is off.
- `Min/Max Age Days`: Age rule for this template row.
- `Recommended Age`: Friendly label shown to patients.
- `Dose Sort Order`: Display order inside the set.

### Patient Family Profiles

- `Patient Account`: Main patient account owner.
- `Profile Name`: Actual recipient name, for example Baby Aryan.
- `Profile Type`: Self, Baby, Pregnancy, Child, Adult, Elderly.
- `Is Primary`: Default profile when no profile is selected.
- `Pregnancy Due Date`: Fill only for pregnancy profiles.

### Assigned Vaccine Programs

- `Patient Profile`: Profile receiving the schedule.
- `Vaccination Schedule Template`: Template to assign.
- `Doctor`: Responsible doctor.
- `Start Date`: Base date for due date calculation.
- `Status`: Active, Completed, Cancelled.

The program is taken from the selected template automatically.

### Patient Vaccine Doses

- `Status`: Pending, Scheduled, Completed, Missed, Cancelled.
- `Scheduled Date`: Dose due date.
- `Completed Date`: Date dose was given.
- `Batch Number`: Vaccine batch/lot number.
- `Route`: Oral, injection, intramuscular, etc.
- `Site`: Left thigh, upper arm, etc.
- `Dose Amount`: Example: 0.5 ml.
- `Side Effect Observed`: Clinical side effect notes.
- `Patient Reaction`: Patient or parent reported reaction.

### Vaccination Documents

- `Patient Vaccine Dose`: The dose this document belongs to.
- `Document Type`: Certificate, Prescription, Scan, or Consent Form.
- `Document Path or URL`: Stored file path or document URL.
- `Certificate Number`: Optional certificate number.

## API Base

```text
http://localhost:8200/api/v2
```

Headers:

```text
Authorization: Bearer YOUR_TOKEN
Accept: application/json
Content-Type: application/json
```

## Doctor APIs

Doctor API is intentionally limited. Master data creation is admin-side.

### Vaccine Lookup

Used when doctor updates an existing template dose row.

```text
GET /doctor/vaccinations
GET /doctor/vaccinations/{id}
```

### Get Doctor Templates With Items

```text
GET /doctor/vaccination-templates
GET /doctor/vaccination-templates?active_only=1
GET /doctor/vaccination-templates?vaccination_program_id=UUID_PROGRAM
GET /doctor/vaccination-templates/{id}
```

Response includes:

- template details
- schedule program
- dose items
- vaccine details per item

### Update Existing Doctor Template

Doctors can modify templates already created for them. They cannot create new templates by API.

```text
PUT /doctor/vaccination-templates/{id}
```

Payload:

```json
{
  "vaccination_program_id": "UUID_PROGRAM",
  "name": "WHO Child Schedule 2026 Updated",
  "description": "Birth to 12 months",
  "is_active": true,
  "items": [
    {
      "vaccination_id": "UUID_BCG",
      "set_name": "Set 1 (Birth)",
      "set_description": "Vaccines given at birth",
      "set_sort_order": 1,
      "dose_no": 1,
      "depends_on_previous_dose": false,
      "due_after_months": 0,
      "due_after_days": 0,
      "interval_months": 0,
      "interval_days": 0,
      "minimum_age_days": 0,
      "maximum_age_days": 30,
      "recommended_age_label": "At Birth",
      "sort_order": 1
    },
    {
      "vaccination_id": "UUID_HEPB",
      "set_name": "Set 2 (Follow-up)",
      "set_description": "Next Hepatitis B dose",
      "set_sort_order": 2,
      "dose_no": 2,
      "depends_on_previous_dose": true,
      "due_after_months": 0,
      "due_after_days": 0,
      "interval_months": 1,
      "interval_days": 0,
      "minimum_age_days": 30,
      "maximum_age_days": 90,
      "recommended_age_label": "1 Month",
      "sort_order": 1
    }
  ]
}
```

### Patient Profiles For Doctor

```text
GET    /doctor/patients/{patientId}/profiles
POST   /doctor/patients/{patientId}/profiles
PUT    /doctor/patients/{patientId}/profiles/{profileId}
DELETE /doctor/patients/{patientId}/profiles/{profileId}
```

Create payload:

```json
{
  "name": "Baby Aryan",
  "profile_type": "baby",
  "date_of_birth": "2026-01-10",
  "gender": "male",
  "pregnancy_due_date": null,
  "blood_group": "O+",
  "weight": 8.5,
  "height": 70,
  "is_primary": false
}
```

Allowed `profile_type`:

```text
self, baby, pregnancy, child, adult, elderly
```

### Assign Template To Patient Profile

```text
POST /doctor/{patientId}/assign-template
```

Payload:

```json
{
  "template_id": "UUID_TEMPLATE",
  "patient_profile_id": "UUID_PROFILE",
  "first_dose_date": "2026-05-01"
}
```

`start_date` is also accepted for backward compatibility.

### Assigned Programs By Doctor

```text
GET /doctor/{patientId}/vaccination-program-assignments
GET /doctor/{patientId}/vaccination-program-assignments?patient_profile_id=UUID_PROFILE
```

### Patient Vaccine Doses By Doctor

```text
GET  /doctor/{patientId}/vaccinations
GET  /doctor/{patientId}/vaccinations?patient_profile_id=UUID_PROFILE
GET  /doctor/{patientId}/vaccinations?status=scheduled
PUT  /doctor/patient-vaccinations/{id}
POST /doctor/patient-vaccinations/{id}/complete
POST /doctor/patient-vaccinations/{id}/documents
DELETE /doctor/vaccination-documents/{documentId}
```

Complete payload:

```json
{
  "completed_date": "2026-05-15",
  "batch_number": "BATCH-1001",
  "manufacturer": "Example Pharma",
  "route": "Injection",
  "site": "Left thigh",
  "dose_amount": "0.5 ml",
  "given_at": "Main Clinic",
  "given_by": "Dr. Mehta",
  "doctor_notes": "Dose completed successfully.",
  "side_effect_observed": "No immediate side effects.",
  "patient_reaction": "Normal"
}
```

Add document payload:

```json
{
  "document": "vaccinations/certificates/cert-1001.pdf",
  "document_type": "certificate",
  "certificate_number": "CERT-1001"
}
```

Allowed `document_type`:

```text
certificate, prescription, scan, consent_form
```

## Patient APIs

### Patient Profiles

```text
GET    /patient/profiles
POST   /patient/profiles
GET    /patient/profiles/{id}
PUT    /patient/profiles/{id}
DELETE /patient/profiles/{id}
```

Create payload:

```json
{
  "name": "Baby Aryan",
  "profile_type": "baby",
  "date_of_birth": "2026-01-10",
  "gender": "male",
  "blood_group": "O+",
  "weight": 8.5,
  "height": 70,
  "is_primary": false
}
```

### Patient Assigned Programs

```text
GET /patient/vaccination-program-assignments
GET /patient/vaccination-program-assignments?patient_profile_id=UUID_PROFILE
```

### Patient Vaccine Overview (single API)

```text
GET /patient/vaccinations
GET /patient/vaccinations?patient_profile_id=UUID_PROFILE
GET /patient/vaccination-content
GET /patient/vaccinations/{id}
```

`GET /patient/vaccinations` returns profile, vaccination summary, schedule sets, global FAQs, and clinical insight in one response.

`GET /patient/vaccination-content` returns only global FAQs and clinical insight.

See **[README_VACCINATION_API_POSTMAN.md](./README_VACCINATION_API_POSTMAN.md)** for full Postman examples and sample JSON.

## Reminder Tracking

Each patient dose has:

- `reminder_sent`
- `last_reminder_sent_at`
- `reminder_count`
- `next_reminder_at`

Command:

```bash
php artisan vaccinations:send-reminders --days=1
```

## Database Step

Run this manually when ready:

```bash
php artisan migrate
```

Do not clear or reset the database unless you intentionally want to remove existing data.

## Postman Test Flow

1. Admin creates vaccines.
2. Admin creates schedule template for a doctor and creates/selects schedule program inside it.
3. Doctor gets templates with `GET /doctor/vaccination-templates`.
4. Doctor updates an existing template if needed.
5. Doctor creates/selects patient profile.
6. Doctor assigns template to patient profile.
7. Doctor checks generated patient vaccine doses.
8. Doctor marks one dose completed.
9. Patient checks `GET /patient/vaccinations` for the full overview screen.

## Demo Seeder

Seeder file:

```text
database/seeders/VaccinationModuleSeeder.php
```

It creates demo data for:

- 6 vaccine master records
- 5 vaccination programs
- 5 schedule templates
- 5 patient family profiles
- 3 assigned vaccine programs
- generated patient vaccine doses with mixed statuses: completed, scheduled, pending, missed, cancelled
- demo certificate documents for completed doses

All seeded status/type values use enum classes such as `VaccinationStatus`, `PatientVaccinationProgramStatus`, `PatientProfileType`, `VaccinationProgramTargetType`, `VaccinationGenderRestriction`, and `VaccinationDocumentType`.

Manual run command when you are ready:

```bash
php artisan db:seed --class=VaccinationModuleSeeder
```
