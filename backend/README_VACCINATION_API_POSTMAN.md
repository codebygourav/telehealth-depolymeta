# Vaccination API Postman Guide

## Base URL
Use the base URL for your Laravel API. Example:

```
{{base_url}}/doctor
```

Set an `Authorization` header:

```
Authorization: Bearer <your_token>
```

---

## Vaccination Master Endpoints

### List Vaccinations
```
GET {{base_url}}/doctor/vaccinations?active_only=true&per_page=20
```

Query params:
- `active_only` (boolean) — filter only active vaccinations
- `search` (string) — search by name, short name, manufacturer, disease_for
- `per_page` (integer) — pagination size

### Get Vaccination Detail
```
GET {{base_url}}/doctor/vaccinations/{vaccination_id}
```

### Create Vaccination
```
POST {{base_url}}/doctor/vaccinations
Content-Type: application/json
```

Body example:
```json
{
  "name": "Pediatric Flu Vaccine",
  "short_name": "FluVax",
  "manufacturer": "HealthPharma",
  "disease_for": "Influenza",
  "description": "Protects against seasonal influenza.",
  "side_effects": "Soreness at injection site, mild fever.",
  "contraindications": "Allergy to egg proteins.",
  "precautions": "Avoid if fever > 101°F.",
  "dosage_information": "Single dose for children aged 6 months and older.",
  "is_multi_dose": false,
  "total_doses": 1,
  "minimum_age_days": 180,
  "maximum_age_days": 3650,
  "gender_restriction": "all",
  "is_active": true
}
```

### Update Vaccination
```
PUT {{base_url}}/doctor/vaccinations/{vaccination_id}
Content-Type: application/json
```

Body example:
```json
{
  "name": "Pediatric Influenza Vaccine",
  "is_active": true,
  "is_multi_dose": true,
  "total_doses": 2
}
```

### Delete Vaccination
```
DELETE {{base_url}}/doctor/vaccinations/{vaccination_id}
```

---

## Field Notes

- `name` is required for create.
- `short_name`, `manufacturer`, `disease_for`, `description`, `side_effects`, `contraindications`, `precautions`, `dosage_information` are optional.
- `is_multi_dose` is optional boolean.
- `total_doses` must be an integer `>= 1`; when `is_multi_dose` is true, it must be `>= 2`.
- `minimum_age_days` and `maximum_age_days` are optional integers.
- `gender_restriction` must be one of:
  - `all`
  - `male`
  - `female`
- `is_active` is optional boolean.

---

## Example headers

```
Content-Type: application/json
Authorization: Bearer <token>
```

## Troubleshooting

- If you get a `404` on `GET /doctor/vaccinations`, verify the base URL and confirm the request is sent to the API backend host, not only the frontend dev server proxy.
- If you get validation errors, inspect the response `errors.message` field for the exact missing/invalid field.
