<?php

namespace App\Http\Controllers\Api\V2\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Resources\Vaccination\VaccinationTemplateResource;
use App\Models\Doctor;
use App\Models\VaccinationTemplate;
use App\Models\VaccinationTemplateItem;
use App\Services\ApiResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VaccinationTemplateController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $doctor = $user->doctor;
        if (! $doctor) {
            return ApiResponseService::unauthorized();
        }

        $templates = VaccinationTemplate::with(['items.vaccination', 'program'])
            ->where(fn($query) => $query->where('doctor_id', $doctor->id)->orWhereNull('doctor_id'))
            ->when($request->filled('vaccination_program_id'), fn($query) => $query->where('vaccination_program_id', $request->string('vaccination_program_id')->toString()))
            ->when($request->boolean('active_only'), fn($query) => $query->where('is_active', true))
            ->when($request->filled('search'), fn($query) => $query->where('name', 'like', '%' . $request->string('search')->toString() . '%'))
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return ApiResponseService::paginated($templates->through(fn($template) => new VaccinationTemplateResource($template)));
    }

    public function store(Request $request)
    {
        $doctor = Doctor::find($request->user()->doctor?->id);
        if (! $doctor) {
            return ApiResponseService::unauthorized();
        }

        $data = $this->validatedData($request);

        $template = DB::transaction(function () use ($data, $doctor) {
            $template = VaccinationTemplate::create([
                'vaccination_program_id' => $data['vaccination_program_id'],
                'doctor_id' => $doctor->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->syncItems($template, $data['items']);

            return $template;
        });

        return ApiResponseService::created(
            data: new VaccinationTemplateResource($template->load(['items.vaccination', 'program']))
        );
    }

    public function show(Request $request, string $id)
    {
        $template = $this->ownedTemplate($request, $id);
        if (! $template) {
            return ApiResponseService::unauthorized();
        }

        return ApiResponseService::success(
            data: new VaccinationTemplateResource($template->load(['items.vaccination', 'program']))
        );
    }

    public function update(Request $request, string $id)
    {
        $template = $this->ownedTemplate($request, $id);
        if (! $template) {
            return ApiResponseService::unauthorized();
        }

        $doctor = $this->doctor($request);
        if ($template->doctor_id !== $doctor?->id) {
            return ApiResponseService::unauthorized();
        }

        $data = $this->validatedData($request, true);

        DB::transaction(function () use ($template, $data) {
            $template->update(collect($data)->only(['vaccination_program_id', 'name', 'description', 'is_active'])->all());

            if (array_key_exists('items', $data)) {
                $template->items()->delete();
                $this->syncItems($template, $data['items']);
            }
        });

        return ApiResponseService::success(
            data: new VaccinationTemplateResource($template->refresh()->load(['items.vaccination', 'program']))
        );
    }

    public function destroy(Request $request, string $id)
    {
        $template = $this->ownedTemplate($request, $id);
        if (! $template) {
            return ApiResponseService::unauthorized();
        }

        $doctor = $this->doctor($request);
        if ($template->doctor_id !== $doctor?->id) {
            return ApiResponseService::unauthorized();
        }

        $template->delete();

        return ApiResponseService::success();
    }

    public function clone(Request $request, string $id)
    {
        $template = $this->ownedTemplate($request, $id);
        if (! $template) {
            return ApiResponseService::unauthorized();
        }

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $clone = DB::transaction(function () use ($template, $data) {
            $clone = VaccinationTemplate::create([
                'vaccination_program_id' => $template->vaccination_program_id,
                'doctor_id' => $template->doctor_id,
                'name' => $data['name'] ?? "{$template->name} Copy",
                'description' => $template->description,
                'is_active' => $template->is_active,
            ]);

            foreach ($template->items as $item) {
                VaccinationTemplateItem::create([
                    'vaccination_template_id' => $clone->id,
                    'vaccination_id' => $item->vaccination_id,
                    'set_name' => $item->set_name,
                    'set_description' => $item->set_description,
                    'set_sort_order' => $item->set_sort_order ?? 0,
                    'dose_no' => $item->dose_no,
                    'depends_on_previous_dose' => $item->depends_on_previous_dose,
                    'timing_type' => $item->effectiveTimingType(),
                    'interval_days' => $item->interval_days ?? 0,
                    'interval_months' => $item->interval_months ?? 0,
                    'interval_value' => $item->effectiveIntervalValue(),
                    'interval_unit' => $item->effectiveIntervalUnit(),
                    'doctor_manual_date' => $item->doctor_manual_date,
                    'minimum_age_days' => $item->minimum_age_days,
                    'maximum_age_days' => $item->maximum_age_days,
                    'recommended_age_label' => $item->recommended_age_label,
                    'due_after_days' => $item->due_after_days,
                    'due_after_months' => $item->due_after_months ?? 0,
                    'offset_value' => $item->effectiveOffsetValue(),
                    'offset_unit' => $item->effectiveOffsetUnit(),
                    'sort_order' => $item->sort_order,
                ]);
            }

            return $clone;
        });

        return ApiResponseService::created(
            data: new VaccinationTemplateResource($clone->load(['items.vaccination', 'program']))
        );
    }

    private function validatedData(Request $request, bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:255'],
            'vaccination_program_id' => [$required, 'exists:vaccination_programs,id'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'items' => [$required, 'array', 'min:1'],
            'items.*.vaccination_id' => ['required', 'exists:vaccinations,id'],
            'items.*.set_name' => ['nullable', 'string', 'max:255'],
            'items.*.set_description' => ['nullable', 'string'],
            'items.*.set_sort_order' => ['nullable', 'integer', 'min:0'],
            'items.*.dose_no' => ['nullable', 'integer', 'min:1'],
            'items.*.depends_on_previous_dose' => ['sometimes', 'boolean'],
            'items.*.timing_type' => ['nullable', 'string', 'in:base_date,previous_dose,doctor_manual_date'],
            'items.*.interval_days' => ['nullable', 'integer', 'min:0'],
            'items.*.interval_months' => ['nullable', 'integer', 'min:0'],
            'items.*.interval_value' => ['nullable', 'integer', 'min:0'],
            'items.*.interval_unit' => ['nullable', 'string', 'in:days,weeks,months,years'],
            'items.*.minimum_age_days' => ['nullable', 'integer', 'min:0'],
            'items.*.maximum_age_days' => ['nullable', 'integer', 'min:0'],
            'items.*.recommended_age_label' => ['nullable', 'string', 'max:255'],
            'items.*.due_after_days' => ['nullable', 'integer', 'min:0'],
            'items.*.due_after_months' => ['nullable', 'integer', 'min:0'],
            'items.*.offset_value' => ['nullable', 'integer', 'min:0'],
            'items.*.offset_unit' => ['nullable', 'string', 'in:days,weeks,months,years'],
            'items.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function syncItems(VaccinationTemplate $template, array $items): void
    {
        foreach (array_values($items) as $index => $item) {
            $timingType = $item['timing_type'] ?? (($item['depends_on_previous_dose'] ?? false) ? 'previous_dose' : 'base_date');
            $offsetValue = (int) ($item['offset_value'] ?? 0);
            $offsetUnit = $item['offset_unit'] ?? 'days';
            $intervalValue = (int) ($item['interval_value'] ?? 0);
            $intervalUnit = $item['interval_unit'] ?? 'days';
            [$dueAfterDays, $dueAfterMonths] = $this->legacyValueColumns($offsetValue, $offsetUnit);
            [$intervalDays, $intervalMonths] = $this->legacyValueColumns($intervalValue, $intervalUnit);

            VaccinationTemplateItem::create([
                'vaccination_template_id' => $template->id,
                'vaccination_id' => $item['vaccination_id'],
                'set_name' => $item['set_name'] ?? null,
                'set_description' => $item['set_description'] ?? null,
                'set_sort_order' => $item['set_sort_order'] ?? 0,
                'dose_no' => $item['dose_no'] ?? 1,
                'depends_on_previous_dose' => $timingType === 'previous_dose',
                'timing_type' => $timingType,
                'interval_days' => $intervalDays,
                'interval_months' => $intervalMonths,
                'interval_value' => $intervalValue,
                'interval_unit' => $intervalUnit,
                'doctor_manual_date' => $timingType === 'doctor_manual_date',
                'minimum_age_days' => $item['minimum_age_days'] ?? null,
                'maximum_age_days' => $item['maximum_age_days'] ?? null,
                'recommended_age_label' => $item['recommended_age_label'] ?? null,
                'due_after_days' => $dueAfterDays,
                'due_after_months' => $dueAfterMonths,
                'offset_value' => $offsetValue,
                'offset_unit' => $offsetUnit,
                'sort_order' => $item['sort_order'] ?? $index,
            ]);
        }
    }

    private function legacyValueColumns(int $value, string $unit): array
    {
        return match ($unit) {
            'weeks' => [$value * 7, 0],
            'months' => [0, $value],
            'years' => [0, $value * 12],
            default => [$value, 0],
        };
    }

    private function ownedTemplate(Request $request, string $id): ?VaccinationTemplate
    {
        $doctor = $this->doctor($request);
        if (! $doctor) {
            return null;
        }

        return VaccinationTemplate::with(['items', 'program'])
            ->where(fn($query) => $query->where('doctor_id', $doctor->id)->orWhereNull('doctor_id'))
            ->findOrFail($id);
    }

    private function doctor(Request $request)
    {
        return $request->user()?->doctor;
    }
}
