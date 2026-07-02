<?php

namespace App\Http\Controllers\Api\V2\Doctor;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Doctor\{DoctorAppoinments, DoctorAvailabilityResource, DoctorProfileResource, DoctorScheduleResource, PatientReportResource, PatientWithReportsResource, GetProfileResource};
use App\Http\Resources\Reviews\DoctorReviewResource;
use App\Models\{Appointment, Doctor, DoctorAvailability, ExternalBooking, Patient, MedicalReport};
use App\Repositories\DoctorProfileRepository;
use App\Services\{ApiResponseService, SlotCapacityService, WherebyService};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DoctorController extends Controller
{
    protected WherebyService $wherebyService;

    public function __construct(WherebyService $wherebyService)
    {
        $this->wherebyService = $wherebyService;
    }

    public function getProfile(Request $request, DoctorProfileRepository $repo)
    {
        $user = $request->user();
        if (! $user->doctor) {
            return ApiResponseService::notFound();
        }

        $doctor = $user->doctor;

        return ApiResponseService::success(
            responseKey: 'responses.success',
            data: new GetProfileResource($doctor)
        );
    }


    public function appointments(Request $request)
    {
        $user = $request->user();

        if (! $user->doctor) {
            return ApiResponseService::notFound();
        }

        $doctor = $user->doctor;

        $appointments = Appointment::with([
            'patient:id,user_id,first_name,last_name,slug,address,pincode,area,city,state,landmark',
            'availability',
        ])
            ->where('doctor_id', $doctor->id)
            ->whereNotIn('status', ['pending', 'no_show'])
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->paginate(10); // Set pagination count as 2

        $appointments->setCollection(
            DoctorAppoinments::collection($appointments->getCollection())->collection
        );

        return ApiResponseService::paginated(
            paginated: $appointments,
            responseKey: 'responses.success'
        );
    }

    public function getOwnSlotForReschdule(Request $request, string $id)
    {
        $query = Doctor::query()->where('id', $id);

        if (! $request->user()?->doctor) {
            $query->visibleInMobileApp($request->user()?->patient, includeBookedHiddenDoctors: true);
        }

        $doctor = $query->first();

        if (! $doctor) {
            return ApiResponseService::notFound();
        }

        $service = app(\App\Services\DoctorAvailabilityService::class);

        $doctor->loadMissing('availabilities.overrides');

        $slots = $service->expandSlots(
            $doctor->availabilities,
            Carbon::today(),
            Carbon::today()->addDays(15)
        );

        $availability = $service->groupSlotsByDate($slots)->map(fn($group) => [
            'date' => $group['date'],
            'slots' => DoctorAvailabilityResource::collection($group['slots'])->resolve($request),
        ]);

        return ApiResponseService::success(
            data: $availability,
            responseKey: 'responses.success'
        );
    }


    public function getPatientReports(Request $request)
    {
        $user = $request->user();
        if (! $user->doctor) {
            return ApiResponseService::notFound();
        }

        $doctor = $user->doctor;
        $filter = $request->get('filter');

        $perPage = ($filter === 'all') ? 10 : 10;

        $patients = Patient::whereHas('medicalReports', function ($q) use ($doctor) {
            $q->where('doctor_id', $doctor->id)
                ->whereNotNull('appointment_id')
                ->where('is_shared', true);
        })
            ->withCount(['medicalReports as total_reports_count' => function ($q) use ($doctor) {
                $q->where('doctor_id', $doctor->id)
                    ->whereNotNull('appointment_id')
                    ->where('is_shared', true);
            }])
            ->with(['user', 'medicalReports' => function ($q) use ($doctor) {
                $q->where('doctor_id', $doctor->id)
                    ->whereNotNull('appointment_id')
                    ->where('is_shared', true)
                    ->orderByDesc('created_at');
            }])
            ->paginate($perPage);

        // Limit to 2 reports only if it is NOT filter=all
        if ($filter !== 'all') {
            $patients->getCollection()->each(function ($patient) {
                if ($patient->medicalReports) {
                    $patient->setRelation('medicalReports', $patient->medicalReports->take(2));
                }
            });
        }

        $patients->setCollection(
            PatientWithReportsResource::collection($patients->getCollection())->collection
        );

        return ApiResponseService::paginated(
            paginated: $patients,
            responseKey: 'responses.success'
        );
    }

    public function update(
        Request $request,
        string $user_id,
        DoctorProfileRepository $repo
    ) {

        // Log::info('update doctor profile', ['request' => $request->all()]);
        $user = $request->user();
        $isOwner = $user && $user->id === $user_id;
        $isAdmin = $user && ($user->hasRole('super_admin') || $user->doctor);

        if (! $user || (! $isOwner && ! $isAdmin)) {
            return ApiResponseService::unauthorized();
        }

        $doctor = Doctor::where('user_id', $user_id)->firstOrFail();

        $group = $request->input('group');

        if (! $group || ! is_string($group)) {
            return ApiResponseService::validationError('The group field is required and must be a string.');
        }

        $groupConfig = config("user_profile.doctor.$group");

        if (! $groupConfig) {
            return ApiResponseService::validationError('Invalid profile group.');
        }

        $allowedFields = $groupConfig['fields'] ?? [];
        if (! $allowedFields) {
            return ApiResponseService::validationError('No fields configured for this group.');
        }

        // Validation
        $data = collect($request->all())->except('group')->toArray();

        $allowedForValidation = in_array('avatar', $allowedFields)
            ? array_merge($allowedFields, ['avatar_base64'])
            : $allowedFields;


        // Allow special operation fields
        $allowedForValidation = array_merge($allowedForValidation, [
            'remove_item_id',
        ]);

        $invalid = array_diff(array_keys($data), $allowedForValidation);
        // Log::info('update doctor $invalidprofile data', ['data' => $invalid]);
        if ($invalid) {
            return ApiResponseService::validationError('The following fields are not allowed: ' . implode(', ', $invalid));
        }

        $rules = collect($allowedFields)
            ->mapWithKeys(
                fn($field) => isset($groupConfig['validation'][$field])
                    ? [$field => $groupConfig['validation'][$field]]
                    : []
            )->toArray();

        if ($rules) {
            validator($data, $rules)->validate();
        }

        // Call repository
        try {
            $responseData = $repo->updateDoctorProfile($request, $doctor, $groupConfig, $group);
        } catch (\Throwable $e) {
            return ApiResponseService::serverError($e);
        }

        return ApiResponseService::success(
            'responses.doctor.updated',
            ['group' => $group],
            $responseData
        );
    }

    public function show(
        Request $request,
        string $user_id,
        DoctorProfileRepository $repo
    ) {
        $request->headers->set('Accept', 'application/json');

        $user = $request->user();
        $isOwner = $user && $user->id === $user_id;
        $isAdmin = $user && ($user->hasRole('super_admin') || $user->doctor);

        if (! $user || (! $isOwner && ! $isAdmin)) {
            return ApiResponseService::unauthorized();
        }

        $doctor = Doctor::where('user_id', $user_id)->firstOrFail();

        $group = $request->query('group');

        if (! $group || ! is_string($group)) {
            return ApiResponseService::validationError('The group field is required and must be a string.');
        }

        $groupConfig = config("user_profile.doctor.$group");

        if (! $groupConfig) {
            return ApiResponseService::validationError('Invalid profile group.');
        }

        $responseData = $repo->getDoctorProfileByGroup($doctor, $groupConfig, $group);

        return ApiResponseService::success(
            'responses.success',
            ['group' => $group],
            $responseData
        );
    }

    public function schedule(Request $request)
    {
        $user = $request->user();

        if (! $user->doctor) {
            return ApiResponseService::notFound();
        }

        $doctor = $user->doctor;
        $doctorId = $doctor->id;

        $filter = $request->get('filter', 'day');
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $selectedDate = Carbon::parse($date);

        // Validate filter
        if (! in_array($filter, ['day', 'week', 'month'])) {
            return ApiResponseService::validationError('Invalid filter. Must be: day, week, or month.');
        }

        $availabilities = collect();

        switch ($filter) {
            case 'day':
                $availabilities = $this->getDaySchedule($doctorId, $selectedDate);
                break;

            case 'week':
                $availabilities = $this->getWeekSchedule($doctorId, $selectedDate);
                break;

            case 'month':
                $availabilities = $this->getMonthSchedule($doctorId, $selectedDate);
                break;
        }

        // Format response based on filter
        $formattedData = $this->formatScheduleResponse($availabilities, $filter, $selectedDate, $doctorId);

        return ApiResponseService::success(
            responseKey: 'responses.success',
            extra: [
                'filter' => $filter,
            ],
            data: new DoctorScheduleResource($formattedData)
        );
    }

    private function getDaySchedule(string $doctorId, Carbon $date): \Illuminate\Support\Collection
    {
        $dateStr = $date->format('Y-m-d');
        $dayName = strtolower($date->format('l'));

        // 1. Recurring: ONLY check recurring start/end dates. Day is from recurring_start_date.
        $recurring = DoctorAvailability::where('doctor_id', $doctorId)
            ->where('is_recurring', true)
            ->with('overrides')
            ->where(function ($q) use ($dateStr) {
                $q->whereNull('recurring_start_date')
                    ->orWhere('recurring_start_date', '<=', $dateStr);
            })
            ->where(function ($q) use ($dateStr) {
                $q->whereNull('recurring_end_date')
                    ->orWhere('recurring_end_date', '>=', $dateStr);
            })
            ->get()
            ->filter(function ($slot) use ($date) {
                $targetDay = app(\App\Services\DoctorAvailabilityService::class)->recurringDayOfWeek($slot, $date);

                return $targetDay && strtolower($targetDay) === strtolower($date->format('l'));
            });

        // 2. Non-recurring: based on date and day_of_week
        $nonRecurring = DoctorAvailability::where('doctor_id', $doctorId)
            ->where('is_recurring', false)
            ->with('overrides')
            ->where(function ($q) use ($dateStr, $dayName) {
                $q->whereDate('date', $dateStr)
                    ->orWhere('day_of_week', $dayName);
            })
            ->get();

        // 3. Combine and add effective_date
        $service = app(\App\Services\DoctorAvailabilityService::class);

        return $recurring->concat($nonRecurring)
            ->map(fn ($slot) => $service->applyEffectiveValuesForDate($slot, $dateStr))
            ->filter()
            ->sortBy(function ($slot) {
            return $slot->start_time;
        });
    }

    /**
     * Get schedule for a week (starting from selected date)
     */
    private function getWeekSchedule(string $doctorId, Carbon $startDate): \Illuminate\Support\Collection
    {
        $startOfWeek = $startDate->copy()->startOfWeek();
        $endOfWeek = $startDate->copy()->endOfWeek();

        // Recurring slots
        $recurring = DoctorAvailability::where('doctor_id', $doctorId)
            ->where('is_recurring', true)
            ->with('overrides')
            ->where(function ($q) use ($startOfWeek) {
                $q->whereNull('recurring_end_date')
                    ->orWhere('recurring_end_date', '>=', $startOfWeek->format('Y-m-d'));
            })
            ->where(function ($q) use ($endOfWeek) {
                $q->whereNull('recurring_start_date')
                    ->orWhere('recurring_start_date', '<=', $endOfWeek->format('Y-m-d'));
            })
            ->get();

        // Non-recurring slots (date OR day_of_week)
        $nonRecurring = DoctorAvailability::where('doctor_id', $doctorId)
            ->where('is_recurring', false)
            ->with('overrides')
            ->where(function ($q) use ($startOfWeek, $endOfWeek) {
                $q->whereBetween('date', [$startOfWeek->format('Y-m-d'), $endOfWeek->format('Y-m-d')])
                    ->orWhereNotNull('day_of_week');
            })
            ->get();

        $expanded = collect();
        $service = app(\App\Services\DoctorAvailabilityService::class);

        // Process Recurring
        foreach ($recurring as $slot) {
            $targetDayName = $service->recurringDayOfWeek($slot, $startOfWeek);

            $current = $startOfWeek->copy();
            while ($current->lte($endOfWeek)) {
                if (strtolower($current->format('l')) === $targetDayName) {
                    $slotStart = $slot->recurring_start_date ? $slot->recurring_start_date->startOfDay() : $startOfWeek->copy();
                    $slotEnd = $slot->recurring_end_date ? $slot->recurring_end_date->endOfDay() : null;

                    if ($current->gte($slotStart) && (! $slotEnd || $current->lte($slotEnd))) {
                        $slotCopy = $service->applyEffectiveValuesForDate($slot, $current);
                        if ($slotCopy) {
                            $expanded->push($slotCopy);
                        }
                    }
                }
                $current->addDay();
            }
        }

        // Process Non-Recurring
        foreach ($nonRecurring as $slot) {
            if ($slot->date) {
                $slotDate = Carbon::parse($slot->date);
                if ($slotDate->between($startOfWeek, $endOfWeek)) {
                    $slotCopy = $service->applyEffectiveValuesForDate($slot, $slotDate);
                    if ($slotCopy) {
                        $expanded->push($slotCopy);
                    }
                }
            } elseif ($slot->day_of_week) {
                $targetDay = strtolower($slot->day_of_week);
                $current = $startOfWeek->copy();
                while ($current->lte($endOfWeek)) {
                    if (strtolower($current->format('l')) === $targetDay) {
                        $slotCopy = $service->applyEffectiveValuesForDate($slot, $current);
                        if ($slotCopy) {
                            $expanded->push($slotCopy);
                        }
                    }
                    $current->addDay();
                }
            }
        }

        return $expanded->sortBy(function ($slot) {
            return $slot->effective_date . ' ' . $slot->start_time;
        });
    }

    private function getMonthSchedule(string $doctorId, Carbon $date): \Illuminate\Support\Collection
    {
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        // Recurring slots
        $recurring = DoctorAvailability::where('doctor_id', $doctorId)
            ->where('is_recurring', true)
            ->with('overrides')
            ->where(function ($q) use ($startOfMonth) {
                $q->whereNull('recurring_end_date')
                    ->orWhere('recurring_end_date', '>=', $startOfMonth->format('Y-m-d'));
            })
            ->where(function ($q) use ($endOfMonth) {
                $q->whereNull('recurring_start_date')
                    ->orWhere('recurring_start_date', '<=', $endOfMonth->format('Y-m-d'));
            })
            ->get();

        // Non-recurring slots
        $nonRecurring = DoctorAvailability::where('doctor_id', $doctorId)
            ->where('is_recurring', false)
            ->with('overrides')
            ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
                    ->orWhereNotNull('day_of_week');
            })
            ->get();

        $expanded = collect();
        $service = app(\App\Services\DoctorAvailabilityService::class);

        // Expand Recurring
        foreach ($recurring as $slot) {
            $targetDayName = $service->recurringDayOfWeek($slot, $startOfMonth);

            $current = $startOfMonth->copy();
            while ($current->lte($endOfMonth)) {
                if (strtolower($current->format('l')) === $targetDayName) {
                    $slotStart = $slot->recurring_start_date ? $slot->recurring_start_date->startOfDay() : $startOfMonth->copy();
                    $slotEnd = $slot->recurring_end_date ? $slot->recurring_end_date->endOfDay() : null;

                    if ($current->gte($slotStart) && (! $slotEnd || $current->lte($slotEnd))) {
                        $slotCopy = $service->applyEffectiveValuesForDate($slot, $current);
                        if ($slotCopy) {
                            $expanded->push($slotCopy);
                        }
                    }
                }
                $current->addDay();
            }
        }

        // Expand Non-Recurring
        foreach ($nonRecurring as $slot) {
            if ($slot->date) {
                $slotDate = Carbon::parse($slot->date);
                if ($slotDate->between($startOfMonth, $endOfMonth)) {
                    $slotCopy = $service->applyEffectiveValuesForDate($slot, $slotDate);
                    if ($slotCopy) {
                        $expanded->push($slotCopy);
                    }
                }
            } elseif ($slot->day_of_week) {
                $targetDay = strtolower($slot->day_of_week);
                $current = $startOfMonth->copy();
                while ($current->lte($endOfMonth)) {
                    if (strtolower($current->format('l')) === $targetDay) {
                        $slotCopy = $service->applyEffectiveValuesForDate($slot, $current);
                        if ($slotCopy) {
                            $expanded->push($slotCopy);
                        }
                    }
                    $current->addDay();
                }
            }
        }

        return $expanded->sortBy(function ($slot) {
            return $slot->effective_date . ' ' . $slot->start_time;
        });
    }

    /**
     * Format schedule response based on filter type
     */
    private function formatScheduleResponse(\Illuminate\Support\Collection $availabilities, string $filter, Carbon $selectedDate, string $doctorId): array
    {
        // 1. Get the date range
        $startDate = $selectedDate->copy();
        $endDate = $selectedDate->copy();

        if ($filter === 'week') {
            $startDate = $selectedDate->copy()->startOfWeek();
            $endDate = $selectedDate->copy()->endOfWeek();
        } elseif ($filter === 'month') {
            $startDate = $selectedDate->copy()->startOfMonth();
            $endDate = $selectedDate->copy()->endOfMonth();
        }

        // 2. Fetch all appointments for the doctor in this range
        $appointmentsInPeriod = Appointment::with(['patient:id,first_name,last_name'])
            ->where('doctor_id', $doctorId)
            ->whereBetween('appointment_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->whereIn('status', [
                AppointmentStatus::CONFIRMED->value,
                AppointmentStatus::COMPLETED->value,
                AppointmentStatus::RESCHEDULED->value,
                AppointmentStatus::CANCELLED->value,
            ])
            ->get();

        $externalBookingsInPeriod = ExternalBooking::query()
            ->where('doctor_id', $doctorId)
            ->where('consultation_type', 'in-person')
            ->where('opd_type', 'private')
            ->whereBetween('appointment_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();

        // 3. Map availabilities to slots and link appointments
        $slots = $availabilities->map(function ($availability) use ($appointmentsInPeriod, $externalBookingsInPeriod) {
            $effectiveDate = $availability->effective_date ?? $availability->date;

            // Filter appointments for this specific slot and date
            $slotAppointments = $appointmentsInPeriod->filter(function ($app) use ($availability, $effectiveDate) {
                return $app->availability_id === $availability->id && $app->appointment_date->format('Y-m-d') == $effectiveDate;
            });

            $slotStart = Carbon::parse($availability->start_time)->format('H:i:s');
            $slotExternalBookings = $externalBookingsInPeriod->filter(function ($booking) use ($availability, $effectiveDate, $slotStart) {
                return $booking->appointment_date->format('Y-m-d') === $effectiveDate
                    && Carbon::parse($booking->start_time)->format('H:i:s') === $slotStart
                    && ($booking->availability_id === null || $booking->availability_id === $availability->id);
            });

            $capacitySummary = app(SlotCapacityService::class)->summary(
                doctorId: $availability->doctor_id,
                date: $effectiveDate,
                startTime: $availability->start_time,
                capacity: (int) ($availability->capacity ?? 1),
                availabilityId: $availability->id,
                consultationType: $availability->consultation_type,
            );

            // Format time
            $startTime = Carbon::parse($availability->start_time)->format('g:i A');
            $endTime = Carbon::parse($availability->end_time)->format('g:i A');

            return [
                'id' => $availability->id,
                'date' => $effectiveDate,
                'day_name' => Carbon::parse($effectiveDate)->format('l'),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'time_range' => $startTime . ' - ' . $endTime,
                'consultation_type' => $availability->consultation_type,
                'consultation_type_label' => $availability->consultation_type === 'video' ? 'Video Consultation' : 'In-Person Consultation',
                'capacity' => $availability->capacity ?? 1,
                'slot_capacity' => $availability->capacity ?? 1,
                'booked_count' => $capacitySummary['booked_count'],
                'available_slots' => $capacitySummary['available_slots'],
                'is_full' => $capacitySummary['is_full'],
                'is_recurring' => (bool) $availability->is_recurring,
                'doctor_room' => $availability->doctor_room,
                'is_available' => (bool) $availability->is_available,
                'appointments' => $slotAppointments->values()->map(function ($app) {
                    return [
                        'id' => $app->id,
                        'patient_name' => ($app->patient->first_name ?? '') . ' ' . ($app->patient->last_name ?? ''),
                        'patient_avatar' => $app->patient->avatar ?? null,
                        'status' => $app->status instanceof AppointmentStatus ? $app->status->value : $app->status,
                        'status_label' => $app->status instanceof AppointmentStatus ? $app->status->label() : $app->status,
                        'consultation_type' => $app->consultation_type,
                        'start_time' => Carbon::parse($app->appointment_time)->format('g:i A'),
                        'end_time' => Carbon::parse($app->appointment_end_time)->format('g:i A'),
                    ];
                }),
                'external_bookings' => $slotExternalBookings->values()->map(function ($booking) {
                    return [
                        'id' => $booking->id,
                        'patient_name' => $booking->patient_name,
                        'patient_unit_number' => $booking->patient_unit_number,
                        'mobile' => $booking->mobile,
                        'source' => $booking->source,
                        'start_time' => Carbon::parse($booking->start_time)->format('g:i A'),
                        'end_time' => $booking->end_time ? Carbon::parse($booking->end_time)->format('g:i A') : null,
                    ];
                }),
            ];
        });

        // 4. Handle appointments NOT linked to any of these slots
        $mappedAppointmentIds = $slots->pluck('appointments')->flatten(1)->pluck('id')->toArray();
        $unlinkedAppointments = $appointmentsInPeriod->whereNotIn('id', $mappedAppointmentIds);

        $extraSlots = $unlinkedAppointments->map(function ($app) {
            $startTime = Carbon::parse($app->appointment_time)->format('g:i A');
            $endTime = Carbon::parse($app->appointment_end_time)->format('g:i A');

            return [
                'id' => null,
                'appointment_id' => $app->id,
                'date' => $app->appointment_date->format('Y-m-d'),
                'day_name' => $app->appointment_date->format('l'),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'time_range' => $startTime . ' - ' . $endTime,
                'consultation_type' => $app->consultation_type,
                'consultation_type_label' => $app->consultation_type === 'video' ? 'Video Consultation' : 'In-Person Consultation',
                'capacity' => 0,
                'slot_capacity' => 0,
                'booked_count' => 0,
                'available_slots' => 0,
                'appointments' => [[
                    'id' => $app->id,
                    'patient_name' => ($app->patient->first_name ?? '') . ' ' . ($app->patient->last_name ?? ''),
                    'patient_avatar' => $app->patient->avatar ?? null,
                    'status' => $app->status instanceof AppointmentStatus ? $app->status->value : $app->status,
                    'status_label' => $app->status instanceof AppointmentStatus ? $app->status->label() : $app->status,
                    'consultation_type' => $app->consultation_type,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                ]],
            ];
        });

        // 5. Combine and Sort using base collections to avoid getKey() error
        $allSlots = collect($slots->values()->all())
            ->merge($extraSlots->values()->all())
            ->sortBy(function ($slot) {
                return $slot['date'] . ' ' . Carbon::parse($slot['start_time'])->format('H:i');
            });

        // Group by date for week and month views
        if ($filter === 'week' || $filter === 'month') {
            $grouped = $allSlots->groupBy('date')->map(function ($daySlots, $date) {
                return [
                    'date' => $date,
                    'day_name' => Carbon::parse($date)->format('l'),
                    'day_short' => Carbon::parse($date)->format('D'),
                    'slots' => $daySlots->values()->all(),
                ];
            })->sortKeys()->values();

            return [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'days' => $grouped->all(),
            ];
        }

        // For day view, return flat list
        return [
            'date' => $selectedDate->format('Y-m-d'),
            'day_name' => $selectedDate->format('l'),
            'slots' => $allSlots->values()->all(),
        ];
    }
}
