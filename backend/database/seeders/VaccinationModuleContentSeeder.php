<?php

namespace Database\Seeders;

use App\Models\VaccinationClinicalInsight;
use App\Models\VaccinationGeneralFaq;
use Illuminate\Database\Seeder;

class VaccinationModuleContentSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'question' => 'Why are multiple doses needed?',
                'answer' => 'Some vaccines require multiple doses to build complete immunity and ensure long-term protection.',
                'sort_order' => 1,
            ],
            [
                'question' => 'What if my baby has a slight cold?',
                'answer' => 'Minor illnesses like a cold usually are not reasons to delay vaccination, but consult your pediatrician first.',
                'sort_order' => 2,
            ],
            [
                'question' => 'Are vaccines safe?',
                'answer' => 'Yes, vaccines undergo rigorous safety testing and monitoring by global health authorities.',
                'sort_order' => 3,
            ],
        ];

        foreach ($faqs as $faq) {
            VaccinationGeneralFaq::query()->firstOrCreate(
                ['question' => $faq['question']],
                array_merge($faq, ['is_active' => true])
            );
        }

        VaccinationClinicalInsight::query()->firstOrCreate(
            ['title' => 'Clinical Insight'],
            [
                'message' => 'Vaccination schedules are based on international pediatric standards. If you miss a dose, please contact your pediatrician immediately to reschedule. You can add personal notes to each log for tracking side effects or allergic reactions.',
                'is_active' => true,
            ]
        );
    }
}
