<?php

namespace App\Http\Resources\Doctor;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PatientReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $reportDate = Carbon::parse($this->report_date);

        // Type labels mapping
        $typeLabels = [
            'lab_report' => 'Lab Report',
            'radiology' => 'Radiology',
            'imaging' => 'Imaging',
            'x_ray' => 'X-Ray Analysis',
            'prescription' => 'Prescription',
            'other' => 'Other',
        ];

        // Collect all files from module_documents or fallback to file_path
        $files = [];
        $moduleDoc = $this->moduleDocuments()->where('name', 'file')->first();

        if ($moduleDoc && !empty($moduleDoc->files)) {
            foreach ($moduleDoc->files as $file) {
                $files = [
                    'url' => $this->getFileUrl($file),
                    'name' => basename($file),
                    'type' => pathinfo($file, PATHINFO_EXTENSION),
                ];
            }
        } elseif ($this->file_path) {
            $files = [
                'url' => $this->getFileUrl($this->file_path),
                'name' => basename($this->file_path),
                'type' => pathinfo($this->file_path, PATHINFO_EXTENSION),
            ];
        }

        return [
            'id' => $this->id,
            'report_name' => $this->name,
            'report_type' => $this->type,
            'type_label' => $typeLabels[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type)),
            'report_date' => $reportDate->format('Y-m-d'),
            'report_date_formatted' => $reportDate->format('D, M d'),
            'uploaded_at' => $this->created_at?->format('M d, Y'),
            'status' => $this->status,
            'files' => $files,
        ];
    }

    /**
     * Helper to get file URL
     */
    private function getFileUrl($file): ?string
    {
        if (str_starts_with($file, 'http://') || str_starts_with($file, 'https://')) {
            return $file;
        }

        $filePath = str_starts_with($file, 'medical_report/') ? $file : 'medical_report/' . basename($file);

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        if ($disk->exists($filePath)) {
            return $disk->url($filePath);
        }

        if ($disk->exists($file)) {
            return $disk->url($file);
        }

        return null;
    }
}
