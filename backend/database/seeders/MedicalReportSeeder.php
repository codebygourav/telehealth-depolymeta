<?php

namespace Database\Seeders;

use App\Models\{ModuleDocument, Appointment, MedicalReport};
use App\Enums\MedicalReportStatus;
use Illuminate\Database\Seeder;

class MedicalReportSeeder extends Seeder
{
    public function run(): void
    {
        $pdfUrl = 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf';
        $imageUrls = [
            'https://raw.githubusercontent.com/mantinedev/mantine/master/.demo/images/bg-1.png',
            'https://raw.githubusercontent.com/mantinedev/mantine/master/.demo/images/bg-2.jpg',
            'https://raw.githubusercontent.com/mantinedev/mantine/master/.demo/images/bg-3.webp',
        ];

        // Create 10 medical reports
        MedicalReport::factory()->count(10)->create()->each(function ($report, $index) use ($pdfUrl, $imageUrls) {
            $fileUrl = ($index % 2 === 0)
                ? $pdfUrl
                : $imageUrls[array_rand($imageUrls)];

            // Randomly set some as not shared and no doctor
            $report->update([
                'doctor_id' => null,
                'status' =>  MedicalReportStatus::UPLOADED,
            ]);

            ModuleDocument::create([
                'model_type' => MedicalReport::class,
                'model_id' => $report->id,
                'name' => 'file',
                'files' => [$fileUrl],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }
}
