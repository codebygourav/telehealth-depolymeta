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
            DoctorAiTrainingSeeder::class,
            DoctorAvailabilitySeeder::class,
            RolesSeeder::class,
            PatientSeeder::class,
            AppointmentSeeder::class,
            VideoConsultationSeeder::class,
            DoctorReviewSeeder::class,
            AdvertisementSeeder::class,
            DisplayScreenSettingSeeder::class,
            DisplayScreenSeeder::class,
            DisplayEventSeeder::class,
            SettingsSeeder::class,
            VaccinationModuleSeeder::class,
            DietTemplateSeeder::class,
            MedicineTemplateSeeder::class,
            QueueLogsSeeder::class,
            MedicalReportSeeder::class,
        ]);
    }
}
