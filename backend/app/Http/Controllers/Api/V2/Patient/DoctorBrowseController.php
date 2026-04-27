<?php

namespace App\Http\Controllers\Api\V2\Patient;

use App\Http\Controllers\Controller;
use App\Http\Resources\Patient\{AllDoctorsResource, DoctorProfileResource, SpecialityAndSymptomsResource};
use App\Models\{Department, Doctor, Patient};
use App\Services\{ApiResponseService, WherebyService};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cache, DB};

use App\Repositories\{DoctorRepository, DepartmentRepository};

class DoctorBrowseController extends Controller
{
    protected $doctorRepository;
    protected $departmentRepository;

    public function __construct(DoctorRepository $doctorRepository, DepartmentRepository $departmentRepository)
    {
        $this->doctorRepository = $doctorRepository;
        $this->departmentRepository = $departmentRepository;
    }

    /**
     * Get all available doctors with pagination - 1 month availability
     * Optimized for performance: Use subqueries, index-friendly conditions, and minimal data selection.
     */
    public function index(Request $request)
    {
        $params = [
            'per_page' => $request->query('per_page', 10),
            'sort_by' => $request->query('sort_by', 'earliest_availability'),
        ];

        $today = today();
        $cacheKey = "browse_doctors_{$params['per_page']}_{$params['sort_by']}_{$today}";

        $availableDoctors = Cache::remember($cacheKey, 10, function () use ($params) {
            return $this->doctorRepository->getAvailableDoctors($params);
        });

        $symptomsMap = $this->doctorRepository->getSymptomsMap($availableDoctors->getCollection());

        $resources = AllDoctorsResource::collection($availableDoctors);
        $resources->each(fn($resource) => $resource->symptomsMap = $symptomsMap);

        if (!$availableDoctors->isEmpty()) {
            return ApiResponseService::success(
                responseKey: 'responses.browse_doctors.success',
                data: $resources->all()
            );
        }

        return ApiResponseService::success(
            responseKey: 'responses.browse_doctors.not_found',
            data: 'NO DOCTORS FOUND'
        );
    }

    /**
     * Get a single doctor profile by user_id for patient to view doctor profile
     * Optimized for performance: Minimize columns, eager load relationships efficiently.
     */
    public function show(Request $request, string $user_id)
    {
        $doctor = $this->doctorRepository->getDoctorProfile($user_id);

        if (!$doctor) {
            return ApiResponseService::notFound(resource: 'Doctor', module: 'patient');
        }

        $totalReviews = (int) $doctor->total_reviews;
        $averageRating = $doctor->average_rating ? round((float) $doctor->average_rating, 1) : 0;

        return ApiResponseService::success(
            responseKey: 'responses.browse_single_doctor.success',
            data: new DoctorProfileResource($doctor, $averageRating, $totalReviews)
        );
    }


    public function getDepartmentsAndSymptomsList(Request $request)
    {
        $params = $request->only(['symptom_id', 'symptom_name']);
        $departments = $this->departmentRepository->getDepartmentsWithSymptoms($params);

        if ($departments->isEmpty()) {
            return ApiResponseService::success(responseKey: 'responses.success', data: []);
        }

        $symptomsMap = $this->departmentRepository->getSymptomsMap($departments);

        $resources = SpecialityAndSymptomsResource::collection($departments);
        $resources->each(fn($resource) => $resource->symptomsMap = $symptomsMap);

        return ApiResponseService::success(
            responseKey: 'responses.success',
            extra: ['global_stamp' => \App\Services\SettingService::getGlobalStamp()],
            data: $resources
        );
    }
}