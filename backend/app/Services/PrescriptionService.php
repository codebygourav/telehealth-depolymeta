<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Prescription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PrescriptionService
{
    /**
     * Resolve all data needed for a prescription PDF
     */
    public static function resolvePrescriptionData($appointmentId): ?array
    {
        $appointment = Appointment::with(['doctor.departments', 'patient'])->find($appointmentId);
        if (! $appointment) {
            return null;
        }

        $prescriptions = Prescription::where('appointment_id', $appointmentId)->get();
        if ($prescriptions->isEmpty()) {
            return null;
        }

        $doctor = $appointment->doctor;
        $patient = $appointment->patient;

        // Resolve Signature URL
        $signature_url = null;
        if ($doctor->signature) {
            $signature_path = str_contains($doctor->signature, 'doctorSignatures/')
                ? $doctor->signature
                : 'doctorSignatures/' . basename($doctor->signature);

            if (Storage::disk('public')->exists($signature_path)) {
                $path = Storage::disk('public')->path($signature_path);
                if (file_exists($path)) {
                    $type = pathinfo($path, PATHINFO_EXTENSION);
                    $data = file_get_contents($path);
                    $signature_url = 'data:image/' . $type . ';base64,' . base64_encode($data);
                }
            }
        }

        // Resolve Stamp Preference
        $stampPreference = $appointment->stamp_preference ?? 'only_department';

        // Resolve Stamp URLs
        $department_stamp_url = null;
        $global_stamp_url = null;

        $department = $doctor->departments->first();
        if ($department && $department->department_stamp) {
            if (Storage::disk('public')->exists($department->department_stamp)) {
                $path = Storage::disk('public')->path($department->department_stamp);
                if (file_exists($path)) {
                    $type = pathinfo($path, PATHINFO_EXTENSION);
                    $data = file_get_contents($path);
                    $department_stamp_url = 'data:image/' . $type . ';base64,' . base64_encode($data);
                }
            }
        }

        // Default Department Stamp Fallback
        if (! $department_stamp_url) {
            $path = public_path('images/department_stamp.png');
            if (file_exists($path)) {
                $type = pathinfo($path, PATHINFO_EXTENSION);
                $data = file_get_contents($path);
                $department_stamp_url = 'data:image/' . $type . ';base64,' . base64_encode($data);
            }
        }

        $global_stamp = SettingService::getGlobalStamp();
        if ($global_stamp) {
            $path_url = parse_url($global_stamp, PHP_URL_PATH);
            $relative_path = ltrim($path_url, '/storage/');
            if (Storage::disk('public')->exists($relative_path)) {
                $path = Storage::disk('public')->path($relative_path);
                if (file_exists($path)) {
                    $type = pathinfo($path, PATHINFO_EXTENSION);
                    $data = file_get_contents($path);
                    $global_stamp_url = 'data:image/' . $type . ';base64,' . base64_encode($data);
                }
            }
        }

        // Default Global Stamp Fallback
        if (! $global_stamp_url) {
            $path = public_path('images/global_stamp.png');
            if (file_exists($path)) {
                $type = pathinfo($path, PATHINFO_EXTENSION);
                $data = file_get_contents($path);
                $global_stamp_url = 'data:image/' . $type . ';base64,' . base64_encode($data);
            }
        }

        $show_department_stamp = false;
        $show_global_stamp = false;

        if ($stampPreference === 'both') {
            $show_department_stamp = (bool) $department_stamp_url;
            $show_global_stamp = (bool) $global_stamp_url;
        } elseif ($stampPreference === 'only_global') {
            $show_global_stamp = (bool) $global_stamp_url;
        } else {
            if ($department_stamp_url) {
                $show_department_stamp = true;
            } else {
                $show_global_stamp = (bool) $global_stamp_url;
            }
        }

        // Resolve Hospital Logo
        $hospital_logo_base64 = null;
        $logo = SettingService::getLogo();
        if ($logo) {
            $path_url = parse_url($logo, PHP_URL_PATH);
            $relative_path = ltrim($path_url, '/storage/');
            if (Storage::disk('public')->exists($relative_path)) {
                $path = Storage::disk('public')->path($relative_path);
                if (file_exists($path)) {
                    $type = pathinfo($path, PATHINFO_EXTENSION);
                    $data_img = file_get_contents($path);
                    $hospital_logo_base64 = 'data:image/' . $type . ';base64,' . base64_encode($data_img);
                }
            }
        }

        // Final Fallback for testing if logo not found
        if (! $hospital_logo_base64) {
            $path = public_path('images/deploymeta.png');
            if (file_exists($path)) {
                $type = pathinfo($path, PATHINFO_EXTENSION);
                $data_img = file_get_contents($path);
                $hospital_logo_base64 = 'data:image/' . $type . ';base64,' . base64_encode($data_img);
            }
        }

        // Load Alex Brush font as base64 for PDF embedding
        $alex_brush_base64 = '';
        $font_path = public_path('fonts/AlexBrush-Regular.ttf');
        if (file_exists($font_path)) {
            $alex_brush_base64 = base64_encode(file_get_contents($font_path));
        }

        $contact = SettingService::getContactInfo();

        return [
            'appointment' => $appointment,
            'doctor' => $doctor,
            'patient' => $appointment->patient,
            'prescriptions' => $prescriptions,
            'signature_url' => $signature_url,
            'has_sign' => (bool) $signature_url,
            'department_stamp_url' => $department_stamp_url,
            'global_stamp_url' => $global_stamp_url,
            'has_dept' => (bool) $department_stamp_url,
            'has_global' => (bool) $global_stamp_url,
            'show_dept' => $show_department_stamp,
            'show_global' => $show_global_stamp,
            'hospital_logo_url' => $hospital_logo_base64,
            'hospital_name' => SettingService::getAppName() ?? 'Telehealth Deploymeta',
            'hospital_address' => $contact['address'] ?? '123 Medical Plaza, Health City',
            'hospital_phone' => $contact['phone'] ?? '+1 (555) 001-2233',
            'hospital_email' => $contact['email'] ?? 'contact@cmctelehealth.com',
            'alex_brush_font' => $alex_brush_base64,
        ];
    }

    /**
     * Generate and save prescription PDF
     */
    public static function generatePdf($appointmentId): bool
    {
        ini_set('memory_limit', '512M');
        $data = self::resolvePrescriptionData($appointmentId);
        if (! $data) {
            throw new \Exception('Could not resolve prescription data for Appointment ID: ' . $appointmentId);
        }

        $pdfPath = 'prescriptions/Prescription-' . $appointmentId . '.pdf';

        // Ensure directories exist
        if (! Storage::disk('public')->exists('prescriptions')) {
            Storage::disk('public')->makeDirectory('prescriptions');
        }

        $fontCachePath = storage_path('fonts');
        if (! file_exists($fontCachePath)) {
            mkdir($fontCachePath, 0775, true);
        }

        try {
            $pdf = Pdf::loadView('Prescription.prescription', $data);

            // Set some options for better compatibility
            $pdf->getDomPDF()->set_option('isRemoteEnabled', true);
            $pdf->getDomPDF()->set_option('isHtml5ParserEnabled', true);
            $pdf->getDomPDF()->set_option('fontCache', $fontCachePath);

            $output = $pdf->output();

            // Save to disk
            $saved = Storage::disk('public')->put($pdfPath, $output);

            if (! $saved) {
                Log::error('Failed to save PDF to storage: ' . $pdfPath);

                return false;
            }

            // Save to Module Documents via Appointment
            $appointment = Appointment::find($appointmentId);
            if ($appointment) {
                // Set the prescription_pdf attribute (managed by the trait)
                // This puts the file path into pendingModuleDocuments
                $appointment->prescription_pdf = $pdfPath;
                $appointment->save();
            } else {
                Log::warning('Appointment not found for PDF generation: ' . $appointmentId);
            }

            // Log::info('Prescription PDF generated successfully: '.$pdfPath);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to generate prescription PDF: ' . $e->getMessage());
            // Throw exception so it is visible in the console/seeder output
            throw $e;
        }
    }
}
