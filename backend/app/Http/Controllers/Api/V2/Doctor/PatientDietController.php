<?php

namespace App\Http\Controllers\Api\V2\Doctor;

use App\Http\Controllers\Controller;
use App\Models\DietTemplate;
use App\Models\DietTemplateDay;
use App\Models\DietTemplateMeal;
use App\Models\Patient;
use App\Models\PatientDietPlanMealCompletion;
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
        if (! $doctor && ! $this->canManageAllDietPlans($request)) {
            return ApiResponseService::unauthorized();
        }

        $data = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'template_id' => ['required', 'exists:diet_templates,id'],
            'start_date' => ['required', 'date'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:180'],
            'special_instructions' => ['nullable', 'string'],
            'doctor_remark' => ['nullable', 'string'],
        ]);

        $patient = $this->patientOrFail($data['patient_id']);

        // Check if there is already an active plan with the same template_id for this patient
        $existingActivePlan = PatientDietPlan::where('patient_id', $patient->id)
            ->where('diet_template_id', $data['template_id'])
            ->where('status', 'active')
            ->first();

        if ($existingActivePlan) {
            return ApiResponseService::validationError([
                'template_id' => 'This diet template is already actively assigned to this patient.'
            ]);
        }

        $templateQuery = DietTemplate::query()
            ->with(['days.meals'])
            ->where('is_active', true);

        if ($doctor) {
            $templateQuery->where('doctor_id', $doctor->id);
        }

        $template = $templateQuery->findOrFail($data['template_id']);
        $planDoctorId = $doctor?->id ?? $template->doctor_id;
        $schedule = (array) data_get($template->features, 'schedule', []);
        $recurrenceMode = (string) ($schedule['recurrence_mode'] ?? 'recurring');

        $startDate = Carbon::parse($data['start_date'])->startOfDay();
        $durationDays = (int) ($data['duration_days'] ?? $template->duration_days ?? 7);

        if ($recurrenceMode === 'one_time' && $template->days->count() > 0) {
            $durationDays = min($durationDays, $template->days->count());
        }

        $endDate = $startDate->copy()->addDays(max(0, $durationDays - 1));

        $plan = DB::transaction(function () use ($planDoctorId, $patient, $template, $data, $startDate, $endDate, $durationDays): PatientDietPlan {
            $plan = PatientDietPlan::create([
                'patient_id' => $patient->id,
                'doctor_id' => $planDoctorId,
                'diet_template_id' => $template->id,
                'template_name' => $template->name,
                'template_description' => $template->description,
                'duration_days' => $durationDays,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'status' => 'active',
                'special_instructions' => $data['special_instructions'] ?? null,
                'diet_category' => $template->diet_category,
                'patient_type' => $template->patient_type,
                'daily_calories' => $template->daily_calories,
                'protein_target' => $template->protein_target,
                'carbs_limit' => $template->carbs_limit,
                'salt_limit' => $template->salt_limit,
                'doctor_remark' => $data['doctor_remark'] ?? $template->doctor_remark ?? null,
                'allowed_food_notes' => $template->allowed_food_notes,
                'hydration_advice' => $template->hydration_advice,
                'exercise_advice' => $template->exercise_advice,
                'features' => $template->features,
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
                        'meal_image' => $meal->meal_image,
                        'helpful_links' => $meal->helpful_links,
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

        try {
            \App\Services\NotificationService::notifyDietPlanAssigned($plan);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to trigger diet plan assignment notification: " . $e->getMessage());
        }

        return ApiResponseService::created(
            data: $this->planData($plan->load(['days.meals', 'doctor.user']))
        );
    }

    public function doctorPatientPlan(Request $request, string $patientId)
    {
        $doctor = $this->doctor($request);
        if (! $doctor) {
            return ApiResponseService::unauthorized();
        }

        $this->patientOrFail($patientId);

        $plans = PatientDietPlan::with(['days.meals', 'doctor.user'])
            ->where('patient_id', $patientId)
            ->where('doctor_id', $doctor->id)
            ->orderBy('start_date')
            ->orderBy('created_at')
            ->get();

        if ($plans->isEmpty()) {
            return ApiResponseService::success(
                data: [
                    'patient_id' => $patientId,
                    'plans' => [],
                ]
            );
        }

        $formattedPlans = $plans->map(fn(PatientDietPlan $plan): array => $this->planData($plan))->values();

        return ApiResponseService::success(
            data: [
                'patient_id' => $patientId,
                'plans' => $formattedPlans,
            ]
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
            ->find($id);

        if (! $plan) {
            return ApiResponseService::notFound('Diet plan not found');
        }

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
    public function patientPlan(Request $request, ?string $patientId = null)
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

        $plans = PatientDietPlan::with(['days.meals', 'doctor.user'])
            ->where('patient_id', $patientId)
            ->orderBy('start_date')
            ->orderBy('created_at')
            ->get();

        if ($plans->isEmpty()) {
            return ApiResponseService::success(
                data: [
                    'patient_id' => $patientId,
                    'plans' => [],
                ]
            );
        }

        $formattedPlans = $plans->map(fn(PatientDietPlan $plan): array => $this->planData($plan))->values();

        return ApiResponseService::success(
            data: [
                'patient_id' => $patientId,
                'plans' => $formattedPlans,
            ]
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
            'date' => ['required', 'date'],
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

        $occurrenceDate = Carbon::parse($data['date'])->toDateString();
        $status = $data['status'] ?? 'completed';
        $actor = $patient ? $request->user() : $request->user();
        $completedByRole = $patient ? 'patient' : 'doctor';
        $completedByName = trim((string) ($actor?->name ?? '')) ?: trim(($actor?->first_name ?? '') . ' ' . ($actor?->last_name ?? ''));
        $completion = PatientDietPlanMealCompletion::updateOrCreate(
            [
                'patient_diet_plan_meal_id' => $meal->id,
                'occurrence_date' => $occurrenceDate,
            ],
            [
                'status' => $status,
                'completed_by_role' => $completedByRole,
                'completed_by_name' => $completedByName ?: null,
                'notes' => $data['notes'] ?? null,
                'completed_at' => $status === 'completed' ? now() : null,
            ]
        );

        return ApiResponseService::success(
            data: [
                'id' => $meal->id,
                'status' => $completion->status,
                'completed_by_role' => $completion->completed_by_role,
                'completed_by_name' => $completion->completed_by_name,
                'notes' => $completion->notes,
                'completed_at' => optional($completion->completed_at)?->toIso8601String(),
                'date' => $occurrenceDate,
            ]
        );
    }

    private function doctor(Request $request)
    {
        return $request->user()?->doctor;
    }

    private function canManageAllDietPlans(Request $request): bool
    {
        $user = $request->user();

        return is_object($user)
            && method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['super_admin', 'admin', 'doctor_manager', 'receptionist']);
    }

    private function patientOrFail(string $patientId): Patient
    {
        return Patient::query()->findOrFail($patientId);
    }

    private function planData(PatientDietPlan $plan): array
    {
        $doctorName = '—';
        if ($plan->doctor) {
            $doctorName = trim("{$plan->doctor->first_name} {$plan->doctor->last_name}") ?: ($plan->doctor->name ?: ($plan->doctor->user?->name ?? '—'));
        }

        // Reference days as "template days" (e.g., a 7-day template)
        $templateDays = $plan->days->sortBy('day_number')->values();
        $templateDaysCount = $templateDays->count();

        $startDate = optional($plan->start_date)?->copy()->startOfDay();
        $endDate = optional($plan->end_date)?->copy()->startOfDay();

        $duration = 0;
        if ($startDate && $endDate) {
            // Inclusive days, add 1
            $duration = $startDate->diffInDays($endDate) + 1;
        } elseif ($plan->duration_days) {
            $duration = $plan->duration_days;
        } else {
            $duration = $templateDaysCount;
        }

        $planDays = [];
        if ($templateDaysCount > 0 && $startDate && $duration > 0) {
            $schedule = (array) data_get($plan->features, 'schedule', []);
            $recurrenceMode = (string) ($schedule['recurrence_mode'] ?? 'recurring');
            $patternType = (string) ($schedule['pattern_type'] ?? 'cycle');
            $followSameMealAllDays = (bool) ($schedule['follow_same_meal_all_days'] ?? false);
            $cycleLengthDays = max(1, (int) ($schedule['cycle_length_days'] ?? $templateDaysCount));
            $templateDayByNumber = $templateDays->keyBy(fn(PatientDietPlanDay $day) => (int) $day->day_number);
            $templateDayByWeekday = $templateDays->keyBy(fn(PatientDietPlanDay $day) => strtoupper((string) $day->week_day));
            $firstTemplateDay = $templateDays->first();

            if ($recurrenceMode === 'one_time') {
                $duration = min($duration, $templateDaysCount);
            }

            $mealIds = $templateDays->flatMap(fn(PatientDietPlanDay $day) => $day->meals->pluck('id'))->values();
            $completionMap = PatientDietPlanMealCompletion::query()
                ->whereIn('patient_diet_plan_meal_id', $mealIds)
                ->whereBetween('occurrence_date', [$startDate->toDateString(), $endDate?->toDateString() ?? $startDate->toDateString()])
                ->get()
                ->mapWithKeys(fn(PatientDietPlanMealCompletion $completion): array => [
                    $completion->patient_diet_plan_meal_id . '|' . $completion->occurrence_date->toDateString() => $completion,
                ]);

            for ($i = 0; $i < $duration; $i++) {
                $currentDate = $startDate->copy()->addDays($i);
                $weekDay = strtoupper($currentDate->format('l'));

                if ($followSameMealAllDays && $firstTemplateDay) {
                    $cloneDay = $firstTemplateDay;
                } elseif ($patternType === 'weekly') {
                    $cloneDay = $templateDaysCount > 7
                        ? ($templateDayByNumber->get($i + 1) ?? $templateDays[$i % $templateDaysCount])
                        : ($templateDayByWeekday->get($weekDay) ?? $templateDays[$i % $templateDaysCount]);
                } else {
                    $cycleDayNumber = ($i % $cycleLengthDays) + 1;
                    $cloneDay = $templateDayByNumber->get($cycleDayNumber) ?? $templateDays[$i % $templateDaysCount];
                }

                $planDays[] = [
                    'id' => $cloneDay->id, // Could alternatively use: $cloneDay->id . "_repeat_" . ($i + 1)
                    'day_number' => $i + 1,
                    'week_day' => $weekDay,
                    'date' => $currentDate->format('Y-m-d'),
                    'meals' => $cloneDay->meals->map(function (PatientDietPlanMeal $meal) use ($completionMap, $currentDate) {
                        $completion = $completionMap[$meal->id . '|' . $currentDate->toDateString()] ?? null;

                        return [
                            'id' => $meal->id,
                            'meal_type' => $meal->meal_type,
                            'meal_name' => $meal->meal_name,
                            'instructions' => $meal->instructions,
                            'meal_image' => $meal->meal_image,
                            'helpful_links' => $meal->helpful_links ?? [],
                            'calories' => $meal->calories,
                            'protein_grams' => $meal->protein_grams,
                            'carbs_grams' => $meal->carbs_grams,
                            'fat_grams' => $meal->fat_grams,
                            'meal_time' => $meal->meal_time,
                            'status' => $completion?->status ?? $meal->status,
                            'completed_by_role' => $completion?->completed_by_role,
                            'completed_by_name' => $completion?->completed_by_name,
                            'notes' => $completion?->notes ?? $meal->notes,
                            'completed_at' => optional($completion?->completed_at)?->toIso8601String(),
                            'occurrence_date' => $currentDate->toDateString(),
                            'sort_order' => $meal->sort_order,
                        ];
                    })->values(),
                ];
            }
        }

        return [
            'id' => $plan->id,
            'patient_id' => $plan->patient_id,
            'doctor_id' => $plan->doctor_id,
            'doctor_name' => $doctorName,
            'template_id' => $plan->diet_template_id,
            'template_name' => $plan->template_name,
            'template_description' => $plan->template_description,
            'duration_days' => $duration,
            'start_date' => optional($plan->start_date)?->format('Y-m-d'),
            'end_date' => optional($plan->end_date)?->format('Y-m-d'),
            'status' => $plan->status,
            'special_instructions' => $plan->special_instructions,
            'diet_category' => $plan->diet_category,
            'patient_type' => $plan->patient_type,
            'daily_calories' => $plan->daily_calories,
            'protein_target' => $plan->protein_target,
            'carbs_limit' => $plan->carbs_limit,
            'salt_limit' => $plan->salt_limit,
            'doctor_remark' => $plan->doctor_remark,
            'allowed_food_notes' => $plan->allowed_food_notes,
            'hydration_advice' => $plan->hydration_advice,
            'exercise_advice' => $plan->exercise_advice,
            'features' => $plan->features,
            'days' => collect($planDays),
            'created_at' => optional($plan->created_at)?->toIso8601String(),
            'updated_at' => optional($plan->updated_at)?->toIso8601String(),
        ];
    }
}
