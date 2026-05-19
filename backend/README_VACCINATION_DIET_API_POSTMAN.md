# Vaccination And Diet API Postman Guide

Base URL examples:

```text
Local: {{base_url}} = http://localhost:8100/api
```

Most doctor endpoints require a doctor bearer token:

```http
Authorization: Bearer {{doctor_token}}
Accept: application/json
Content-Type: application/json
```

Most patient endpoints require a patient bearer token:

```http
Authorization: Bearer {{patient_token}}
Accept: application/json
Content-Type: application/json
```

Use UUID values from your database/API responses for placeholders:

```text
{{patient_id}}
{{diet_template_id}}
{{diet_plan_id}}
{{vaccination_template_id}}
{{patient_profile_id}}
{{patient_vaccination_id}}
{{vaccination_id}}
{{vaccination_document_id}}
```

## Diet APIs

### Doctor: List Diet Templates

Lists active diet templates owned by the logged-in doctor.

```http
GET {{base_url}}/doctor/diet/templates?active_only=true&per_page=20
Authorization: Bearer {{doctor_token}}
```

Optional query params:

```text
active_only=true
search=diabetes
per_page=20
```

Use `data[0].id` from this response as `{{diet_template_id}}`.

### Doctor: Create Diet Template

```http
POST {{base_url}}/doctor/diet/templates
Authorization: Bearer {{doctor_token}}
Content-Type: application/json
```

```json
{
    "name": "Diabetes Friendly Diet - 7 Days",
    "description": "Low glycemic meal chart with balanced protein and fiber.",
    "duration_days": 7,
    "restrictions": "Avoid sugar, sweet drinks, refined flour, and deep fried food.",
    "notes": "Monitor glucose and adjust carbs as clinically needed.",
    "is_active": true,
    "days": [
        {
            "day_number": 1,
            "week_day": "MONDAY",
            "meals": [
                {
                    "meal_type": "MORNING",
                    "meal_name": "Methi water and walnuts",
                    "instructions": "Unsweetened methi water with 2 walnuts.",
                    "calories": 110,
                    "protein_grams": 3,
                    "carbs_grams": 5,
                    "fat_grams": 9,
                    "start_time": "07:00",
                    "sort_order": 1
                },
                {
                    "meal_type": "BREAKFAST",
                    "meal_name": "Besan chilla with mint curd",
                    "instructions": "Prepare with minimal oil.",
                    "calories": 300,
                    "protein_grams": 18,
                    "carbs_grams": 34,
                    "fat_grams": 10,
                    "start_time": "08:30",
                    "sort_order": 2
                },
                {
                    "meal_type": "LUNCH",
                    "meal_name": "Multigrain roti, dal, paneer, and salad",
                    "instructions": "Keep half plate salad or vegetables.",
                    "calories": 520,
                    "protein_grams": 28,
                    "carbs_grams": 58,
                    "fat_grams": 18,
                    "start_time": "13:00",
                    "sort_order": 3
                },
                {
                    "meal_type": "DINNER",
                    "meal_name": "Vegetable soup with grilled paneer",
                    "instructions": "Prefer early dinner.",
                    "calories": 390,
                    "protein_grams": 25,
                    "carbs_grams": 28,
                    "fat_grams": 20,
                    "start_time": "19:30",
                    "sort_order": 4
                }
            ]
        }
    ]
}
```

Allowed `week_day` values:

```text
MONDAY, TUESDAY, WEDNESDAY, THURSDAY, FRIDAY, SATURDAY, SUNDAY
```

Allowed `meal_type` values:

```text
MORNING, BREAKFAST, MID_MEAL, LUNCH, EVENING_SNACK, DINNER, NIGHT
```

### Doctor: Get Diet Template Detail

```http
GET {{base_url}}/doctor/diet/templates/{{diet_template_id}}
Authorization: Bearer {{doctor_token}}
```

### Doctor: Update Diet Template

```http
PUT {{base_url}}/doctor/diet/templates/{{diet_template_id}}
Authorization: Bearer {{doctor_token}}
Content-Type: application/json
```

You can update only template fields, or include `days` to replace all existing template days/meals.

```json
{
    "name": "Updated Diabetes Friendly Diet",
    "duration_days": 7,
    "is_active": true,
    "notes": "Updated notes for testing."
}
```

### Doctor: Delete Diet Template

```http
DELETE {{base_url}}/doctor/diet/templates/{{diet_template_id}}
Authorization: Bearer {{doctor_token}}
```

### Doctor: Assign Diet Template To Patient

This is the main endpoint for assigning a diet template to a patient using patient id.

```http
POST {{base_url}}/doctor/diet/assign
Authorization: Bearer {{doctor_token}}
Content-Type: application/json
```

```json
{
    "patient_id": "{{patient_id}}",
    "template_id": "{{diet_template_id}}",
    "start_date": "2026-05-18",
    "duration_days": 7,
    "special_instructions": "Avoid sugar and fried snacks. Keep dinner before 8 PM."
}
```

Notes:

- `patient_id` must exist in `patients`.
- `template_id` must exist in `diet_templates`.
- The template must belong to the logged-in doctor and must be active.
- The API copies template days and meals into a patient diet plan.
- Use the returned `data.id` as `{{diet_plan_id}}`.

### Doctor: Get Latest Patient Diet Plan

```http
GET {{base_url}}/doctor/{{patient_id}}/diet-plan
Authorization: Bearer {{doctor_token}}
```

### Doctor: Update Patient Diet Plan Status

```http
PUT {{base_url}}/doctor/diet/plans/{{diet_plan_id}}
Authorization: Bearer {{doctor_token}}
Content-Type: application/json
```

```json
{
    "status": "paused",
    "special_instructions": "Pause temporarily due to patient travel."
}
```

Allowed `status` values:

```text
draft, active, paused, completed, cancelled
```

### Patient: Get My Active Diet Plan

```http
GET {{base_url}}/patient/diet-plan
Authorization: Bearer {{patient_token}}
```

### Patient/Doctor: Mark Diet Meal Completed/Missed/Skipped

Use a meal id from the patient diet plan response: `data.days[].meals[].id`.
This is one shared endpoint for both patient and doctor apps.

- Patient token: can update meals from the authenticated patient's own diet plan.
- Doctor token: can update meals from diet plans assigned by the authenticated doctor.

```http
POST {{base_url}}/diet/meal/{{diet_plan_meal_id}}/complete
Authorization: Bearer {{patient_token_or_doctor_token}}
Content-Type: application/json
```

```json
{
    "status": "completed",
    "notes": "Meal completed as instructed."
}
```

Allowed `status` values:

```text
completed, missed, skipped
```

## Vaccination APIs

### Doctor: List Vaccination Master

Vaccination master creation is admin-side. Doctors can use this endpoint to fetch vaccine ids for custom assignment or templates.

```http
GET {{base_url}}/doctor/vaccinations?active_only=true&per_page=20
Authorization: Bearer {{doctor_token}}
```

### Doctor: Get Vaccination Master Detail

```http
GET {{base_url}}/doctor/vaccinations/{{vaccination_id}}
Authorization: Bearer {{doctor_token}}
```

### Doctor: List Vaccination Templates

```http
GET {{base_url}}/doctor/vaccination-templates?active_only=true&per_page=20
Authorization: Bearer {{doctor_token}}
```

Optional query params:

```text
vaccination_program_id={{vaccination_program_id}}
active_only=true
search=baby
per_page=20
```

Use `data[0].id` from this response as `{{vaccination_template_id}}`.

### Doctor: Get Vaccination Template Detail

```http
GET {{base_url}}/doctor/vaccination-templates/{{vaccination_template_id}}
Authorization: Bearer {{doctor_token}}
```

### Doctor: Update Vaccination Template

```http
PUT {{base_url}}/doctor/vaccination-templates/{{vaccination_template_id}}
Authorization: Bearer {{doctor_token}}
Content-Type: application/json
```

```json
{
    "name": "Updated Baby Immunization Template",
    "description": "Updated test template from Postman.",
    "is_active": true,
    "items": [
        {
            "vaccination_id": "{{vaccination_id}}",
            "set_name": "At Birth",
            "set_description": "Birth dose",
            "set_sort_order": 1,
            "dose_no": 1,
            "depends_on_previous_dose": false,
            "recommended_age_label": "At Birth",
            "due_after_days": 0,
            "due_after_months": 0,
            "sort_order": 1
        }
    ]
}
```

### Doctor: List Patient Profiles For Vaccination Assignment

```http
GET {{base_url}}/doctor/patients/{{patient_id}}/profiles?per_page=20
Authorization: Bearer {{doctor_token}}
```

### Doctor: Create Patient Profile

Useful for baby/pregnancy vaccination schedules. If `patient_profile_id` is not passed during template assignment, the API will resolve or create a primary self profile.

```http
POST {{base_url}}/doctor/patients/{{patient_id}}/profiles
Authorization: Bearer {{doctor_token}}
Content-Type: application/json
```

```json
{
    "name": "Baby Kumar",
    "profile_type": "baby",
    "date_of_birth": "2026-01-10",
    "gender": "male",
    "blood_group": "O+"
}
```

Common `profile_type` values depend on the enum in the backend. Existing flows typically use values such as:

```text
self, baby, pregnancy
```

### Doctor: Update Patient Profile

```http
PUT {{base_url}}/doctor/patients/{{patient_id}}/profiles/{{patient_profile_id}}
Authorization: Bearer {{doctor_token}}
Content-Type: application/json
```

```json
{
    "name": "Baby Kumar Updated",
    "profile_type": "baby",
    "date_of_birth": "2026-01-10",
    "gender": "male",
    "blood_group": "O+"
}
```

### Doctor: Delete Patient Profile

```http
DELETE {{base_url}}/doctor/patients/{{patient_id}}/profiles/{{patient_profile_id}}
Authorization: Bearer {{doctor_token}}
```

### Doctor: Assign Vaccination Template To Patient

```http
POST {{base_url}}/doctor/{{patient_id}}/assign-template
Authorization: Bearer {{doctor_token}}
Content-Type: application/json
```

```json
{
    "template_id": "{{vaccination_template_id}}",
    "patient_profile_id": "{{patient_profile_id}}",
    "first_dose_date": "2026-05-18"
}
```

Notes:

- `patient_profile_id` is optional.
- `first_dose_date` is optional. You can also send `start_date`.
- The selected template must belong to the logged-in doctor, be active, and be linked to a vaccination program.
- The response returns a vaccination program assignment and generated patient vaccinations.

### Doctor: Assign Custom Vaccination To Patient

```http
POST {{base_url}}/doctor/patients/{{patient_id}}/assign-custom-vaccination
Authorization: Bearer {{doctor_token}}
Content-Type: application/json
```

```json
{
    "patient_profile_id": "{{patient_profile_id}}",
    "vaccination_id": "{{vaccination_id}}",
    "dose_no": 1,
    "scheduled_date": "2026-05-25",
    "status": "scheduled",
    "manufacturer": "Demo Pharma",
    "given_at": "Clinic A",
    "given_by": "Dr. Amit Sharma",
    "doctor_notes": "Custom scheduled vaccine."
}
```

Allowed `status` values for custom assignment:

```text
pending, scheduled
```

### Doctor: List Patient Vaccination Program Assignments

```http
GET {{base_url}}/doctor/{{patient_id}}/vaccination-program-assignments?per_page=20
Authorization: Bearer {{doctor_token}}
```

Optional:

```text
patient_profile_id={{patient_profile_id}}
```

### Doctor: List Patient Vaccinations

```http
GET {{base_url}}/doctor/{{patient_id}}/vaccinations?per_page=20
Authorization: Bearer {{doctor_token}}
```

Optional:

```text
patient_profile_id={{patient_profile_id}}
status=pending
```

Common `status` values:

```text
pending, scheduled, completed, missed, cancelled
```

Use `data[0].id` from this response as `{{patient_vaccination_id}}`.

### Doctor: Update Patient Vaccination

```http
PUT {{base_url}}/doctor/patient-vaccinations/{{patient_vaccination_id}}
Authorization: Bearer {{doctor_token}}
Content-Type: application/json
```

```json
{
    "dose_no": 1,
    "scheduled_date": "2026-05-25",
    "status": "scheduled",
    "doctor_notes": "Rescheduled from Postman."
}
```

### Doctor: Mark Patient Vaccination Completed

```http
POST {{base_url}}/doctor/patient-vaccinations/{{patient_vaccination_id}}/complete
Authorization: Bearer {{doctor_token}}
Content-Type: application/json
```

```json
{
    "completed_date": "2026-05-18",
    "batch_number": "BATCH-001",
    "manufacturer": "Demo Pharma",
    "route": "IM",
    "site": "Left arm",
    "dose_amount": "0.5 ml",
    "given_at": "Clinic A",
    "given_by": "Dr. Amit Sharma",
    "doctor_notes": "Completed without complication.",
    "side_effect_observed": "None",
    "patient_reaction": "Stable"
}
```

### Doctor: Add Vaccination Document

This API expects a string document path/name, not a multipart upload.

```http
POST {{base_url}}/doctor/patient-vaccinations/{{patient_vaccination_id}}/documents
Authorization: Bearer {{doctor_token}}
Content-Type: application/json
```

```json
{
    "document": "vaccination-certificates/certificate-001.pdf",
    "document_type": "certificate",
    "certificate_number": "CERT-001"
}
```

### Doctor: Delete Vaccination Document

```http
DELETE {{base_url}}/doctor/vaccination-documents/{{vaccination_document_id}}
Authorization: Bearer {{doctor_token}}
```

### Patient: Vaccination Overview

```http
GET {{base_url}}/patient/vaccinations
Authorization: Bearer {{patient_token}}
```

### Patient: Vaccination Module Content

```http
GET {{base_url}}/patient/vaccination-content
Authorization: Bearer {{patient_token}}
```

### Patient: Vaccination Program Assignments

```http
GET {{base_url}}/patient/vaccination-program-assignments?per_page=20
Authorization: Bearer {{patient_token}}
```

### Patient: Vaccination Detail

```http
GET {{base_url}}/patient/vaccinations/{{patient_vaccination_id}}
Authorization: Bearer {{patient_token}}
```

## Suggested Postman Test Flow

1. Login as doctor and set `{{doctor_token}}`.
2. Get a patient id from your patient list API and set `{{patient_id}}`.
3. Diet flow:
    - `GET /doctor/diet/templates?active_only=true`
    - Set `{{diet_template_id}}`
    - `POST /doctor/diet/assign`
    - `GET /doctor/{{patient_id}}/diet-plan`
4. Vaccination flow:
    - `GET /doctor/vaccination-templates?active_only=true`
    - Set `{{vaccination_template_id}}`
    - Optional: create/list patient profile and set `{{patient_profile_id}}`
    - `POST /doctor/{{patient_id}}/assign-template`
    - `GET /doctor/{{patient_id}}/vaccinations`
    - Mark one vaccination completed.

## Seeder Note

Diet templates can be seeded with:

```bash
php artisan db:seed --class=DietTemplateSeeder
```

Vaccination demo data is seeded by `VaccinationModuleSeeder` when included in `DatabaseSeeder`.

Do not run database commands in Codex for this project unless you explicitly decide to run them yourself.
