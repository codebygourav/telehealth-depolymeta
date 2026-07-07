<?php

namespace App\Http\Controllers\Api\V2\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Resources\Reviews\DoctorReviewResource;
use App\Models\{Appointment, Doctor, DoctorReview, Patient};
use App\Services\{ApiResponseService, WherebyService};
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Enums\AppointmentStatus;

class DoctorHomeController extends Controller
{
    protected WherebyService $wherebyService;

    protected function getStatusLabel(AppointmentStatus $status, Carbon $appointmentDate, Carbon $appointmentTime, Carbon $now): string
    {
        if (AppointmentStatus::equals($status, AppointmentStatus::CANCELLED)) {
            return AppointmentStatus::CANCELLED->label();
        }
        if (AppointmentStatus::equals($status, AppointmentStatus::CONFIRMED)) {
            return AppointmentStatus::CONFIRMED->label();
        }
        if (AppointmentStatus::equals($status, AppointmentStatus::RESCHEDULED)) {
            return AppointmentStatus::RESCHEDULED->label();
        }
        if (AppointmentStatus::equals($status, AppointmentStatus::FAILED)) {
            return AppointmentStatus::FAILED->label();
        }

        if (AppointmentStatus::equals($status, AppointmentStatus::COMPLETED)) {
            return AppointmentStatus::COMPLETED->label();
        }

        $appointmentDateTime = $appointmentDate
            ->copy()
            ->setTimeFromTimeString($appointmentTime->format('H:i:s'));

        if ($appointmentDateTime->lt($now) && AppointmentStatus::equals($status, AppointmentStatus::CONFIRMED)) {
            return AppointmentStatus::COMPLETED->label();
        }

        if ($appointmentDate->isToday()) {
            $joinWindow = $appointmentDateTime->copy()->subMinutes(30);
            if ($now->between($joinWindow, $appointmentDateTime->copy()->addHour())) {
                return 'Ready to join';
            }

            return 'Today';
        }

        $enumStatus = $status instanceof AppointmentStatus
            ? $status
            : AppointmentStatus::tryFrom($status);

        return $enumStatus ? $enumStatus->label() : 'Scheduled';
    }
    public function __construct(WherebyService $wherebyService)
    {
        $this->wherebyService = $wherebyService;
    }

    /**
     * Get doctor home screen data
     * Returns grouped data for all sections
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user->doctor) {
            return ApiResponseService::notFound();
        }

        $doctor = $user->doctor;
        $doctorId = $doctor->id;
        $today = Carbon::today();

        // Load doctor with user relationship
        $doctor->load('user');

        // Summary Cards - Optimized: Use whereIn instead of whereHas for better performance

        $summary = [
            'todays_appointments' => Appointment::where('doctor_id', $doctorId)
                ->whereIn('status', [
                    AppointmentStatus::CONFIRMED->value,
                    AppointmentStatus::COMPLETED->value,
                    AppointmentStatus::RESCHEDULED->value,
                ])
                ->whereDate('appointment_date', $today)
                ->where(function ($query) {
                    $currentTime = Carbon::now(config('app.timezone'))->format('H:i:s');
                    $query->whereTime('appointment_end_time', '>=', $currentTime)
                        ->orWhere(function ($sub) use ($currentTime) {
                            $sub->whereNull('appointment_end_time')
                                ->whereTime('appointment_time', '>=', $currentTime);
                        });
                })
                ->count(),
            'upcoming_appointments' => Appointment::where('doctor_id', $doctorId)
                ->where(function ($query) use ($today) {
                    $query->whereDate('appointment_date', '>', $today)
                        ->orWhere(function ($subQuery) use ($today) {
                            // Include appointments with start_time or end_time after today
                            $subQuery->whereDate('appointment_date', $today)
                                ->whereHas('availability', function ($q) use ($today) {
                                    $q->where(function ($qq) use ($today) {
                                        $qq->whereDate('end_time', '>=', $today)
                                            ->orWhereNull('end_time');
                                    });
                                });
                        });
                })
                ->whereIn('status', [
                    AppointmentStatus::CONFIRMED->value,
                    AppointmentStatus::COMPLETED->value,
                    AppointmentStatus::RESCHEDULED->value,
                ])
                ->count(),
            'cancelled_appointments' => Appointment::where('doctor_id', $doctorId)
                ->where('status', 'cancelled')
                ->whereIn('status', [
                    AppointmentStatus::CANCELLED->value,
                ])
                ->count(),
            'average_review_score' => round(DoctorReview::where('doctor_id', $doctorId)
                ->where('is_active', true)
                ->avg('rating') ?? 0, 1),
            'total_reviews' => DoctorReview::where('doctor_id', $doctorId)
                ->where('is_active', true)
                ->count(),
        ];

        // Today's Appointments - Optimized: Use whereIn instead of whereHas
        $todaysAppointments = Appointment::with([
            'patient:id,user_id,first_name,last_name,source,create_user_account',
            'patient.user:id,name,email,phone', // avatar is accessed via InteractsWithModuleDocuments trait
            'videoConsultation:id,appointment_id,room_url,host_url,participate_url,room_id,status',
        ])
            ->where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $today)
            ->where(function ($query) {
                $currentTime = Carbon::now(config('app.timezone'))->format('H:i:s');
                $query->whereTime('appointment_end_time', '>=', $currentTime)
                    ->orWhere(function ($sub) use ($currentTime) {
                        $sub->whereNull('appointment_end_time')
                            ->whereTime('appointment_time', '>=', $currentTime);
                    });
            })
            ->whereIn('status', [
                AppointmentStatus::CONFIRMED->value,
                AppointmentStatus::COMPLETED->value,
                AppointmentStatus::RESCHEDULED->value,
            ])
            ->orderBy('appointment_time', 'asc')
            ->limit(3)
            ->get()
            ->map(function ($appointment) {
                $now = now();
                $appointmentDate = $appointment->appointment_date;
                $appointmentTime = $appointment->appointment_time ? Carbon::parse($appointment->appointment_time) : null;
                $status = $appointment->status;

                $statusValue = $status instanceof \BackedEnum ? $status->value : $status;

                // Combine date and time for accurate comparison
                $appointmentStart = $appointmentDate && $appointmentTime
                    ? $appointmentDate->copy()->setTimeFromTimeString($appointmentTime->format('H:i:s'))
                    : null;

                $appointmentEnd = null;
                if ($appointmentStart) {
                    $appointmentEnd = $appointment->appointment_end_time
                        ? $appointmentDate->copy()->setTimeFromTimeString(Carbon::parse($appointment->appointment_end_time)->format('H:i:s'))
                        : $appointmentStart->copy()->addMinutes(30);
                }

                // Allow joining 60 minutes before start until the end time
                $isReadyToJoin = $appointmentStart && in_array($statusValue, [
                    AppointmentStatus::CONFIRMED->value,
                    AppointmentStatus::COMPLETED->value,
                    AppointmentStatus::RESCHEDULED->value
                ])
                    && $now->isBetween($appointmentStart->copy()->subMinutes(60), $appointmentEnd);

                return [
                    'id' => $appointment->id,
                    'patient_name' => $appointment->patient
                        ? $appointment->patient->first_name . ' ' . $appointment->patient->last_name
                        : 'Unknown',
                    'patient_image' => storage_url($appointment->patient->avatar) ?? null,
                    'time' => $appointmentTime ? $appointmentTime->format('g:i A') : 'N/A',
                    'date' => $appointmentDate ? $appointmentDate->format('D, M d') : 'N/A',
                    'consultation_type' => $appointment->consultation_type,
                    'status' => $status,
                    'status_label' => ($appointmentDate && $appointmentTime)
                        ? $this->getStatusLabel($status, $appointmentDate, $appointmentTime, $now)
                        : 'Scheduled',
                    'call_now' => $isReadyToJoin,
                    'join_url' => $isReadyToJoin && isset($appointment->videoConsultation)
                        ? ($appointment->videoConsultation->host_url ?? null)
                        : null,

                ];
            });

        // Upcoming Appointments - Optimized: Use whereIn instead of whereHas
        $upcomingAppointments = Appointment::with([
            'patient:id,user_id,first_name,last_name,source,create_user_account',
            'patient.user:id,name,email,phone', // avatar is accessed via InteractsWithModuleDocuments trait
        ])
            ->where('doctor_id', $doctorId)
            ->whereDate('appointment_date', '>', $today)
            ->whereIn('status', [
                AppointmentStatus::CONFIRMED->value,
                AppointmentStatus::COMPLETED->value,
                AppointmentStatus::RESCHEDULED->value,
            ])
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->limit(3)
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'patient_name' => $appointment->patient
                        ? $appointment->patient->first_name . ' ' . $appointment->patient->last_name
                        : 'Unknown',
                    'patient_image' => storage_url($appointment->patient->avatar) ?? null,
                    'date' => Carbon::parse($appointment->appointment_date)->format('D, M d'),
                    'time' => Carbon::parse($appointment->appointment_time)->format('g:i A'),
                    'consultation_type' => $appointment->consultation_type,
                    'status' => $appointment->status,
                ];
            });

        // Patient Reviews - Only for this doctor - Optimized eager loading
        $doctorReviews = DoctorReview::with([
            'patient:id,user_id,first_name,last_name,date_of_birth',
            'patient.user:id,name', // avatar is accessed via InteractsWithModuleDocuments trait
            'doctor:id,user_id,first_name,last_name',
            'doctor.user:id,name',
        ])
            ->where('doctor_id', $doctorId)
            ->where('is_active', true)
            ->orderBy('is_featured', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Doctor Profile Info
        $doctorProfile = [
            'id' => $doctor->id,
            'name' => $doctor->user->name ?? $doctor->first_name . ' ' . $doctor->last_name,
            'first_name' => $doctor->first_name,
            'last_name' => $doctor->last_name,
            'slug' => $doctor->slug,
            'avatar' => storage_url($doctor->avatar) ?? null,
            'location' => $doctor->address_line1 . ($doctor->address_line2 ? ', ' . $doctor->address_line2 : '') ?? 'Not set',
        ];

        return ApiResponseService::success(responseKey: 'responses.success', data: array_merge($doctorProfile, [
            'doctor' => $doctorProfile,
            'summary' => $summary,
            'todays_appointments' => $todaysAppointments,
            'upcoming_appointments' => $upcomingAppointments,
            'doctor_reviews' => DoctorReviewResource::collection($doctorReviews),
        ]));
    }
}