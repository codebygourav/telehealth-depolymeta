<?php

namespace Database\Seeders;

use App\Models\{Medicine, MedicineCategory, MedicineType};
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MedicineSeeder extends Seeder
{
    public function run(): void
    {
        $categories = MedicineCategory::all();
        $types = MedicineType::all();

        if ($categories->isEmpty() || $types->isEmpty()) {
            return;
        }

        $medicines = [
            [
                'name' => 'Paracetamol',
                'category' => 'Analgesics',
                'type' => 'Tablet',
                'price' => 50,
                'hospital_stock' => 500,
                'strength_options' => ['500 mg', '650 mg'],
                'dosage_options' => ['1 tablet', '0.5 tablet'],
                'frequency_options' => ['OD', 'BD', 'TDS', 'SOS'],
                'timing_options' => ['morning', 'afternoon', 'evening', 'night'],
                'meal_options' => ['before_meal', 'after_meal'],
                'field_rules' => ['strength', 'dosage', 'frequency', 'timing', 'meal'],
                'default_strength' => '650 mg',
                'default_dosage' => '1 tablet',
                'default_frequency' => 'BD',
                'default_meal' => 'after_meal',
                'spoken_aliases' => ['dolo', 'crocin', 'calpol'],
            ],
            [
                'name' => 'Amoxicillin',
                'category' => 'Antibiotics',
                'type' => 'Capsule',
                'price' => 250,
                'hospital_stock' => 100,
                'strength_options' => ['250 mg', '500 mg'],
                'dosage_options' => ['1 capsule'],
                'frequency_options' => ['BD', 'TDS'],
                'timing_options' => ['morning', 'afternoon', 'evening', 'night'],
                'meal_options' => ['after_meal'],
                'field_rules' => ['strength', 'dosage', 'frequency', 'meal', 'duration'],
                'default_strength' => '500 mg',
                'default_dosage' => '1 capsule',
                'default_frequency' => 'TDS',
                'default_meal' => 'after_meal',
                'spoken_aliases' => ['mox', 'amox'],
            ],
            [
                'name' => 'Cough Syrup',
                'category' => 'Antiseptics',
                'type' => 'Syrup',
                'price' => 150,
                'hospital_stock' => 50,
                'strength_options' => ['100 ml'],
                'dosage_options' => ['5 ml', '10 ml'],
                'frequency_options' => ['BD', 'TDS'],
                'timing_options' => ['morning', 'evening', 'night'],
                'meal_options' => ['before_meal', 'after_meal'],
                'field_rules' => ['dosage', 'frequency', 'meal', 'duration', 'remarks'],
                'default_strength' => '100 ml',
                'default_dosage' => '10 ml',
                'default_frequency' => 'TDS',
                'default_meal' => 'after_meal',
                'spoken_aliases' => ['grilinctus', 'benadryl'],
            ],
            [
                'name' => 'Omeprazole',
                'category' => 'Antacids',
                'type' => 'Capsule',
                'price' => 120,
                'hospital_stock' => 150,
                'strength_options' => ['20 mg', '40 mg'],
                'dosage_options' => ['1 capsule'],
                'frequency_options' => ['OD'],
                'timing_options' => ['morning'],
                'meal_options' => ['before_meal'],
                'field_rules' => ['strength', 'dosage', 'frequency', 'meal'],
                'default_strength' => '20 mg',
                'default_dosage' => '1 capsule',
                'default_frequency' => 'OD',
                'default_meal' => 'before_meal',
                'spoken_aliases' => ['omee', 'pantocid'],
            ],
            [
                'name' => 'Eye Drops',
                'category' => 'Analgesics',
                'type' => 'Drops',
                'price' => 90,
                'hospital_stock' => 80,
                'strength_options' => ['5 ml'],
                'dosage_options' => ['1 drop', '2 drops'],
                'frequency_options' => ['BD', 'TDS', 'SOS'],
                'timing_options' => ['morning', 'afternoon', 'evening', 'night'],
                'meal_options' => [],
                'field_rules' => ['dosage', 'frequency', 'application_area', 'remarks'],
                'default_strength' => '5 ml',
                'default_dosage' => '1 drop',
                'default_frequency' => 'BD',
                'spoken_aliases' => ['tear drops', 'carboxymethylcellulose'],
            ]
        ];

        foreach ($medicines as $med) {
            $category = $categories->where('name', $med['category'])->first();
            $type = $types->where('name', $med['type'])->first();

            Medicine::updateOrCreate(
                ['name' => $med['name']],
                [
                    'slug' => Str::slug($med['name']),
                    'category_id' => $category?->id,
                    'type_id' => $type?->id,
                    'price' => $med['price'],
                    'hospital_stock' => $med['hospital_stock'],
                    'quantity' => 1,
                    'batch_number' => 'BATCH-' . strtoupper(Str::random(6)),
                    'manufactured_date' => now()->subMonths(6),
                    'expiry_date' => now()->addYears(2),
                    'manufacturer' => 'Generic Pharma',
                    'strength_options' => $med['strength_options'] ?? null,
                    'dosage_options' => $med['dosage_options'] ?? null,
                    'frequency_options' => $med['frequency_options'] ?? null,
                    'timing_options' => $med['timing_options'] ?? null,
                    'meal_options' => $med['meal_options'] ?? null,
                    'field_rules' => $med['field_rules'] ?? null,
                    'default_strength' => $med['default_strength'] ?? null,
                    'default_dosage' => $med['default_dosage'] ?? null,
                    'default_frequency' => $med['default_frequency'] ?? null,
                    'default_meal' => $med['default_meal'] ?? null,
                    'spoken_aliases' => $med['spoken_aliases'] ?? null,
                ]
            );
        }
    }
}