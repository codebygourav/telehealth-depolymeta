# Diet Module (Simple Template + Assign Flow)

This diet module now uses a simple flow:

1. Doctor creates dynamic reusable diet templates.
2. Doctor assigns template to a patient with `start_date`.
3. System clones template into patient plan tables.
4. Patient follows meals and marks completion.

## Base URL

`http://localhost:8200/api/v2`

Use bearer token in all requests.

## Data Model

- `diet_templates`
- `diet_template_days`
- `diet_template_meals`
- `patient_diet_plans`
- `patient_diet_plan_days`
- `patient_diet_plan_meals`

## Template Structure

Each template stores:

- name
- description
- duration_days
- restrictions
- notes
- days (weekly/monthly day-wise)
  - meals per day
    - meal_type
    - meal_name
    - instructions
    - calories/macros
    - start_time

Supported `meal_type`:

- `MORNING`
- `BREAKFAST`
- `MID_MEAL`
- `LUNCH`
- `EVENING_SNACK`
- `DINNER`
- `NIGHT`

Supported `week_day`:

- `MONDAY` to `SUNDAY`

## APIs

### Doctor - Templates

- `GET /doctor/diet/templates`
- `POST /doctor/diet/templates`
- `GET /doctor/diet/templates/{id}`
- `PUT /doctor/diet/templates/{id}`
- `DELETE /doctor/diet/templates/{id}`

Sample create payload:

```json
{
  "name": "Diabetes Diet Plan",
  "description": "Low sugar balanced diet",
  "duration_days": 7,
  "restrictions": "Avoid refined sugar",
  "notes": "Hydration required",
  "is_active": true,
  "days": [
    {
      "day_number": 1,
      "week_day": "MONDAY",
      "meals": [
        {
          "meal_type": "BREAKFAST",
          "meal_name": "Oatmeal + Pear",
          "instructions": "Serve warm",
          "calories": 250,
          "protein_grams": 8,
          "carbs_grams": 40,
          "fat_grams": 4,
          "start_time": "08:30",
          "sort_order": 1
        },
        {
          "meal_type": "LUNCH",
          "meal_name": "Sweet Potato Puree",
          "calories": 320,
          "start_time": "12:45",
          "sort_order": 2
        }
      ]
    }
  ]
}
```

### Doctor - Assign + Manage Patient Plan

- `POST /doctor/diet/assign`
- `GET /doctor/{patientId}/diet-plan`
// To test what data will be included in the payload for updating a doctor diet plan, you can use the following endpoint and example:

- `PUT /doctor/diet/plans/{id}`

Sample update payload:

```json
{
  "status": "paused",
  "special_instructions": "Resume after blood test"
}
```

To verify, you can use tools such as Postman, curl, or unit/integration tests in your backend to submit a PUT request to `/doctor/diet/plans/{id}` with the above JSON payload. Then, check the backend logs, or the saved/returned data, to see exactly what is received and processed in the payload.

Assign payload:

```json
{
  "patient_id": "UUID_PATIENT",
  "template_id": "UUID_TEMPLATE",
  "start_date": "2026-05-01",
  "duration_days": 28,
  "special_instructions": "Low salt only"
}
```

Plan update payload:

```json
{
  "status": "paused",
  "special_instructions": "Resume after blood test"
}
```

Plan statuses:

- `draft`
- `active`
- `paused`
- `completed`
- `cancelled`

### Patient - Follow Plan

- `GET /patient/diet-plan`
- `POST /patient/diet/meal/{mealId}/complete`

Meal completion payload:

```json
{
  "status": "completed",
  "patient_notes": "Finished full meal"
}
```

Meal statuses:

- `pending`
- `completed`
- `missed`
- `skipped`

## Important Behavior

- Assignment creates a **snapshot clone** in patient plan tables.
- Later doctor edits in template **do not modify existing assigned patient plan**.
- Doctor can edit patient plan status/instructions separately.

## Postman Test Order

1. Create template (`POST /doctor/diet/templates`)
2. Fetch template (`GET /doctor/diet/templates/{id}`)
3. Assign template (`POST /doctor/diet/assign`)
4. Check doctor view (`GET /doctor/patients/{patientId}/diet-plan`)
5. Check patient view (`GET /patient/diet-plan`)
6. Mark meal complete (`POST /patient/diet/meal/{mealId}/complete`)
