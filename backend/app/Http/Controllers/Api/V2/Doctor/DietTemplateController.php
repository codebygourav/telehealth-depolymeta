<?php

namespace App\Http\Controllers\Api\V2\Doctor;

use App\Http\Controllers\Controller;
use App\Models\DietTemplateDay;
use App\Models\DietTemplateMeal;
use App\Models\DietTemplate;
use App\Services\ApiResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DietTemplateController extends Controller
{
    public function index(Request $request)
    {
        $doctor = $this->doctor($request);
        if (! $doctor && ! $this->canManageAllTemplates($request)) {
            return ApiResponseService::unauthorized();
        }

        $templates = DietTemplate::with(['days.meals'])
            ->when($doctor, fn($query) => $query->where('doctor_id', $doctor->id))
            ->when($request->boolean('active_only'), fn($query) => $query->where('is_active', true))
            ->when($request->filled('search'), fn($query) => $query->where('name', 'like', '%' . $request->string('search')->toString() . '%'))
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return ApiResponseService::paginated($templates->through(fn($template) => $this->templateData($template)));
    }

    public function store(Request $request)
    {
        $doctor = $this->doctor($request);
        if (! $doctor) {
            return ApiResponseService::unauthorized();
        }

        $data = $this->validatedData($request);

        $template = DB::transaction(function () use ($data, $doctor): DietTemplate {
            $template = DietTemplate::create([
                'doctor_id' => $doctor->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'duration_days' => $data['duration_days'] ?? 7,
                'restrictions' => $data['restrictions'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->syncDays($template, $data['days']);

            return $template;
        });

        return ApiResponseService::created(
            data: $this->templateData($template->load(['days.meals']))
        );
    }

    public function show(Request $request, string $id)
    {
        $doctor = $this->doctor($request);
        if (! $doctor) {
            return ApiResponseService::unauthorized();
        }

        $template = $this->ownedTemplate($doctor->id, $id);

        return ApiResponseService::success(
            data: $this->templateData($template->load(['days.meals']))
        );
    }

    public function update(Request $request, string $id)
    {
        $doctor = $this->doctor($request);
        if (! $doctor) {
            return ApiResponseService::unauthorized();
        }

        $template = $this->ownedTemplate($doctor->id, $id);
        $data = $this->validatedData($request, true);

        DB::transaction(function () use ($template, $data) {
            $template->update(collect($data)->only([
                'name',
                'description',
                'duration_days',
                'restrictions',
                'notes',
                'is_active',
            ])->all());

            if (array_key_exists('days', $data)) {
                $template->days()->delete();
                $this->syncDays($template, $data['days']);
            }
        });

        return ApiResponseService::success(
            data: $this->templateData($template->refresh()->load(['days.meals']))
        );
    }

    public function destroy(Request $request, string $id)
    {
        $doctor = $this->doctor($request);
        if (! $doctor) {
            return ApiResponseService::unauthorized();
        }

        $template = $this->ownedTemplate($doctor->id, $id);
        $template->delete();

        return ApiResponseService::success();
    }

    private function validatedData(Request $request, bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration_days' => ['sometimes', 'integer', 'min:1', 'max:180'],
            'restrictions' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'days' => [$required, 'array', 'min:1'],
            'days.*.day_number' => ['required', 'integer', 'min:1', 'max:31'],
            'days.*.week_day' => ['required', 'string', 'in:MONDAY,TUESDAY,WEDNESDAY,THURSDAY,FRIDAY,SATURDAY,SUNDAY'],
            'days.*.meals' => ['required', 'array', 'min:1'],
            'days.*.meals.*.meal_type' => ['required', 'string', 'in:MORNING,BREAKFAST,MID_MEAL,LUNCH,EVENING_SNACK,DINNER,NIGHT'],
            'days.*.meals.*.meal_name' => ['required', 'string', 'max:255'],
            'days.*.meals.*.instructions' => ['nullable', 'string'],
            'days.*.meals.*.meal_image' => ['nullable', 'string'],
            'days.*.meals.*.helpful_links' => ['nullable', 'array'],
            'days.*.meals.*.helpful_links.*.type' => ['nullable', 'string', 'max:50'],
            'days.*.meals.*.helpful_links.*.title' => ['nullable', 'string', 'max:255'],
            'days.*.meals.*.helpful_links.*.url' => ['nullable', 'url', 'max:2000'],
            'days.*.meals.*.calories' => ['nullable', 'integer', 'min:0'],
            'days.*.meals.*.protein_grams' => ['nullable', 'integer', 'min:0'],
            'days.*.meals.*.carbs_grams' => ['nullable', 'integer', 'min:0'],
            'days.*.meals.*.fat_grams' => ['nullable', 'integer', 'min:0'],
            'days.*.meals.*.start_time' => ['nullable', 'date_format:H:i'],
            'days.*.meals.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function syncDays(DietTemplate $template, array $days): void
    {
        foreach ($days as $dayData) {
            $day = DietTemplateDay::create([
                'diet_template_id' => $template->id,
                'day_number' => $dayData['day_number'],
                'week_day' => strtoupper($dayData['week_day']),
            ]);

            foreach ($dayData['meals'] as $mealIndex => $mealData) {
                DietTemplateMeal::create([
                    'diet_template_day_id' => $day->id,
                    'meal_type' => strtoupper($mealData['meal_type']),
                    'meal_name' => $mealData['meal_name'],
                    'instructions' => $mealData['instructions'] ?? null,
                    'meal_image' => $mealData['meal_image'] ?? null,
                    'helpful_links' => $mealData['helpful_links'] ?? null,
                    'calories' => $mealData['calories'] ?? null,
                    'protein_grams' => $mealData['protein_grams'] ?? null,
                    'carbs_grams' => $mealData['carbs_grams'] ?? null,
                    'fat_grams' => $mealData['fat_grams'] ?? null,
                    'start_time' => $mealData['start_time'] ?? null,
                    'sort_order' => $mealData['sort_order'] ?? $mealIndex,
                ]);
            }
        }
    }

    private function ownedTemplate(string $doctorId, string $id): DietTemplate
    {
        return DietTemplate::with(['days.meals'])
            ->where('doctor_id', $doctorId)
            ->where('id', $id)
            ->firstOrFail();
    }

    private function doctor(Request $request)
    {
        return $request->user()?->doctor;
    }

    private function canManageAllTemplates(Request $request): bool
    {
        $user = $request->user();

        return is_object($user)
            && method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['super_admin', 'admin', 'doctor_manager', 'receptionist']);
    }

    private function templateData(DietTemplate $template): array
    {
        return [
            'id' => $template->id,
            'doctor_id' => $template->doctor_id,
            'name' => $template->name,
            'description' => $template->description,
            'duration_days' => $template->duration_days,
            'restrictions' => $template->restrictions,
            'notes' => $template->notes,
            'is_active' => $template->is_active,
            'features' => $template->features,
            'days' => $template->days->map(function (DietTemplateDay $day) {
                return [
                    'id' => $day->id,
                    'day_number' => $day->day_number,
                    'week_day' => $day->week_day,
                    'meals' => $day->meals->map(function (DietTemplateMeal $meal) {
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
                            'start_time' => $meal->start_time,
                            'sort_order' => $meal->sort_order,
                        ];
                    })->values(),
                ];
            })->values(),
            'created_at' => optional($template->created_at)?->toIso8601String(),
            'updated_at' => optional($template->updated_at)?->toIso8601String(),
        ];
    }
}
