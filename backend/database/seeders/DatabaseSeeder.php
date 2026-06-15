<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\{User};

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {

        $this->call([
            UserSeeder::class,
            SymptomSeeder::class,
            DepartmentSeeder::class,
            MedicineCategorySeeder::class,
            MedicineTypeSeeder::class,
            MedicineSeeder::class,
            DoctorSeeder::class,
            DoctorAvailabilitySeeder::class,
            RolesSeeder::class,
            PatientSeeder::class,
            AppointmentSeeder::class,
            VideoConsultationSeeder::class,
            DoctorReviewSeeder::class,
            AdvertisementSeeder::class,
            SettingsSeeder::class,
            // MedicalReportSeeder::class,
        ]);
    }
}
