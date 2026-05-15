<?php

namespace App\Http\Controllers\Api\V2\Doctor;

use App\Enums\VaccinationProgramTargetType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Vaccination\VaccinationProgramResource;
use App\Models\VaccinationProgram;
use App\Services\ApiResponseService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VaccinationProgramController extends Controller
{
    public function index(Request $request)
    {
        $programs = VaccinationProgram::query()
            ->when($request->boolean('active_only'), fn ($query) => $query->where('is_active', true))
            ->when($request->filled('target_type'), fn ($query) => $query->where('target_type', $request->string('target_type')->toString()))
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%' . $request->string('search')->toString() . '%'))
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return ApiResponseService::paginated($programs->through(fn ($program) => new VaccinationProgramResource($program)));
    }

    public function store(Request $request)
    {
        $program = VaccinationProgram::create($this->validatedData($request));

        return ApiResponseService::created(data: new VaccinationProgramResource($program));
    }

    public function show(string $id)
    {
        return ApiResponseService::success(data: new VaccinationProgramResource(VaccinationProgram::findOrFail($id)));
    }

    public function update(Request $request, string $id)
    {
        $program = VaccinationProgram::findOrFail($id);
        $program->update($this->validatedData($request, true, $program));

        return ApiResponseService::success(data: new VaccinationProgramResource($program->refresh()));
    }

    public function destroy(string $id)
    {
        VaccinationProgram::findOrFail($id)->delete();

        return ApiResponseService::success();
    }

    private function validatedData(Request $request, bool $isUpdate = false, ?VaccinationProgram $program = null): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('vaccination_programs', 'slug')->ignore($program?->id),
            ],
            'description' => ['nullable', 'string'],
            'target_type' => [$required, Rule::in(VaccinationProgramTargetType::values())],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
