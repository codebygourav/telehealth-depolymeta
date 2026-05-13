# Vaccination Module - Admin + API Guide

This document explains the updated vaccination architecture, how due dates are calculated, and how to test APIs in Postman.

## 1) Updated Architecture

The module now supports:

- Template groups/sets (for example: `Set 1 (0-6 Months)`, `Set 2 (6-12 Months)`).
- Recommended age label per template row (for display).
- First dose date driven scheduling.
- Automatic due date calculation using:
  - `due_after_months`
  - `due_after_days`
- Snapshot fields copied to patient assignments for reliable reporting and notifications.

## 2) Date Calculation Logic

When assigning a template to a patient:

- Request sends `first_dose_date` (or legacy `start_date`).
- Each template item calculates:
  - `scheduled_date = first_dose_date + due_after_months + due_after_days`

Example:

- `first_dose_date = 2026-05-01`
- `due_after_months = 1`
- `due_after_days = 15`
- Result: `scheduled_date = 2026-06-16`

## 3) Database Additions

New metadata columns are added by migration:

- `vaccination_template_items`
  - `set_name`, `set_description`, `set_sort_order`
  - `recommended_age_label`
  - `due_after_months`
- `patient_vaccinations`
  - `first_dose_date`
  - `set_name`, `set_sort_order`, `recommended_age_label`
  - `due_after_days`, `due_after_months`

Run:

```bash
php artisan migrate
```

## 4) Admin Panel Flow

### A) Template Setup

Screen: `/admin/vaccination-templates`

For each template item, fill:

- Vaccination
- Set Name (example: `Set 1 (0-6 Months)`)
- Set Description (optional)
- Set Order (numeric)
- Dose No
- Recommended Age (example: `At Birth`, `6 Weeks`)
- Due After Months
- Due After Days
- Sort Order

### B) Assigned Vaccinations Monitoring

Screen: `/admin/patient-vaccinations`

Shows:

- Patient, Doctor, Vaccination, Template
- Set, Recommended Age
- Dose, First Dose Date, Scheduled Date, Completed Date
- Status + Reminder sent

## 5) API Endpoints (Postman)

Base URL example:

`http://localhost:8200/api/v2`

Use bearer token in Authorization header.

### Doctor APIs

#### Vaccination Templates

- `GET /doctor/vaccination-templates`
- `POST /doctor/vaccination-templates`
- `GET /doctor/vaccination-templates/{id}`
- `PUT /doctor/vaccination-templates/{id}`
- `DELETE /doctor/vaccination-templates/{id}`
- `POST /doctor/vaccination-templates/{id}/clone`

Sample create payload:

```json
{
  "name": "Child Primary Schedule",
  "description": "Birth to 12 months",
  "is_active": true,
  "items": [
    {
      "vaccination_id": "UUID_VACCINE_1",
      "set_name": "Set 1 (0-6 Months)",
      "set_description": "Primary immunizations",
      "set_sort_order": 1,
      "dose_no": 1,
      "recommended_age_label": "At Birth",
      "due_after_months": 0,
      "due_after_days": 0,
      "sort_order": 1
    },
    {
      "vaccination_id": "UUID_VACCINE_2",
      "set_name": "Set 2 (6-12 Months)",
      "set_sort_order": 2,
      "dose_no": 1,
      "recommended_age_label": "6 Months",
      "due_after_months": 6,
      "due_after_days": 0,
      "sort_order": 1
    }
  ]
}
```

#### Assign Template To Patient

- `POST /doctor/{patientId}/assign-template`

Payload:

```json
{
  "template_id": "UUID_TEMPLATE",
  "first_dose_date": "2026-05-01"
}
```

`start_date` is still accepted for backward compatibility.

#### Patient Vaccinations By Doctor

- `GET /doctor/{patientId}/vaccinations`
- `POST /doctor/patient-vaccinations/{id}/complete`
- `PUT /doctor/patient-vaccinations/{id}`

### Patient APIs

#### Single endpoint with filter

- `GET /patient/vaccinations?filter=all`
- `GET /patient/vaccinations?filter=completed`
- `GET /patient/vaccinations?filter=upcoming`

`filter` values: `all` (default), `completed`, `upcoming`.

## 6) Notification Flow

Command:

```bash
php artisan vaccinations:send-reminders --days=1
```

Scheduler:

- `vaccinations:send-reminders --days=1` runs daily at `08:00`.

The command picks scheduled vaccinations due on target date and sends patient notification.

## 7) Suggested Postman Test Sequence

1. Create template with set and age metadata.
2. Assign template to patient with `first_dose_date`.
3. Verify created records in:
   - `/doctor/{patientId}/vaccinations`
   - `/patient/vaccinations?filter=all`
4. Mark one as completed.
5. Verify:
   - `/patient/vaccinations?filter=completed`
   - `/patient/vaccinations?filter=upcoming`
6. Run reminder command for test date window.

## 8) Website Design Mapping

Your provided UI design (Set 1/Set 2 cards, status badges, due dates) now maps directly to backend fields:

- Card title: `set_name`
- Card subtitle: `set_description`
- Recommended age: `recommended_age_label`
- Due date: computed `scheduled_date`
- Status badge: `status`

