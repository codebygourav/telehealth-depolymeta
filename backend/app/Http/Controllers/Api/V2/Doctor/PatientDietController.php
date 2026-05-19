<?php

namespace App\Http\Controllers\Api\V2\Doctor;

use App\Http\Controllers\Controller;
use App\Models\DietTemplate;
use App\Models\DietTemplateDay;
use App\Models\DietTemplateMeal;
use App\Models\Patient;
use App\Models\PatientDietPlan;
use App\Models\PatientDietPlanDay;
use App\Models\PatientDietPlanMeal;
use App\Services\ApiResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PatientDietController extends Controller
{
    public function assign(Request $request)
    {
        $doctor = $this->doctor($request);
        if (! $doctor) {
            return ApiResponseService::unauthorized();
        }

        $data = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'template_id' => ['required', 'exists:diet_templates,id'],
            'start_date' => ['required', 'date'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:180'],
            'special_instructions' => ['nullable', 'string'],
        ]);

        $patient = $this->patientOrFail($data['patient_id']);
        $template = DietTemplate::query()
            ->with(['days.meals'])
            ->where('doctor_id', $doctor->id)
            ->where('is_active', true)
            ->findOrFail($data['template_id']);

        $startDate = Carbon::parse($data['start_date'])->startOfDay();
        $durationDays = (int) ($data['duration_days'] ?? $template->duration_days ?? 7);
        $endDate = $startDate->copy()->addDays(max(0, $durationDays - 1));

        $plan = DB::transaction(function () use ($doctor, $patient, $template, $data, $startDate, $endDate, $durationDays): PatientDietPlan {
            $plan = PatientDietPlan::create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'diet_template_id' => $template->id,
                'template_name' => $template->name,
                'template_description' => $template->description,
                'duration_days' => $durationDays,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'status' => 'active',
                'special_instructions' => $data['special_instructions'] ?? null,
            ]);

            foreach ($template->days as $day) {
                $planDayDate = $startDate->copy()->addDays($day->day_number - 1);

                $planDay = PatientDietPlanDay::create([
                    'patient_diet_plan_id' => $plan->id,
                    'day_number' => $day->day_number,
                    'week_day' => $day->week_day,
                    'date' => $planDayDate->toDateString(),
                ]);

                foreach ($day->meals as $meal) {
                    PatientDietPlanMeal::create([
                        'patient_diet_plan_day_id' => $planDay->id,
                        'meal_type' => $meal->meal_type,
                        'meal_name' => $meal->meal_name,
                        'instructions' => $meal->instructions,
                        'calories' => $meal->calories,
                        'protein_grams' => $meal->protein_grams,
                        'carbs_grams' => $meal->carbs_grams,
                        'fat_grams' => $meal->fat_grams,
                        'meal_time' => $meal->start_time,
                        'status' => 'pending',
                        'sort_order' => $meal->sort_order,
                    ]);
                }
            }

            return $plan;
        });

        return ApiResponseService::created(
            data: $this->planData($plan->load(['days.meals']))
        );
    }

    public function doctorPatientPlan(Request $request, string $patientId)
    {
        $doctor = $this->doctor($request);
        if (! $doctor) {
            return ApiResponseService::unauthorized();
        }

        $this->patientOrFail($patientId);

        $plan = PatientDietPlan::with(['days.meals'])
            ->where('patient_id', $patientId)
            ->where('doctor_id', $doctor->id)
            ->latest()
            ->firstOrFail();
        if (empty($plan)) {
            return ApiResponseService::notFound('Diet plan not found');
        }

        return ApiResponseService::success(
            data: $this->planData($plan)
        );
    }

    public function updatePlan(Request $request, string $id)
    {
        $doctor = $this->doctor($request);
        if (! $doctor) {
            return ApiResponseService::unauthorized();
        }

        $plan = PatientDietPlan::query()
            ->where('doctor_id', $doctor->id)
            ->findOrFail($id);

        $data = $request->validate([
            'status' => ['sometimes', Rule::in(['draft', 'active', 'paused', 'completed', 'cancelled'])],
            'special_instructions' => ['nullable', 'string'],
        ]);

        $plan->update($data);

        return ApiResponseService::success(
            data: $this->planData($plan->refresh()->load(['days.meals']))
        );
    }

    /**
     * Get a patient's diet plan.
     * Allows "me" or {patient_id} for multi-profile/patient support.
     * Works for both patient (self) and doctor (by patient id).
     *
     * Example GET /api/v2/patient/diet-plan (for patient:me)
     * Example GET /api/v2/doctor/{patientId}/diet-plan (for doctor)
     */
    public function patientPlan(Request $request, string $patientId = null)
    {
        // If patientId is not provided (default route), treat as "me" (authenticated patient)
        $user = $request->user();
        $doctor = $this->doctor($request);

        // If accessing as authenticated patient
        if (!$patientId || $patientId === "me") {
            $patient = $user?->patient;
            if (! $patient) {
                return ApiResponseService::unauthorized();
            }
            $patientId = $patient->id;
        } else {
            if (!$doctor) {
                return ApiResponseService::unauthorized();
            }
            $this->patientOrFail($patientId); // Throws if not a valid patient
        }

        $plan = PatientDietPlan::with(['days.meals'])
            ->where('patient_id', $patientId)
            ->whereIn('status', ['active', 'paused'])
            ->latest()
            ->firstOrFail();

        return ApiResponseService::success(
            data: $this->planData($plan)
        );
    }

    public function markMealCompleted(Request $request, string $mealId)
    {
        $patient = $request->user()?->patient;
        $doctor = $this->doctor($request);

        if (! $patient && ! $doctor) {
            return ApiResponseService::unauthorized();
        }

        $data = $request->validate([
            'status' => ['nullable', Rule::in(['completed', 'missed', 'skipped'])],
            'notes' => ['nullable', 'string'],
        ]);


        $meal = PatientDietPlanMeal::where('id', $mealId)
            ->whereHas('planDay.plan', function ($query) use ($patient, $doctor) {
                if ($patient && $doctor) {
                    $query->where('patient_id', $patient->id)
                        ->orWhere('doctor_id', $doctor->id);
                } elseif ($patient) {
                    $query->where('patient_id', $patient->id);
                } elseif ($doctor) {
                    $query->where('doctor_id', $doctor->id);
                }
            })
            ->firstOrFail();

        $status = $data['status'] ?? 'completed';
        $updates = [
            'status' => $status,
            'completed_at' => $status === 'completed' ? now() : null,
        ];

        if ($request->has('notes')) {
            $updates['notes'] = $data['notes'];
        }

        $meal->update($updates);

        return ApiResponseService::success(
            data: [
                'id' => $meal->id,
                'status' => $meal->status,
                'notes' => $meal->notes,
                'completed_at' => optional($meal->completed_at)?->toIso8601String(),
            ]
        );
    }

    private function doctor(Request $request)
    {
        return $request->user()?->doctor;
    }

    private function patientOrFail(string $patientId): Patient
    {
        return Patient::query()->findOrFail($patientId);
    }

    private function planData(PatientDietPlan $plan): array
    {
        return [
            'id' => $plan->id,
            'patient_id' => $plan->patient_id,
            'doctor_id' => $plan->doctor_id,
            'template_id' => $plan->diet_template_id,
            'template_name' => $plan->template_name,
            'template_description' => $plan->template_description,
            'duration_days' => $plan->duration_days,
            'start_date' => optional($plan->start_date)?->format('Y-m-d'),
            'end_date' => optional($plan->end_date)?->format('Y-m-d'),
            'status' => $plan->status,
            'special_instructions' => $plan->special_instructions,
            'days' => $plan->days->map(function (PatientDietPlanDay $day) {
                return [
                    'id' => $day->id,
                    'day_number' => $day->day_number,
                    'week_day' => $day->week_day,
                    'date' => optional($day->date)?->format('Y-m-d'),
                    'meals' => $day->meals->map(function (PatientDietPlanMeal $meal) {
                        return [
                            'id' => $meal->id,
                            'meal_type' => $meal->meal_type,
                            'meal_name' => $meal->meal_name,
                            'instructions' => $meal->instructions,
                            'calories' => $meal->calories,
                            'protein_grams' => $meal->protein_grams,
                            'carbs_grams' => $meal->carbs_grams,
                            'fat_grams' => $meal->fat_grams,
                            'meal_time' => $meal->meal_time,
                            'status' => $meal->status,
                            'notes' => $meal->notes,
                            'completed_at' => optional($meal->completed_at)?->toIso8601String(),
                            'sort_order' => $meal->sort_order,
                        ];
                    })->values(),
                ];
            })->values(),
            'created_at' => optional($plan->created_at)?->toIso8601String(),
            'updated_at' => optional($plan->updated_at)?->toIso8601String(),
        ];
    }
}