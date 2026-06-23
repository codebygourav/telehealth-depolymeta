<?php

namespace Database\Seeders;

use App\Models\Medicine;
use App\Models\MedicineTemplate;
use App\Models\MedicineTemplateItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MedicineTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $medicines = Medicine::with('type')->get()->keyBy('name');

        $templates = [
            // ── 1. Fever & Pain Management ──────────────────────────────────────
            [
                'name'        => 'Fever & Pain Management',
                'description' => 'Standard protocol for fever with pain relief. Includes paracetamol every 6 hours and ibuprofen SOS.',
                'scope_type'  => MedicineTemplate::SCOPE_GLOBAL,
                'is_active'   => true,
                'items' => [
                    [
                        'medicine_name'       => 'Paracetamol',
                        'medicine_type'       => 'Tablet',
                        'dosage'              => '1 Tablet',
                        'use_type'            => 'regular',
                        'meal_timing'         => 'after_meal',
                        'doses_per_day'       => 4,
                        'first_dose_time'     => '08:00',
                        'dose_interval_hours' => 6,
                        'duration_type'       => 'days',
                        'duration_value'      => 5,
                        'instructions'        => 'Take after meals. Do not exceed 4g in 24 hours.',
                        'sort_order'          => 1,
                    ],
                    [
                        'medicine_name'       => 'Ibuprofen',
                        'medicine_type'       => 'Tablet',
                        'dosage'              => '1 Tablet',
                        'use_type'            => 'sos',
                        'take_when'           => 'Pain',
                        'min_gap'             => '6 hours',
                        'max_doses_per_day'   => '3 doses',
                        'duration_type'       => 'days',
                        'duration_value'      => 5,
                        'instructions'        => 'Take only if pain occurs. Minimum gap of 6 hours between doses. Do not take more than 3 doses in one day.',
                        'sort_order'          => 2,
                    ],
                ],
            ],

            // ── 2. Standard Antibiotic Course ────────────────────────────────────
            [
                'name'        => 'Standard Antibiotic Course',
                'description' => '7-day antibiotic course with Amoxicillin for bacterial infections. Includes Omeprazole for gastric protection.',
                'scope_type'  => MedicineTemplate::SCOPE_GLOBAL,
                'is_active'   => true,
                'items' => [
                    [
                        'medicine_name'       => 'Amoxicillin',
                        'medicine_type'       => 'Capsule',
                        'dosage'              => '1 Capsule',
                        'use_type'            => 'regular',
                        'meal_timing'         => 'after_meal',
                        'doses_per_day'       => 3,
                        'first_dose_time'     => '08:00',
                        'dose_interval_hours' => 8,
                        'duration_type'       => 'days',
                        'duration_value'      => 7,
                        'instructions'        => 'Complete the full course even if you feel better. Take with food.',
                        'sort_order'          => 1,
                    ],
                    [
                        'medicine_name'       => 'Omeprazole',
                        'medicine_type'       => 'Capsule',
                        'dosage'              => '1 Capsule',
                        'use_type'            => 'regular',
                        'meal_timing'         => 'before_meal',
                        'doses_per_day'       => 1,
                        'first_dose_time'     => '08:00',
                        'dose_interval_hours' => 24,
                        'duration_type'       => 'days',
                        'duration_value'      => 7,
                        'instructions'        => 'Take 30 minutes before breakfast.',
                        'sort_order'          => 2,
                    ],
                    [
                        'medicine_name'       => 'Vitamin C',
                        'medicine_type'       => 'Tablet',
                        'dosage'              => '1 Tablet',
                        'use_type'            => 'regular',
                        'meal_timing'         => 'after_meal',
                        'doses_per_day'       => 1,
                        'first_dose_time'     => '09:00',
                        'dose_interval_hours' => 24,
                        'duration_type'       => 'days',
                        'duration_value'      => 7,
                        'instructions'        => 'Immune support during antibiotic therapy.',
                        'sort_order'          => 3,
                    ],
                ],
            ],

            // ── 3. Diabetes Daily Management ─────────────────────────────────────
            [
                'name'        => 'Diabetes Daily Management',
                'description' => 'Daily insulin protocol for Type 2 diabetes management with Aspirin for cardiovascular protection.',
                'scope_type'  => MedicineTemplate::SCOPE_GLOBAL,
                'is_active'   => true,
                'items' => [
                    [
                        'medicine_name'       => 'Insulin',
                        'medicine_type'       => 'Injection',
                        'dosage'              => '10 Units',
                        'use_type'            => 'regular',
                        'meal_timing'         => 'before_meal',
                        'doses_per_day'       => 2,
                        'first_dose_time'     => '08:00',
                        'dose_interval_hours' => 12,
                        'duration_type'       => 'months',
                        'duration_value'      => null,
                        'instructions'        => 'Administer subcutaneously 30 minutes before meals. Rotate injection sites.',
                        'sort_order'          => 1,
                    ],
                    [
                        'medicine_name'       => 'Aspirin',
                        'medicine_type'       => 'Tablet',
                        'dosage'              => '½ Tablet',
                        'use_type'            => 'regular',
                        'meal_timing'         => 'after_meal',
                        'doses_per_day'       => 1,
                        'first_dose_time'     => '08:00',
                        'dose_interval_hours' => 24,
                        'duration_type'       => 'months',
                        'duration_value'      => null,
                        'instructions'        => 'Low-dose aspirin for cardiovascular protection. Take with food.',
                        'sort_order'          => 2,
                    ],
                    [
                        'medicine_name'       => 'Vitamin C',
                        'medicine_type'       => 'Tablet',
                        'dosage'              => '1 Tablet',
                        'use_type'            => 'regular',
                        'meal_timing'         => 'after_meal',
                        'doses_per_day'       => 1,
                        'first_dose_time'     => '09:00',
                        'dose_interval_hours' => 24,
                        'duration_type'       => 'months',
                        'duration_value'      => null,
                        'instructions'        => 'Daily antioxidant supplement for diabetic patients.',
                        'sort_order'          => 3,
                    ],
                ],
            ],

            // ── 4. Post-Op Recovery Protocol ─────────────────────────────────────
            [
                'name'        => 'Post-Op Recovery Protocol',
                'description' => 'Standard post-operative recovery with pain management, antibiotic prophylaxis, and gastric protection.',
                'scope_type'  => MedicineTemplate::SCOPE_GLOBAL,
                'is_active'   => true,
                'items' => [
                    [
                        'medicine_name'       => 'Amoxicillin',
                        'medicine_type'       => 'Capsule',
                        'dosage'              => '1 Capsule',
                        'use_type'            => 'regular',
                        'meal_timing'         => 'after_meal',
                        'doses_per_day'       => 3,
                        'first_dose_time'     => '08:00',
                        'dose_interval_hours' => 8,
                        'duration_type'       => 'days',
                        'duration_value'      => 5,
                        'instructions'        => 'Antibiotic prophylaxis post-surgery. Complete the full course.',
                        'sort_order'          => 1,
                    ],
                    [
                        'medicine_name'       => 'Ibuprofen',
                        'medicine_type'       => 'Tablet',
                        'dosage'              => '1 Tablet',
                        'use_type'            => 'regular',
                        'meal_timing'         => 'after_meal',
                        'doses_per_day'       => 2,
                        'first_dose_time'     => '08:00',
                        'dose_interval_hours' => 12,
                        'duration_type'       => 'days',
                        'duration_value'      => 3,
                        'instructions'        => 'Post-operative pain relief. Take with food to reduce gastric irritation.',
                        'sort_order'          => 2,
                    ],
                    [
                        'medicine_name'       => 'Omeprazole',
                        'medicine_type'       => 'Capsule',
                        'dosage'              => '1 Capsule',
                        'use_type'            => 'regular',
                        'meal_timing'         => 'before_meal',
                        'doses_per_day'       => 1,
                        'first_dose_time'     => '08:00',
                        'dose_interval_hours' => 24,
                        'duration_type'       => 'days',
                        'duration_value'      => 5,
                        'instructions'        => 'Protects gastric lining while on NSAID therapy.',
                        'sort_order'          => 3,
                    ],
                    [
                        'medicine_name'       => 'Paracetamol',
                        'medicine_type'       => 'Tablet',
                        'dosage'              => '1 Tablet',
                        'use_type'            => 'sos',
                        'take_when'           => 'Pain',
                        'min_gap'             => '4 hours',
                        'max_doses_per_day'   => '4 doses',
                        'duration_type'       => 'days',
                        'duration_value'      => 5,
                        'instructions'        => 'Take only if pain occurs. Minimum gap of 4 hours between doses. Do not take more than 4 doses in one day.',
                        'sort_order'          => 4,
                    ],
                ],
            ],
        ];

        foreach ($templates as $tplData) {
            $items = $tplData['items'];
            unset($tplData['items']);

            /** @var MedicineTemplate $template */
            $template = MedicineTemplate::updateOrCreate(
                ['name' => $tplData['name']],
                $tplData
            );

            // Re-sync items each time
            $template->items()->delete();

            foreach ($items as $itemData) {
                $medicineName = $itemData['medicine_name'];
                $medicine     = $medicines->get($medicineName);

                // Compute frequency and frequency_times from doses_per_day
                $dosesPerDay = (int) ($itemData['doses_per_day'] ?? 1);
                $firstDose   = $itemData['first_dose_time'] ?? '08:00';
                $gapHours    = (int) ($itemData['dose_interval_hours'] ?? 8);

                $useType = $itemData['use_type'] ?? 'regular';

                if ($useType === 'sos') {
                    $frequency      = 'SOS';
                    $frequencyTimes = null;
                    $dosesPerDay    = 0;
                    $gapHours       = 0;
                    $firstDose      = null;
                } else {
                    $frequency      = MedicineTemplateItem::frequencyFromDoses($dosesPerDay);
                    $frequencyTimes = MedicineTemplateItem::autoTimings($dosesPerDay, $firstDose, $gapHours);
                }

                MedicineTemplateItem::create([
                    'id'                  => (string) Str::uuid(),
                    'medicine_template_id'=> $template->id,
                    'medicine_id'         => $medicine?->id,
                    'medicine_name'       => $itemData['medicine_name'],
                    'medicine_type'       => $itemData['medicine_type'] ?? $medicine?->type?->name,
                    'dosage'              => $itemData['dosage'] ?? null,
                    'use_type'            => $useType,
                    'take_when'           => $itemData['take_when'] ?? null,
                    'min_gap'             => $itemData['min_gap'] ?? null,
                    'max_doses_per_day'   => $itemData['max_doses_per_day'] ?? null,
                    'meal_timing'         => $itemData['meal_timing'] ?? null,
                    'doses_per_day'       => $dosesPerDay,
                    'first_dose_time'     => $firstDose,
                    'dose_interval_hours' => $gapHours,
                    'frequency'           => $frequency,
                    'frequency_times'     => $frequencyTimes,
                    'duration_type'       => $itemData['duration_type'] ?? 'days',
                    'duration_value'      => $itemData['duration_value'] ?? null,
                    'instructions'        => $itemData['instructions'] ?? null,
                    'sort_order'          => $itemData['sort_order'] ?? 0,
                ]);
            }

            $this->command->info("✓ Template seeded: {$template->name} ({$template->items()->count()} medicines)");
        }
    }
}
