<?php

namespace Database\Factories;

use App\Models\MedicalReport;
use App\Models\Patient;
use App\Models\Doctor;
use App\Enums\MedicalReportStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MedicalReportFactory extends Factory
{
    protected $model = MedicalReport::class;

    public function definition(): array
    {
        $types = ['lab_report', 'radiology', 'prescription', 'other'];
        $faker = $this->faker;

        return [
            'id' => (string) Str::uuid(),
            'patient_id' => Patient::inRandomOrder()->first()?->id ?? Patient::factory(),
            'doctor_id' => Doctor::inRandomOrder()->first()?->id ?? Doctor::factory(),
            'name' => $faker->sentence(3),
            'type' => $faker->randomElement($types),
            'description' => $faker->paragraph(),
            'report_date' => $faker->date(),
            'status' => $faker->randomElement(MedicalReportStatus::cases()),
            'is_public' => $faker->boolean(),
            'is_shared' => $faker->boolean(),
            'notes' => $faker->sentence(),
        ];
    }
}