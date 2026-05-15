# Vaccination APIs — Postman Guide

Complete reference for vaccination module APIs with sample requests and response shapes for Postman testing.

## Base URL

```text
http://localhost:8200/api/v2
```

Adjust host/port for your environment (`8100` proxy, Docker, etc.).

## Headers (all requests)

```text
Authorization: Bearer YOUR_SANCTUM_TOKEN
Accept: application/json
Content-Type: application/json
```

---

## Patient APIs

Login as a **patient** user and use that token for these routes.

### 1. Full vaccination overview (recommended)

Returns profile, summary, schedule sets, global FAQs, and clinical insight in one call.

```http
GET /patient/vaccinations
```

Optional query:

| Param | Example | Description |
|-------|---------|-------------|
| `patient_profile_id` | UUID | Scope to a family/baby/pregnancy profile |

**Sample response**

```json
{
  "success": true,
  "data": {
    "profile": {
      "id": "patient-uuid-or-profile-uuid",
      "patient_user_id": "user-uuid",
      "name": "Baby Aryan",
      "profile_type": "baby",
      "age": "8 months old",
      "weight": "8.5 kg",
      "height": "70 cm",
      "blood_group": "O+ve",
      "gender": "Male",
      "photo": "https://example.com/storage/avatar.jpg",
      "vaccination_summary": {
        "completed_percentage": 75,
        "completed_count": 9,
        "total_count": 12,
        "next_due_date": "15 Oct 2026"
      }
    },
    "vaccination_schedule": [
      {
        "set_id": 1,
        "set_name": "Set 1 (0-6 Months)",
        "description": "All primary immunizations completed successfully.",
        "status": "completed",
        "expanded": false,
        "vaccinations": [
          {
            "id": "patient-vaccination-uuid",
            "vaccine_name": "BCG",
            "short_description": "Tuberculosis",
            "recommended_age": "At Birth",
            "due_date": "01 May 2026",
            "status": "completed",
            "dose_no": 1,
            "completed_date": "01 May 2026",
            "information": {
              "id": "vaccine-uuid",
              "name": "BCG",
              "description": "...",
              "faqs": [
                {
                  "id": "faq-uuid",
                  "question": "Is BCG safe?",
                  "answer": "Yes, when given as per schedule.",
                  "sort_order": 1
                }
              ]
            },
            "dose_details": {
              "status": "completed",
              "batch_number": "BATCH-001",
              "documents": []
            }
          }
        ]
      }
    ],
    "faqs": [
      {
        "id": "general-faq-uuid",
        "question": "Why are multiple doses needed?",
        "answer": "Some vaccines require multiple doses...",
        "sort_order": 1
      }
    ],
    "clinical_insight": {
      "id": "insight-uuid",
      "title": "Clinical Insight",
      "message": "Vaccination schedules are based on international pediatric standards..."
    }
  }
}
```

**Notes**

- `profile_type: self` → health fields come from the **patient** account.
- `profile_type: baby|pregnancy|child|...` → fields come from **patient_profiles**.
- `expanded: true` on one schedule set marks the active/upcoming group in the UI.

---

### 2. Vaccination screen content only

Global FAQs + clinical insight (same data as bottom of overview, without schedule).

```http
GET /patient/vaccination-content
```

**Sample response**

```json
{
  "success": true,
  "data": {
    "faqs": [
      {
        "id": "uuid",
        "question": "Are vaccines safe?",
        "answer": "Yes, vaccines undergo rigorous testing.",
        "sort_order": 1
      }
    ],
    "clinical_insight": {
      "id": "uuid",
      "title": "Clinical Insight",
      "message": "If you miss a dose, contact your pediatrician..."
    }
  }
}
```

Managed in admin: **Vaccination Screen FAQs** and **Vaccination Clinical Insight** (not Settings).

---

### 3. Single patient vaccine dose

```http
GET /patient/vaccinations/{patient_vaccination_id}
```

---

### 4. Assigned programs list

```http
GET /patient/vaccination-program-assignments
GET /patient/vaccination-program-assignments?patient_profile_id=UUID_PROFILE
```

---

### 5. Patient family profiles (CRUD)

```http
GET    /patient/profiles
POST   /patient/profiles
GET    /patient/profiles/{id}
PUT    /patient/profiles/{id}
DELETE /patient/profiles/{id}
```

**Create body**

```json
{
  "name": "Baby Aryan",
  "profile_type": "baby",
  "date_of_birth": "2025-09-10",
  "gender": "male",
  "blood_group": "O+",
  "weight": 8.5,
  "height": 70,
  "is_primary": false
}
```

**`profile_type` values:** `self`, `baby`, `pregnancy`, `child`, `adult`, `elderly`

---

## Doctor APIs

Login as a **doctor** user.

### Vaccine lookup (read-only)

```http
GET /doctor/vaccinations
GET /doctor/vaccinations/{id}
GET /doctor/vaccinations?active_only=1&search=hep
```

---

### Vaccination templates

```http
GET  /doctor/vaccination-templates
GET  /doctor/vaccination-templates/{id}
PUT  /doctor/vaccination-templates/{id}
```

**Update body (excerpt)**

```json
{
  "name": "WHO Child Schedule 2026",
  "is_active": true,
  "items": [
    {
      "vaccination_id": "VACCINE_UUID",
      "set_name": "Set 1 (Birth)",
      "set_description": "Birth vaccines",
      "set_sort_order": 1,
      "dose_no": 1,
      "depends_on_previous_dose": false,
      "due_after_months": 0,
      "due_after_days": 0,
      "recommended_age_label": "At Birth",
      "sort_order": 1
    }
  ]
}
```

---

### Patient profiles (doctor)

```http
GET    /doctor/patients/{patientId}/profiles
POST   /doctor/patients/{patientId}/profiles
PUT    /doctor/patients/{patientId}/profiles/{profileId}
DELETE /doctor/patients/{patientId}/profiles/{profileId}
```

---

### Assign template to profile

```http
POST /doctor/{patientId}/assign-template
```

```json
{
  "template_id": "TEMPLATE_UUID",
  "patient_profile_id": "PROFILE_UUID",
  "first_dose_date": "2026-05-01"
}
```

---

### Assign custom single vaccine

```http
POST /doctor/patients/{patientId}/assign-custom-vaccination
```

```json
{
  "patient_profile_id": "PROFILE_UUID",
  "vaccination_id": "VACCINE_UUID",
  "dose_no": 1,
  "scheduled_date": "2026-06-01",
  "status": "scheduled"
}
```

---

### Patient vaccinations (doctor list)

```http
GET /doctor/{patientId}/vaccinations
GET /doctor/{patientId}/vaccinations?patient_profile_id=UUID&status=scheduled
```

---

### Program assignments

```http
GET /doctor/{patientId}/vaccination-program-assignments
```

---

### Update / complete dose

```http
PUT  /doctor/patient-vaccinations/{id}
POST /doctor/patient-vaccinations/{id}/complete
```

**Complete body**

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
  "doctor_notes": "Completed without issues.",
  "side_effect_observed": "None",
  "patient_reaction": "Normal"
}
```

---

### Vaccination documents

```http
POST   /doctor/patient-vaccinations/{id}/documents
DELETE /doctor/vaccination-documents/{documentId}
```

**Add document**

```json
{
  "document": "vaccinations/documents/cert-1001.pdf",
  "document_type": "certificate",
  "certificate_number": "CERT-1001"
}
```

**`document_type`:** `certificate`, `prescription`, `scan`, `consent_form`

---

## Postman collection flow (end-to-end)

1. **Auth** — `POST /login` (or your app login) → save `token`.
2. **Doctor** — `GET /doctor/vaccination-templates` → copy `template_id`.
3. **Doctor** — `GET /doctor/patients/{id}/profiles` → copy `patient_profile_id`.
4. **Doctor** — `POST /doctor/{patientId}/assign-template` with template + profile + `first_dose_date`.
5. **Doctor** — `GET /doctor/{patientId}/vaccinations` → copy a dose `id`.
6. **Doctor** — `POST /doctor/patient-vaccinations/{id}/complete`.
7. **Doctor** — `POST /doctor/patient-vaccinations/{id}/documents`.
8. **Patient** — `GET /patient/vaccinations` → full overview for app home screen.
9. **Patient** — `GET /patient/vaccination-content` → FAQs + insight only.
10. **Patient** — `GET /patient/vaccinations/{id}` → single dose detail.

---

## Demo data

```bash
php artisan migrate
php artisan db:seed --class=VaccinationModuleSeeder
```

Seeder creates demo doctor, patient, vaccines, templates, profiles, doses, documents, general FAQs, and clinical insight.

**Demo patient (if seeded):** check `VaccinationModuleSeeder` for `vaccination.patient@example.com` credentials after seed.

---

## Admin (content management)

| Admin URL | Purpose |
|-----------|---------|
| `/admin/vaccinations` | Vaccine master + per-vaccine FAQs |
| `/admin/vaccination-templates` | Schedule templates |
| `/admin/patient-profiles` | Family profiles |
| `/admin/patient-vaccination-programs` | Assign programs |
| `/admin/patient-vaccinations` | Dose tracking |
| `/admin/vaccination-documents` | Document grid + preview (like module-documents) |
| `/admin/vaccination-general-faqs` | Screen FAQs |
| `/admin/vaccination-clinical-insights` | Clinical insight message |

---

## Related docs

- [README_VACCINATION_MODULE.md](./README_VACCINATION_MODULE.md) — Admin workflows and field meanings
