<?php

namespace App\Http\Controllers\Api\V2\Doctor;

use App\Enums\VaccinationGenderRestriction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Vaccination\VaccinationResource;
use App\Models\Vaccination;
use App\Services\ApiResponseService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VaccinationController extends Controller
{
    public function index(Request $request)
    {
        $vaccinations = Vaccination::query()
            ->when($request->boolean('active_only'), fn ($query) => $query->where('is_active', true))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('short_name', 'like', "%{$search}%")
                        ->orWhere('manufacturer', 'like', "%{$search}%")
                        ->orWhere('disease_for', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return ApiResponseService::paginated(
            $vaccinations->through(fn ($vaccination) => new VaccinationResource($vaccination))
        );
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        $vaccination = Vaccination::create($data);

        return ApiResponseService::created(
            data: new VaccinationResource($vaccination)
        );
    }

    public function show(string $id)
    {
        $vaccination = Vaccination::findOrFail($id);

        return ApiResponseService::success(
            data: new VaccinationResource($vaccination)
        );
    }

    public function update(Request $request, string $id)
    {
        $vaccination = Vaccination::findOrFail($id);

        $vaccination->update($this->validatedData($request, true, $vaccination));

        return ApiResponseService::success(
            data: new VaccinationResource($vaccination->refresh())
        );
    }

    public function destroy(string $id)
    {
        Vaccination::findOrFail($id)->delete();

        return ApiResponseService::success();
    }

    private function validatedData(Request $request, bool $isUpdate = false, ?Vaccination $existingVaccination = null): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';
        $effectiveIsMultiDose = $request->has('is_multi_dose')
            ? $request->boolean('is_multi_dose')
            : (bool) ($existingVaccination?->is_multi_dose ?? false);

        return $request->validate([
            'name' => [$required, 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'disease_for' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'side_effects' => ['nullable', 'string'],
            'contraindications' => ['nullable', 'string'],
            'precautions' => ['nullable', 'string'],
            'dosage_information' => ['nullable', 'string'],
            'is_multi_dose' => ['sometimes', 'boolean'],
            'total_doses' => ['sometimes', 'integer', 'min:1', Rule::when($effectiveIsMultiDose, ['min:2'])],
            'minimum_age_days' => ['nullable', 'integer', 'min:0'],
            'maximum_age_days' => ['nullable', 'integer', 'min:0'],
            'gender_restriction' => ['sometimes', Rule::in(VaccinationGenderRestriction::values())],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
