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
            ['name' => 'Amoxicillin', 'category' => 'Antibiotics', 'type' => 'Capsule', 'price' => 250, 'hospital_stock' => 100],
            ['name' => 'Paracetamol', 'category' => 'Analgesics', 'type' => 'Tablet', 'price' => 50, 'hospital_stock' => 500],
            ['name' => 'Ibuprofen', 'category' => 'Analgesics', 'type' => 'Tablet', 'price' => 80, 'hospital_stock' => 300],
            ['name' => 'Aspirin', 'category' => 'Analgesics', 'type' => 'Tablet', 'price' => 40, 'hospital_stock' => 400],
            ['name' => 'Cough Syrup', 'category' => 'Antiseptics', 'type' => 'Syrup', 'price' => 150, 'hospital_stock' => 50],
            ['name' => 'Vitamin C', 'category' => 'Vitamins', 'type' => 'Tablet', 'price' => 100, 'hospital_stock' => 200],
            ['name' => 'Omeprazole', 'category' => 'Antacids', 'type' => 'Capsule', 'price' => 120, 'hospital_stock' => 150],
            ['name' => 'Insulin', 'category' => 'Supplements', 'type' => 'Injection', 'price' => 1200, 'hospital_stock' => 30],
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
                ]
            );
        }
    }
}