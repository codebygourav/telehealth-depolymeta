<?php

namespace App\Http\Controllers\Api\V2\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Resources\Medicine\MedicineTemplateResource;
use App\Models\Appointment;
use App\Models\DoctorAddedMedicine;
use App\Models\Medicine;
use App\Models\MedicineTemplate;
use App\Models\Prescription;
use App\Services\ApiResponseService;
use App\Services\NotificationService;
use App\Services\PrescriptionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MedicineTemplateController extends Controller
{
    public function index(Request $request)
    {
        $doctor = $request->user()?->doctor;
        if (! $doctor) {
            return ApiResponseService::unauthorized();
        }

        $templates = MedicineTemplate::with(['items.medicine.type'])
            ->where(fn ($query) => $this->applyVisibleTemplateScope($query, $doctor))
            ->when($request->boolean('active_only'), fn ($query) => $query->where('is_active', true))
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%' . $request->string('search')->toString() . '%'))
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return ApiResponseService::paginated($templates->through(fn ($template) => new MedicineTemplateResource($template)));
    }

    public function show(Request $request, string $id)
    {
        $template = $this->visibleTemplate($request, $id);
        if (! $template) {
            return ApiResponseService::unauthorized();
        }

        return ApiResponseService::success(data: new MedicineTemplateResource($template));
    }

    public function assign(Request $request, string $appointmentId)
    {
        $data = $request->validate([
            'template_id' => ['required', 'exists:medicine_templates,id'],
            'start_date' => ['nullable', 'date'],
            'stamp_preference' => ['nullable', 'string', 'in:only_department,both,only_global'],
        ]);

        $doctor = $request->user()?->doctor;
        if (! $doctor) {
            return ApiResponseService::unauthorized();
        }

        $appointment = Appointment::findOrFail($appointmentId);
        if ($appointment->doctor_id !== $doctor->id) {
            return ApiResponseService::unauthorized();
        }

        $template = $this->visibleTemplate($request, $data['template_id']);
        if (! $template || ! $template->is_active || $template->items->isEmpty()) {
            return ApiResponseService::notFound();
        }

        if ($request->has('stamp_preference')) {
            $appointment->update(['stamp_preference' => $data['stamp_preference']]);
        }

        $baseStartDate = Carbon::parse($data['start_date'] ?? now())->startOfDay();

        $created = DB::transaction(function () use ($template, $appointment, $doctor, $baseStartDate) {
            $created = [];

            foreach ($template->items as $item) {
                $medicineId = $item->medicine_id;
                $doctorAddedMedicineId = null;
                $medicineType = $item->medicine_type;

                if ($medicineId) {
                    $medicine = Medicine::with('type')->find($medicineId);
                    $medicineType = $medicine?->type?->name ?? $medicineType;
                } else {
                    $addedMedicine = DoctorAddedMedicine::firstOrCreate(
                        ['name' => $item->medicine_name],
                        ['added_by_doctor' => $doctor->id]
                    );
                    $doctorAddedMedicineId = $addedMedicine->id;
                }

                $endDate = null;
                if ($item->duration_value) {
                    $endDate = match ($item->duration_type) {
                        'weeks' => $baseStartDate->copy()->addWeeks($item->duration_value),
                        'months' => $baseStartDate->copy()->addMonths($item->duration_value),
                        default => $baseStartDate->copy()->addDays($item->duration_value),
                    };
                }

                $created[] = Prescription::create([
                    'appointment_id' => $appointment->id,
                    'doctor_id' => $doctor->id,
                    'patient_id' => $appointment->patient_id,
                    'medicine_id' => $medicineId,
                    'doctor_added_medicine_id' => $doctorAddedMedicineId,
                    'medicine_type' => $medicineType,
                    'medicine_name' => $item->medicine_name,
                    'dosage' => $item->dosage,
                    'frequency' => $item->frequency,
                    'frequency_times' => $item->frequency_times ?? [],
                    'duration_type' => $item->duration_type,
                    'duration_value' => $item->duration_value,
                    'start_date' => $baseStartDate,
                    'end_date' => $endDate,
                    'instructions' => $item->instructions,
                    'meal_timing' => $item->meal_timing,
                    'order' => $item->sort_order ?? 0,
                    'use_type' => $item->use_type ?? 'regular',
                    'take_when' => $item->take_when,
                    'min_gap' => $item->min_gap,
                    'max_doses_per_day' => $item->max_doses_per_day,
                    'patient_instruction' => $item->patient_instruction,
                ]);
            }

            return $created;
        });

        $pdfPath = 'prescriptions/Prescription-' . $appointmentId . '.pdf';
        if (Storage::disk('public')->exists($pdfPath)) {
            Storage::disk('public')->delete($pdfPath);
        }

        PrescriptionService::generatePdf($appointmentId);
        NotificationService::notifyPrescriptionAdded($appointment);

        return ApiResponseService::created('responses.created', [
            'medicines' => $created,
            'pdf_url' => Storage::disk('public')->url($pdfPath),
        ]);
    }

    private function visibleTemplate(Request $request, string $id): ?MedicineTemplate
    {
        $doctor = $request->user()?->doctor;
        if (! $doctor) {
            return null;
        }

        return MedicineTemplate::with(['items.medicine.type'])
            ->where(fn ($query) => $this->applyVisibleTemplateScope($query, $doctor))
            ->findOrFail($id);
    }

    private function applyVisibleTemplateScope($query, $doctor)
    {
        $departmentIds = $doctor->departments()->pluck('departments.id');

        return $query
            ->where('scope_type', MedicineTemplate::SCOPE_GLOBAL)
            ->orWhereNull('scope_type')
            ->orWhere('doctor_id', $doctor->id)
            ->orWhere(function ($query) use ($departmentIds) {
                $query->where('scope_type', MedicineTemplate::SCOPE_DEPARTMENT)
                    ->whereIn('department_id', $departmentIds);
            });
    }
}
