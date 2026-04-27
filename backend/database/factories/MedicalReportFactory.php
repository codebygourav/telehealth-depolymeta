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

        return [
            'id' => (string) Str::uuid(),
            'patient_id' => Patient::inRandomOrder()->first()?->id ?? Patient::factory(),
            'doctor_id' => Doctor::inRandomOrder()->first()?->id ?? Doctor::factory(),
            'name' => fake()->sentence(3),
            'type' => fake()->randomElement($types),
            'description' => fake()->paragraph(),
            'report_date' => fake()->date(),
            'status' => fake()->randomElement(MedicalReportStatus::cases()),
            'is_public' => fake()->boolean(),
            'is_shared' => fake()->boolean(),
            'notes' => fake()->sentence(),
        ];
    }
}
