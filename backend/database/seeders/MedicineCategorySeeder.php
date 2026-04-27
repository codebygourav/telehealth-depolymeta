<?php

namespace Database\Seeders;

use App\Models\MedicineCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MedicineCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Antibiotics',
            'Analgesics',
            'Antipyretics',
            'Antiseptics',
            'Antacids',
            'Antivirals',
            'Vitamins',
            'Supplements',
        ];

        foreach ($categories as $category) {
            MedicineCategory::updateOrCreate(
                ['name' => $category],
                ['slug' => Str::slug($category)]
            );
        }
    }
}
