<?php

namespace App\Http\Controllers\Api\V2\Common\Prescription;

use App\Http\Controllers\Controller;
use App\Models\{Appointment, Medicine, Patient, Prescription, PrescriptionDraft, DoctorAddedMedicine, Doctor};
use App\Services\{ApiResponseService, PrescriptionDraftParser, PrescriptionService, NotificationService};
use App\Support\{PrescriptionDictation, PrescriptionSpeech};
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Schema, Storage, Validator};

class PrescriptionController extends Controller
{
    public function index(Request $request)
    {
        $medicines = Medicine::with(['category:id,name', 'type:id,name'])->get();

        $data = $medicines->map(function ($medicine) {
            $category = $medicine->category?->name ?? 'N/A';
            $type = $medicine->type?->name ?? 'N/A';

            return [
                'id' => $medicine->id,
                'name' => $medicine->name,
                'type' => $type,
                'category' => $category,
                'strength_options' => $medicine->strength_options ?? [],
                'dosage_options' => $medicine->dosage_options ?? [],
                'frequency_options' => $medicine->frequency_options ?? [],
                'timing_options' => $medicine->timing_options ?? [],
                'meal_options' => $medicine->meal_options ?? [],
                'route_options' => $medicine->route_options ?? [],
                'duration_options' => $medicine->duration_options ?? [],
                'application_area_options' => $medicine->application_area_options ?? [],
                'field_rules' => $medicine->field_rules ?? [],
                'spoken_aliases' => $medicine->spoken_aliases ?? [],
                'defaults' => [
                    'strength' => $medicine->default_strength,
                    'dosage' => $medicine->default_dosage,
                    'frequency' => $medicine->default_frequency,
                    'timing' => $medicine->default_timing,
                    'meal' => $medicine->default_meal,
                    'duration' => $medicine->default_duration,
                    'route' => $medicine->default_route,
                    'instructions' => $medicine->default_instructions,
                ],
            ];
        })->toArray(); // Cast to array to satisfy the type hint

        return ApiResponseService::success(
            'responses.success',
            data: $data
        );
    }

    public function store(Request $request, string $appointmentId)
    {
        $appointment = Appointment::findOrFail($appointmentId);

        // 🔹 Inline Validation
        $validator = Validator::make($request->all(), [
            'medicines' => 'required|array|min:1',
            'draft_id' => 'nullable|uuid|exists:prescription_drafts,id',

            'medicines.*.medicine_name' => 'required|string|max:255',
            'medicines.*.medicine_id' => 'nullable|uuid|exists:medicines,id',

            'medicines.*.frequency' => 'required|in:OD,BD,TDS,SOS',

            'medicines.*.timings' => 'nullable|array',
            'medicines.*.timings.*' => 'in:morning,afternoon,evening,night',
            'medicines.*.dosage' => 'nullable|string|max:255',
            'medicines.*.strength' => 'nullable|string|max:255',
            'medicines.*.meal' => 'nullable|string|max:255',
            'medicines.*.route' => 'nullable|string|max:255',
            'medicines.*.application_area' => 'nullable|string|max:255',
            'medicines.*.is_sos' => 'nullable|boolean',
            'medicines.*.sos_instruction' => 'nullable|string|max:500',
            'medicines.*.remarks' => 'nullable|string|max:1000',

            'medicines.*.start_date' => 'nullable|date',
            'medicines.*.end_date' => 'nullable|date|after_or_equal:medicines.*.start_date',

            'medicines.*.is_ongoing' => 'nullable|boolean',

            'medicines.*.instructions' => 'nullable|string|max:500',
            'follow_up_note' => 'nullable|string|max:2000',
            'patient_follow_up_note' => 'nullable|string|max:2000',
            'stamp_preference' => 'nullable|string|in:only_department,both,only_global',
        ]);

        if ($validator->fails()) {
            // Flatten all error messages into a single array then join into a single string
            $messages = collect($validator->errors()->toArray())
                ->flatten()
                ->all();

            $messageString = implode(' ', $messages);

            return ApiResponseService::error(
                'responses.validation_failed',
                [
                    'message' => $messageString
                ],
                422,
                null,
                'VALIDATION_FAILED'
            );
        }

        // 🔹 Save Stamp Preference to Appointment
        if ($request->has('stamp_preference')) {
            $appointment->update(['stamp_preference' => $request->stamp_preference]);
        }

        $followUpNote = trim((string) ($request->input('follow_up_note') ?: $request->input('patient_follow_up_note')));
        if ($followUpNote !== '') {
            $appointment->update(['instructions_by_doctor' => $followUpNote]);
        }

        // 🔹 Authenticated doctor
        $doctor = $request->user();
        $doctorId = $this->resolveDoctorId($doctor);
        // Ultimately, prevent further progress if we can't determine a valid doctor_id,
        // since this will break the foreign key constraint in the prescriptions table.
        if (! $doctorId) {
            return ApiResponseService::validationError('Doctor association not detected or invalid. Please contact support.');
        }

        // 🔹 Patient comes from appointment
        $patientId = $appointment->patient_id;

        $created = [];

        foreach ($request->medicines as $item) {

            // Resolve meal_timing from dedicated field, or fall back to before_meal/after_meal flags
            $mealTiming = $item['meal'] ?? null;
            if (! $mealTiming) {
                if (! empty($item['before_meal'])) {
                    $mealTiming = 'Before Meal';
                } elseif (! empty($item['after_meal'])) {
                    $mealTiming = 'After Meal';
                }
            }

            // Build timing labels
            $frequencyTimes = [];
            if (! empty($item['timings'])) {
                if (in_array('morning', $item['timings'])) {
                    $frequencyTimes[] = 'Morning';
                }
                if (in_array('afternoon', $item['timings'])) {
                    $frequencyTimes[] = 'Afternoon';
                }
                if (in_array('evening', $item['timings'])) {
                    $frequencyTimes[] = 'Evening';
                }
                if (in_array('night', $item['timings'])) {
                    $frequencyTimes[] = 'Night';
                }
            }

            // Dates
            $startDate = Carbon::parse($item['start_date'] ?? now());
            $endDate = ! empty($item['end_date'])
                ? Carbon::parse($item['end_date'])
                : null;
            $medicineId = $item['medicine_id'] ?? null;
            $doctorAddedMedicineId = null;
            $medicineType = null;

            if ($medicineId) {
                $medicine = Medicine::with('type')->find($medicineId);
                $medicineType = $medicine?->type?->name;
            } else {
                // If medicine_id is not provided, check if it exists in main medicines table by name
                $existingMedicine = Medicine::with('type')->where('name', $item['medicine_name'])->first();
                if ($existingMedicine) {
                    $medicineId = $existingMedicine->id;
                    $medicineType = $existingMedicine->type?->name;
                } else {
                    // It's a new medicine, store it in doctor_added_medicines table
                    $addedMedicine = DoctorAddedMedicine::firstOrCreate(
                        ['name' => $item['medicine_name']],
                        ['added_by_doctor' => $doctorId]
                    );
                    $doctorAddedMedicineId = $addedMedicine->id;
                }
            }

            $prescriptionData = [
                'appointment_id' => $appointment->id,
                'doctor_id' => $doctorId, // Use resolved doctor_id, not blindly $doctor->id
                'patient_id' => $patientId,
                'medicine_id' => $medicineId,
                'doctor_added_medicine_id' => $doctorAddedMedicineId,
                'medicine_type' => $medicineType,
                'medicine_name' => $item['medicine_name'],
                'dosage' => $item['dosage'],
                'frequency' => $item['frequency'],
                'frequency_times' => $frequencyTimes,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'instructions' => $item['instructions'] ?? null,
                'meal_timing' => $item['meal'],
            ];

            foreach ([
                'strength',
                'route',
                'application_area',
                'is_sos',
                'sos_instruction',
                'remarks',
            ] as $optionalColumn) {
                if (Schema::hasColumn('prescriptions', $optionalColumn) && array_key_exists($optionalColumn, $item)) {
                    $prescriptionData[$optionalColumn] = $item[$optionalColumn];
                }
            }

            // Save Prescription
            $prescription = Prescription::create($prescriptionData);

            $created[] = $prescription;
        }

        if ($request->filled('draft_id')) {
            $draft = PrescriptionDraft::query()
                ->where('id', $request->input('draft_id'))
                ->where('appointment_id', $appointment->id)
                ->where('doctor_id', $doctorId)
                ->first();

            if ($draft) {
                $draft->update([
                    'status' => PrescriptionDraft::STATUS_APPLIED,
                    'submitted_payload' => [
                        'stamp_preference' => $request->input('stamp_preference'),
                        'medicines' => $request->input('medicines', []),
                        'created_prescription_ids' => collect($created)->pluck('id')->values()->all(),
                        'created_medicines' => collect($created)->map(function (Prescription $prescription): array {
                            return [
                                'prescription_id' => $prescription->id,
                                'medicine_name' => $prescription->medicine_name,
                                'medicine_source' => $prescription->doctor_added_medicine_id
                                    ? 'doctor_added'
                                    : ($prescription->medicine_id ? 'inventory' : 'unknown'),
                            ];
                        })->values()->all(),
                    ],
                    'applied_at' => now(),
                ]);
            }
        }

        // 🔹 Delete existing PDF and regenerate
        $pdfPath = 'prescriptions/Prescription-' . $appointmentId . '.pdf';
        if (Storage::disk('public')->exists($pdfPath)) {
            Storage::disk('public')->delete($pdfPath);
        }

        // Generate new PDF
        PrescriptionService::generatePdf($appointmentId);

        NotificationService::notifyPrescriptionAdded($appointment);

        return ApiResponseService::created('responses.created', [
            'medicines' => $created,
            'pdf_url' => $this->getPrescriptionPdfUrl($appointmentId),
        ]);
    }

    public function parseTextDraft(Request $request, string $appointmentId, PrescriptionDraftParser $parser)
    {
        if (! PrescriptionDictation::isDraftParsingEnabled()) {
            return ApiResponseService::error(
                'responses.validation_failed',
                ['message' => 'Prescription draft parsing is not enabled for this account.'],
                422,
                null,
                'DICTATION_DISABLED'
            );
        }

        $appointment = Appointment::findOrFail($appointmentId);

        $validator = Validator::make($request->all(), [
            'input_text' => 'required|string|max:5000',
            'source_type' => 'nullable|string|in:text,speech',
        ]);

        if ($validator->fails()) {
            $messages = collect($validator->errors()->toArray())
                ->flatten()
                ->all();

            return ApiResponseService::error(
                'responses.validation_failed',
                ['message' => implode(' ', $messages)],
                422,
                null,
                'VALIDATION_FAILED'
            );
        }

        $doctorId = $this->resolveDoctorId($request->user());

        if (! $doctorId) {
            return ApiResponseService::validationError('Doctor association not detected or invalid. Please contact support.');
        }

        $inputText = trim((string) $request->input('input_text'));
        $parsed = $parser->parse($inputText, $doctorId);

        $draft = PrescriptionDraft::create([
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctorId,
            'patient_id' => $appointment->patient_id,
            'source_type' => (string) $request->input('source_type', PrescriptionDraft::SOURCE_TEXT),
            'status' => PrescriptionDraft::STATUS_PARSED,
            'input_text' => $inputText,
            'parsed_payload' => $parsed,
            'warnings' => $parsed['warnings'] ?? [],
            'missing_fields' => $parsed['missing_fields'] ?? [],
            'confidence_score' => $parsed['confidence_score'] ?? null,
        ]);

        return ApiResponseService::success('responses.success', data: [
            'draft_id' => $draft->id,
            'form' => $parsed['form'] ?? [],
            'warnings' => $draft->warnings ?? [],
            'missing_fields' => $draft->missing_fields ?? [],
            'confidence_score' => $draft->confidence_score,
            'source_type' => $draft->source_type,
        ]);
    }

    public function getPrescriptionByUser(Request $request, string $id)
    {
        // Detect whether ID belongs to doctor or patient
        $isDoctor = Doctor::where('id', $id)->exists();
        $isPatient = Patient::where('id', $id)->exists();

        if (! $isDoctor && ! $isPatient) {
            return ApiResponseService::notFound();
        }

        // Get filter from query param, default to 'current'
        $filter = $request->query('filter', 'current');

        // Get appointment IDs linked to this doctor or patient
        $appointmentIds = Appointment::query()
            ->when($isDoctor, fn($q) => $q->where('doctor_id', $id))
            ->when($isPatient, fn($q) => $q->where('patient_id', $id))
            ->pluck('id');

        // Fetch prescriptions with appointment + doctor
        $prescriptions = Prescription::whereIn('appointment_id', $appointmentIds)
            ->with([
                'appointment',
                'appointment.doctor',
            ])
            ->orderByDesc('created_at')
            ->get();

        $now = Carbon::now()->startOfDay();

        $groupedData = [];
        foreach ($prescriptions as $item) {
            $appointment = $item->appointment;
            $doctor = $appointment?->doctor;

            // Determine the prescription's start and end date at day precision
            $startDate = $item->start_date ? Carbon::parse($item->start_date)->startOfDay() : null;
            $endDate = $item->end_date ? Carbon::parse($item->end_date)->startOfDay() : null;

            // Ongoing/Past Logic
            $isOngoing = false;
            if ($startDate && $endDate) {
                $isOngoing = $now->betweenIncluded($startDate, $endDate);
            } elseif ($startDate) {
                $isOngoing = $now->greaterThanOrEqualTo($startDate);
            } elseif ($endDate) {
                $isOngoing = $now->lessThanOrEqualTo($endDate);
            } else {
                $isOngoing = true;
            }

            // Match based on filter param
            if (
                ($filter === 'current' && $isOngoing)
                || ($filter === 'past' && ! $isOngoing)
            ) {
                $apptId = $item->appointment_id;
                if (! isset($groupedData[$apptId])) {
                    $groupedData[$apptId] = [
                        'appointment_id' => $apptId,
                        'doctor_name' => $doctor ? $doctor->first_name . ' ' . $doctor->last_name : null,
                        'medician_name' => $item->medicine_name ? $item->medicine_name : null,
                        'problem' => $appointment?->notes ?? 'Consultation',
                        'frequency' => $item->frequency,
                        'frequencylabel' => match ($item->frequency) {
                            'OD' => 'Once a day',
                            'BD' => 'Twice a day',
                            'TDS' => '3 times a day',
                            'SOS' => 'As needed',
                            default => $item->frequency,
                        },
                        'status' => $isOngoing ? 'Active' : 'Inactive',
                        'pdf_url' => $this->getPrescriptionPdfUrl($apptId),
                        'instructions_by_doctor' => $appointment?->instructions_by_doctor,
                        'next_visit_date' => $appointment?->next_visit_date ? Carbon::parse($appointment->next_visit_date)->format('Y-m-d') : null,
                        'min_start' => $startDate,
                        'max_end' => $endDate,
                    ];
                } else {
                    if ($startDate && (! $groupedData[$apptId]['min_start'] || $startDate->lt($groupedData[$apptId]['min_start']))) {
                        $groupedData[$apptId]['min_start'] = $startDate;
                    }
                    if ($endDate && (! $groupedData[$apptId]['max_end'] || $endDate->gt($groupedData[$apptId]['max_end']))) {
                        $groupedData[$apptId]['max_end'] = $endDate;
                    }
                }
            }
        }

        foreach ($groupedData as &$data) {
            $startStr = $data['min_start'] ? $data['min_start']->format('D, M d') : null;
            $endStr = $data['max_end'] ? $data['max_end']->format('D, M d') : null;

            $data['timing'] = ($startStr && $endStr) ? "$startStr — $endStr" : ($startStr ?: $endStr);

            unset($data['min_start'], $data['max_end']);
        }

        return ApiResponseService::success('responses.success', data: array_values($groupedData));
    }

    public function show(Request $request, $appointmentid)
    {
        $appointment = Appointment::find($appointmentid);

        if (! $appointment) {
            return ApiResponseService::notFound(__('responses.appointment.not_found'));
        }

        $data = $this->resolvePrescriptionData($appointmentid);

        $appliedDrafts = PrescriptionDraft::query()
            ->where('appointment_id', $appointment->id)
            ->where('status', PrescriptionDraft::STATUS_APPLIED)
            ->latest('applied_at')
            ->latest('created_at')
            ->get();

        $voicePrescriptionIds = $appliedDrafts
            ->where('source_type', 'speech')
            ->flatMap(fn (PrescriptionDraft $draft) => $draft->submitted_payload['created_prescription_ids'] ?? [])
            ->filter()
            ->flip();

        if (! $data) {
            return ApiResponseService::success('responses.prescription.not_found', data: [
                'pdf_url' => null,
                'medicines' => [],
                'instructions_by_doctor' => $appointment->instructions_by_doctor,
                'next_visit_date' => $appointment->next_visit_date ? Carbon::parse($appointment->next_visit_date)->format('Y-m-d') : null,
                'dictation_assistant' => array_merge(
                    PrescriptionDictation::settings(),
                    [
                        'browser_speech_enabled' => (bool) (PrescriptionSpeech::settings()['enabled'] ?? false),
                    ]
                ),
                'draft_history' => $this->mapDraftHistory($appliedDrafts),
            ]);
        }

        $pdfPath = 'prescriptions/Prescription-' . $appointmentid . '.pdf';
        if (! Storage::disk('public')->exists($pdfPath)) {
            PrescriptionService::generatePdf($appointmentid);
        }

        $pdf_url = $this->getPrescriptionPdfUrl($appointmentid);

        $allPrescriptions = $data['prescriptions'];
        $doctor = $data['doctor'] ?? null;
        $doctorName = $doctor ? $doctor->first_name . ' ' . $doctor->last_name : null;
        $now = Carbon::now()->startOfDay();

        $currentMedicines = [];
        $expiredMedicines = [];
        $upcomingMedicines = [];

        foreach ($allPrescriptions as $idx => $med) {
            $formattedTimes = is_array($med->frequency_times) ? implode(', ', $med->frequency_times) : '';
            $frequencyLabel = match ($med->frequency) {
                'OD' => 'Once a day',
                'BD' => 'Twice a day',
                'TDS' => '3 times a day',
                'SOS' => 'As needed',
                default => $med->frequency,
            };

            $instructions = [];
            if (! empty($med->instructions)) {
                $instructions[] = $med->instructions;
            }

            $fromDateObj = $med->start_date ? Carbon::parse($med->start_date)->startOfDay() : null;
            $toDateObj = $med->end_date ? Carbon::parse($med->end_date)->startOfDay() : null;
            $fromDate = $fromDateObj ? $fromDateObj->format('D, M d') : null;
            $toDate = $toDateObj ? $toDateObj->format('D, M d') : null;

            $status = 'Ongoing';
            if ($fromDateObj && $toDateObj) {
                if ($now->lt($fromDateObj)) {
                    $status = 'Upcoming';
                } elseif ($now->gt($toDateObj)) {
                    $status = 'Past';
                }
            } elseif ($fromDateObj && $now->lt($fromDateObj)) {
                $status = 'Upcoming';
            } elseif ($toDateObj && $now->gt($toDateObj)) {
                $status = 'Past';
            }

            $medicineData = [
                'number' => $idx + 1,
                'prescription_id' => $med->id,
                'name' => $med->medicine_name,
                'type' => $med->medicine_type,
                'frequency' => $med->frequency,
                'frequencylabel' => $frequencyLabel,
                'times' => $formattedTimes,
                'date' => $fromDate . ' - ' . $toDate,
                'start_date' => $fromDate,
                'end_date' => $toDate,
                'instructions' => $instructions,
                'dosage' => $med->dosage,
                'strength' => $med->strength,
                'meal' => $med->meal_timing,
                'route' => $med->route,
                'application_area' => $med->application_area,
                'is_sos' => (bool) $med->is_sos,
                'sos_instruction' => $med->sos_instruction,
                'remarks' => $med->remarks,
                'status' => $status,
                'notes' => $med->notes,
                'medicine_source' => $med->doctor_added_medicine_id
                    ? 'doctor_added'
                    : ($med->medicine_id ? 'inventory' : 'unknown'),
                'created_via' => $voicePrescriptionIds->has($med->id) ? 'speech' : null,
            ];

            if ($status === 'Ongoing') {
                $currentMedicines[] = $medicineData;
            } elseif ($status === 'Upcoming') {
                $upcomingMedicines[] = $medicineData;
            } else {
                $expiredMedicines[] = $medicineData;
            }
        }

        $medicineList = array_merge($currentMedicines, $upcomingMedicines, $expiredMedicines);
        return ApiResponseService::success(responseKey: 'responses.success', data: [
            'appointment_id' => $appointmentid,
            'doctor_id' => $doctor->user_id,
            'doctor_name' => $doctorName,
            'pdf_url' => $pdf_url,
            'medicines' => $medicineList,
            'instructions_by_doctor' => $appointment->instructions_by_doctor,
            'next_visit_date' => $appointment->next_visit_date ? Carbon::parse($appointment->next_visit_date)->format('Y-m-d') : null,
            'dictation_assistant' => array_merge(
                PrescriptionDictation::settings(),
                [
                    'browser_speech_enabled' => (bool) (PrescriptionSpeech::settings()['enabled'] ?? false),
                ]
            ),
            'draft_history' => $this->mapDraftHistory($appliedDrafts),
        ]);
    }

    public function download(Request $request, $appointmentId)
    {
        $appointment = Appointment::find($appointmentId);

        if (! $appointment) {
            return ApiResponseService::notFound(__('responses.appointment.not_found'));
        }

        $data = PrescriptionService::resolvePrescriptionData($appointmentId);

        if (! $data) {
            return ApiResponseService::notFound(__('responses.prescription.not_found'));
        }

        $pdf = Pdf::loadView('Prescription.prescription', $data);

        return $pdf->stream('Prescription-' . $appointmentId . '.pdf');
    }

    protected function getPrescriptionPdfUrl($appointmentId)
    {
        return Storage::disk('public')->url('prescriptions/Prescription-' . $appointmentId . '.pdf');
    }

    protected function resolvePrescriptionData($appointmentId)
    {
        return PrescriptionService::resolvePrescriptionData($appointmentId);
    }

    protected function resolveDoctorId($doctor): ?string
    {
        $doctorId = null;

        if (method_exists($doctor, 'doctor')) {
            $doctorInstance = $doctor->doctor;
            if ($doctorInstance) {
                $doctorId = $doctorInstance->id;
            }
        }

        if (! $doctorId && property_exists($doctor, 'doctor_id')) {
            $doctorId = $doctor->doctor_id;
        }

        if (! $doctorId && ! empty($doctor->id)) {
            $doctorId = $doctor->id;
        }

        return $doctorId;
    }

    protected function mapDraftHistory($drafts): array
    {
        return $drafts->map(function (PrescriptionDraft $draft): array {
            $parsedForm = $draft->parsed_payload['form'] ?? [];
            $createdMedicines = $draft->submitted_payload['created_medicines'] ?? [];

            return [
                'id' => $draft->id,
                'source_type' => $draft->source_type,
                'status' => $draft->status,
                'input_text' => $draft->input_text,
                'confidence_score' => $draft->confidence_score,
                'warnings' => $draft->warnings ?? [],
                'missing_fields' => $draft->missing_fields ?? [],
                'applied_at' => $draft->applied_at?->toIso8601String(),
                'medicine_name' => $parsedForm['medicine_name'] ?? null,
                'medicine_source' => $parsedForm['medicine_source'] ?? null,
                'created_prescription_ids' => $draft->submitted_payload['created_prescription_ids'] ?? [],
                'created_medicines' => is_array($createdMedicines) ? array_values($createdMedicines) : [],
            ];
        })->values()->all();
    }
}
