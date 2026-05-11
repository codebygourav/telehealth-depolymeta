# Clinical APIs (Vaccination + Diet) - Postman Guide

This README covers API testing for both modules as separate domains:

- Vaccination module
- Diet plan module

Base URL:

`http://localhost:8200/api/v2`

Auth:

- Use Bearer token in `Authorization` header.
- All endpoints below are inside `auth:sanctum`.

---

## Important Note: Due After Months

`due_after_months` is treated as a **gap** during template assignment flow.

- First item starts from `first_dose_date` / `first_plan_date`.
- Next item date is calculated by adding gap to previous scheduled date.
- Formula used:
  - `next_date = previous_scheduled_date + due_after_months + due_after_days`

So yes, if second item has `due_after_months = 1`, it appears one month after the previous scheduled entry.

---

## 1) Vaccination APIs

### 1.1 Vaccine Master (Doctor)

- `GET /doctor/vaccinations`
- `POST /doctor/vaccinations`
- `GET /doctor/vaccinations/{id}`
- `PUT /doctor/vaccinations/{id}`
- `DELETE /doctor/vaccinations/{id}`

Create payload:

```json
{
  "name": "Hepatitis B",
  "short_name": "HepB",
  "manufacturer": "ABC Pharma",
  "disease_for": "Hepatitis B",
  "description": "Given at birth",
  "is_multi_dose": true,
  "total_doses": 3,
  "is_active": true
}
```

### 1.2 Vaccination Templates (Doctor)

- `GET /doctor/vaccination-templates`
- `POST /doctor/vaccination-templates`
- `GET /doctor/vaccination-templates/{id}`
- `PUT /doctor/vaccination-templates/{id}`
- `DELETE /doctor/vaccination-templates/{id}`
- `POST /doctor/vaccination-templates/{id}/clone`

Create payload:

```json
{
  "name": "Child Immunization Plan",
  "description": "0-12 month schedule",
  "is_active": true,
  "items": [
    {
      "vaccination_id": "UUID_VACCINE_1",
      "set_name": "Baby Month 1",
      "set_description": "Birth month",
      "set_sort_order": 1,
      "dose_no": 1,
      "recommended_age_label": "At Birth",
      "due_after_days": 0,
      "due_after_months": 0,
      "sort_order": 1
    },
    {
      "vaccination_id": "UUID_VACCINE_2",
      "set_name": "Baby Month 2",
      "set_description": "Follow-up",
      "set_sort_order": 2,
      "dose_no": 2,
      "recommended_age_label": "After 1 month",
      "due_after_days": 0,
      "due_after_months": 1,
      "sort_order": 2
    }
  ]
}
```

### 1.3 Assign + Manage Patient Vaccinations (Doctor)

- `POST /doctor/{patientId}/assign-template`
- `POST /doctor/patients/{patientId}/assign-custom-vaccination`
- `GET /doctor/{patientId}/vaccinations`
- `PUT /doctor/patient-vaccinations/{id}`
- `POST /doctor/patient-vaccinations/{id}/complete`

Assign template payload:

```json
{
  "template_id": "UUID_TEMPLATE",
  "first_dose_date": "2026-05-01"
}
```

Assign custom payload:

```json
{
  "vaccination_id": "UUID_VACCINE_1",
  "dose_no": 1,
  "scheduled_date": "2026-05-20",
  "status": "scheduled",
  "doctor_notes": "Take after consultation"
}
```

Update patient vaccination payload:

```json
{
  "status": "completed",
  "completed_date": "2026-05-20",
  "batch_number": "BATCH-101",
  "manufacturer": "ABC Pharma",
  "given_at": "City Clinic",
  "given_by": "Nurse A",
  "doctor_notes": "No side effects"
}
```

### 1.4 Patient Vaccination Endpoints (Patient App)

- `GET /patient/vaccinations?filter=all`
- `GET /patient/vaccinations?filter=completed`
- `GET /patient/vaccinations?filter=upcoming`
- `GET /patient/vaccinations/{id}`

---

## 2) Diet APIs (Separate Module)

Diet and Vaccination are separate concerns:

- Vaccination is for vaccine schedule/completion.
- Diet is for nutrition plan templates and patient prescription lifecycle.

Reflection rule:

- If doctor updates a diet master (`/doctor/diets/{id}`), patient responses reflect updated `diet.name`, `meal_time`, `description`, `instructions`.
- If doctor updates a specific assigned patient diet (`/doctor/patient-diets/{id}`), that assigned row immediately reflects on patient side lists/chart.

### 2.1 Diet Master (Doctor)

- `GET /doctor/diets`
- `POST /doctor/diets`
- `GET /doctor/diets/{id}`
- `PUT /doctor/diets/{id}`
- `DELETE /doctor/diets/{id}`

Create diet payload:

```json
{
  "name": "First Trimester High-Protein Diet",
  "meal_time": "Morning",
  "description": "Protein-rich foods for early pregnancy",
  "instructions": "Take with adequate hydration",
  "is_active": true
}
```

### 2.2 Diet Templates (Doctor)

- `GET /doctor/diet-templates`
- `POST /doctor/diet-templates`
- `GET /doctor/diet-templates/{id}`
- `PUT /doctor/diet-templates/{id}`
- `DELETE /doctor/diet-templates/{id}`
- `POST /doctor/diet-templates/{id}/clone`

Create template payload:

```json
{
  "name": "Pediatrics Month-wise Diet Plan",
  "description": "Child diet progression",
  "is_active": true,
  "items": [
    {
      "diet_id": "UUID_DIET_1",
      "set_name": "Baby Month 6",
      "set_description": "Intro solids",
      "set_sort_order": 1,
      "recommended_age_label": "6 Months",
      "due_after_days": 0,
      "due_after_months": 0,
      "sort_order": 1
    },
    {
      "diet_id": "UUID_DIET_2",
      "set_name": "Baby Month 7",
      "set_description": "Texture progression",
      "set_sort_order": 2,
      "recommended_age_label": "7 Months",
      "due_after_days": 0,
      "due_after_months": 1,
      "sort_order": 2
    }
  ]
}
```

### 2.3 Assign + Manage Patient Diets (Doctor)

- `POST /doctor/{patientId}/assign-diet-template`
- `POST /doctor/patients/{patientId}/assign-custom-diet`
- `GET /doctor/{patientId}/diets`
- `GET /doctor/diet-assignments`
- `PUT /doctor/patient-diets/{id}`
- `DELETE /doctor/patient-diets/{id}`

Assign template payload:

```json
{
  "template_id": "UUID_DIET_TEMPLATE",diet module is wromng ,

this should be simple doctor cerate create diet prebuilt tempale and giev to any patinet , patient can be any type any not for particulat pateint 

  "first_plan_date": "2026-05-01"
}
```

Assign custom diet payload:

```json
{
  "diet_id": "UUID_DIET_1",
  "scheduled_date": "2026-05-12",
  "status": "scheduled",
  "doctor_notes": "Follow for 2 weeks"
}
```

Update patient diet payload:

```json
{
  "status": "active",
  "scheduled_date": "2026-05-12",
  "doctor_notes": "Increased calorie requirement",
  "is_active": true
}
```

Assignment tracking filters:

- `GET /doctor/diet-assignments?patient_id=UUID_PATIENT`
- `GET /doctor/diet-assignments?diet_id=UUID_DIET`
- `GET /doctor/diet-assignments?template_id=UUID_TEMPLATE`
- `GET /doctor/diet-assignments?status=scheduled`

This endpoint gives all records: who was assigned, which diet, which template, when assigned, and current status.

### 2.4 Patient Diet Endpoints (Patient App)

- `GET /patient/diets?filter=all`
- `GET /patient/diets?filter=completed`
- `GET /patient/diets?filter=upcoming`
- `GET /patient/diets/{id}`
- `GET /patient/diets-weekly-chart?week_start=2026-05-18`

Weekly chart API is designed for your UI-style meal calendar and groups records by day + meal time.

---

## 3) Suggested Postman Test Order

1. Create master vaccine + master diet.
2. Create vaccination template + diet template.
3. Assign both templates to same patient.
4. Fetch doctor patient lists:
   - `/doctor/{patientId}/vaccinations`
   - `/doctor/{patientId}/diets`
5. Update one vaccination and one diet record.
6. Fetch patient-side lists:
   - `/patient/vaccinations?filter=upcoming`
   - `/patient/diets?filter=upcoming`
7. Mark vaccination completed and set diet active/completed.

---

## 4) Admin Notes

- Admin edit for patient vaccination is now enabled in:
  - `/admin/patient-vaccinations`
- Vaccination template item UI focuses on:
  - Vaccination
  - Set Name
  - Set Description
  - Dose No.
  - Recommended Age
  - Due After Months
