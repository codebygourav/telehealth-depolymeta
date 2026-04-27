<?php

namespace Database\Seeders;

use App\Models\MedicineType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MedicineTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Tablet',
            'Capsule',
            'Syrup',
            'Injection',
            'Ointment',
            'Drops',
            'Inhaler',
            'Powder',
        ];

        foreach ($types as $type) {
            MedicineType::updateOrCreate(
                ['name' => $type],
                ['slug' => Str::slug($type)]
            );
        }
    }
}
