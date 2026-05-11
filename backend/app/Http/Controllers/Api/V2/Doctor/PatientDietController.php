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

    public function patientPlan(Request $request)
    {
        $patient = $request->user()?->patient;
        if (! $patient) {
            return ApiResponseService::unauthorized();
        }

        $plan = PatientDietPlan::with(['days.meals'])
            ->where('patient_id', $patient->id)
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
        if (! $patient) {
            return ApiResponseService::unauthorized();
        }

        $data = $request->validate([
            'status' => ['nullable', Rule::in(['completed', 'missed', 'skipped'])],
            'patient_notes' => ['nullable', 'string'],
        ]);

        $meal = PatientDietPlanMeal::query()
            ->whereHas('planDay.plan', fn ($query) => $query->where('patient_id', $patient->id))
            ->findOrFail($mealId);

        $status = $data['status'] ?? 'completed';
        $meal->update([
            'status' => $status,
            'patient_notes' => $data['patient_notes'] ?? null,
            'completed_at' => $status === 'completed' ? now() : null,
        ]);

        return ApiResponseService::success(
            data: [
                'id' => $meal->id,
                'status' => $meal->status,
                'patient_notes' => $meal->patient_notes,
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
                            'patient_notes' => $meal->patient_notes,
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
