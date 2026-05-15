<?php

namespace App\Http\Controllers\Api\V2\Patient;

use App\Enums\PatientProfileType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Vaccination\PatientProfileResource;
use App\Models\PatientProfile;
use App\Services\ApiResponseService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PatientProfileMemberController extends Controller
{
    public function index(Request $request)
    {
        $patient = $request->user()?->patient;
        if (! $patient) {
            return ApiResponseService::unauthorized();
        }

        $profiles = $patient->profiles()
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return ApiResponseService::paginated($profiles->through(fn ($profile) => new PatientProfileResource($profile)));
    }

    public function store(Request $request)
    {
        $patient = $request->user()?->patient;
        if (! $patient) {
            return ApiResponseService::unauthorized();
        }

        $profile = $patient->profiles()->create($this->validatedData($request));

        return ApiResponseService::created(data: new PatientProfileResource($profile));
    }

    public function show(Request $request, string $id)
    {
        $profile = $this->ownedProfile($request, $id);
        if (! $profile) {
            return ApiResponseService::unauthorized();
        }

        return ApiResponseService::success(data: new PatientProfileResource($profile));
    }

    public function update(Request $request, string $id)
    {
        $profile = $this->ownedProfile($request, $id);
        if (! $profile) {
            return ApiResponseService::unauthorized();
        }

        $profile->update($this->validatedData($request, true));

        return ApiResponseService::success(data: new PatientProfileResource($profile->refresh()));
    }

    public function destroy(Request $request, string $id)
    {
        $profile = $this->ownedProfile($request, $id);
        if (! $profile) {
            return ApiResponseService::unauthorized();
        }

        $profile->delete();

        return ApiResponseService::success();
    }

    private function validatedData(Request $request, bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:255'],
            'profile_type' => [$required, Rule::in(PatientProfileType::values())],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'pregnancy_due_date' => ['nullable', 'date'],
            'blood_group' => ['nullable', 'string', 'max:20'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'height' => ['nullable', 'numeric', 'min:0'],
            'is_primary' => ['sometimes', 'boolean'],
        ]);
    }

    private function ownedProfile(Request $request, string $id): ?PatientProfile
    {
        $patient = $request->user()?->patient;
        if (! $patient) {
            return null;
        }

        return PatientProfile::where('patient_id', $patient->id)->findOrFail($id);
    }
}
