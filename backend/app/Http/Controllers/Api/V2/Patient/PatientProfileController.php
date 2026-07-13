<?php

namespace App\Http\Controllers\Api\V2\Patient;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Repositories\PatientProfileRepository;
use App\Services\ApiResponseService;
use Illuminate\Http\Request;

class PatientProfileController extends Controller
{
    protected $repo;

    public function __construct(PatientProfileRepository $repo)
    {
        $this->repo = $repo;
    }

    public function show(Request $request, string $user_id)
    {
        $request->headers->set('Accept', 'application/json');

        $user = $request->user();

        // Ensure logged user is patient or admin/manager
        if (! $user) {
            return ApiResponseService::unauthorized();
        }

        if (! $user->patient || $user->id !== $user_id) {
            return ApiResponseService::unauthorized();
        }

        // Get group from query param
        $group = $request->query('group');

        if (! $group || ! is_string($group)) {
            return ApiResponseService::validationError('The group field is required');
        }

        // Fetch group config from config/user_profile.php
        $groupConfig = config("user_profile.patient.$group");

        if (! $groupConfig) {
            return ApiResponseService::validationError('Invalid profile group');
        }

        // Fetch Patient
        $patient = Patient::where('user_id', $user->id)->first();

        // If patient not found, fail with 404
        if (! $patient) {
            return ApiResponseService::notFound('Patient not found');
        }

        $data = $this->repo->getPatientProfileByGroup($patient, $groupConfig, $group);

        return ApiResponseService::success(
            'responses.success',
            [
                'group' => $group,
            ],
            $data
        );
    }

    public function update(Request $request, string $user_id)
    {
        // Force JSON response
        $request->headers->set('Accept', 'application/json');

        // Get authenticated user
        $user = $request->user();

        if (! $user) {
            return ApiResponseService::unauthorized();
        }

        if (! $user->patient || $user->id !== $user_id) {
            return ApiResponseService::unauthorized();
        }

        $patient = Patient::where('user_id', $user->id)->firstOrFail();

        $group = $request->input('group')
            ?? $request->get('group')
            ?? $request->query('group');

        if (empty($group) || ! is_string($group)) {
            return ApiResponseService::validationError('The group field is required and must be a string.');
        }

        // Get group config
        $groupConfig = config("user_profile.patient.{$group}");
        if (! $groupConfig) {
            return ApiResponseService::validationError('Invalid profile group.');
        }

        // Get allowed fields from config
        $allowedFields = $groupConfig['fields'] ?? [];
        if (empty($allowedFields)) {
            return ApiResponseService::validationError('No fields configured for this group.');
        }

        // Validate data
        $data = $request->all();
        if ($request->has('data')) {
            $data = $request->input('data');
        }

        $allowedForValidation = in_array('avatar', $allowedFields)
            ? array_merge($allowedFields, ['avatar_base64'])
            : $allowedFields;

        $invalidFields = array_diff(array_keys(collect($data)->except('group')->toArray()), $allowedForValidation);
        if (! empty($invalidFields)) {
            return ApiResponseService::validationError('The following fields are not allowed: ' . implode(', ', $invalidFields));
        }

        // Validate data using config rules
        $validationRules = [];
        foreach ($allowedFields as $field) {
            if (isset($groupConfig['validation'][$field])) {
                $validationRules[$field] = $groupConfig['validation'][$field];
            }
        }

        if (! empty($validationRules)) {
            validator($data, $validationRules)->validate();
        }

        try {
            $responseData = $this->repo->updatePatientProfile($request, $patient, $groupConfig, $group);
        } catch (\Exception $e) {
            return ApiResponseService::serverError($e);
        }

        return ApiResponseService::success(
            'responses.success',
            extra: [
                'group' => $group,
            ],
            data: $responseData
        );
    }
}
