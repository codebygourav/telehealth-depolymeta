<?php

namespace App\Http\Controllers\Api\V2\Patient;

use App\Http\Controllers\Controller;
use App\Http\Resources\Patient\{HomeResource, SpecialityAndSymptomsResource};
use App\Http\Resources\Reviews\DoctorReviewResource;
use App\Models\{Advertisement, Department, Doctor, DoctorReview, Symptom};
use App\Services\ApiResponseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use App\Models\Appointment;
use App\Enums\AppointmentStatus;

use App\Repositories\{DoctorRepository, DepartmentRepository};

class PatientHomeController extends Controller
{
    protected $doctorRepository;
    protected $departmentRepository;

    public function __construct(DoctorRepository $doctorRepository, DepartmentRepository $departmentRepository)
    {
        $this->doctorRepository = $doctorRepository;
        $this->departmentRepository = $departmentRepository;
    }

    public function index()
    {
        $now = Carbon::now();
        $today = $now->toDateString();
        $currentTime = $now->format('H:i:s');
        $oneWeekLater = $now->copy()->addWeek()->toDateString();

        // ---------------- Available Doctors (Smart Ranking) ----------------
        $availableDoctors = Cache::remember('home_doctors', 60, function () {
            return $this->doctorRepository->getAvailableDoctorsWithSmartRanking(5);
        });

        // ---------------- Advertisements ----------------
        $advertisements = Cache::remember('home_advertisements', 60, function () {
            return Advertisement::select('id', 'title', 'description', 'link')
                ->where('is_active', true)
                ->orderByDesc('updated_at')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();
        });


        // ---------------- Departments + Symptoms ----------------
        $specialityData = Cache::remember('home_speciality_symptoms', 60, function () {
            $departments = $this->departmentRepository->getDepartmentsWithSymptoms(['limit' => 5]);
            $symptoms = $this->departmentRepository->getSymptomsMap($departments);

            return compact('departments', 'symptoms');
        });

        $departments = $specialityData['departments'];
        $symptoms = $specialityData['symptoms'];

        $patientReviews = Cache::remember('home_patient_reviews', 60, function () {
            return $this->doctorRepository->getFeaturedReviewsWithStats(5);
        });

        // ---------------- Build Response Resource ----------------
        $specialities = SpecialityAndSymptomsResource::collection($departments);
        $specialities->each(function ($resource) use ($symptoms) {
            $resource->symptomsMap = $symptoms;
        });

        // ---------------- Upcoming Appointments ----------------
        $patient = request()->user()?->patient;
        $upcomingAppointments = collect();

        if ($patient) {
            $upcomingAppointments = Appointment::with(['doctor.departments', 'availability', 'videoConsultation'])
                ->withoutTestDoctors()
                ->where('patient_id', $patient->id)
                ->whereIn('status', [
                    AppointmentStatus::CONFIRMED->value,
                    AppointmentStatus::RESCHEDULED->value,
                ])
                ->whereBetween('appointment_date', [$today, $oneWeekLater])
                ->where(function ($query) use ($today, $currentTime) {
                    $query->where('appointment_date', '>', $today)
                        ->orWhere(function ($q) use ($today, $currentTime) {
                            $q->where('appointment_date', $today)
                                ->where('appointment_time', '>=', $currentTime);
                        });
                })
                ->orderBy('appointment_date', 'asc')
                ->orderBy('appointment_time', 'asc')
                ->limit(2)
                ->get();
        }

        $homeResource = new HomeResource([
            'advertisements' => $advertisements,
            'available_doctors' => $availableDoctors,
            'patient_reviews' => DoctorReviewResource::collection($patientReviews),
            'speciality_symptoms' => $specialities,
            'upcoming_appointments' => $upcomingAppointments,
        ]);

        return ApiResponseService::success(
            'responses.patient_home.dashboard',
            data: $homeResource->toArray(request())
        );
    }
}