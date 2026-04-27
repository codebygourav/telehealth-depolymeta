<?php

namespace Database\Seeders;

use App\Models\{Department, DoctorAvailability, ModuleDocument, Symptom, User, Patient, Doctor};
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FactorySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create 5 Departments if none exist
        if (Department::count() < 5) {
            $departments = Department::factory()->count(5)->create();
        } else {
            $departments = Department::take(5)->get();
        }

        // 2. Create 5 Symptoms if none exist
        if (Symptom::count() < 5) {
            $symptoms = Symptom::factory()->count(5)->create();
        } else {
            $symptoms = Symptom::take(5)->get();
        }

        // Attach some symptoms and images if they don't have them
        $departments->each(function ($dept) use ($symptoms) {
            if (empty($dept->symptom_ids)) {
                $dept->update([
                    'symptom_ids' => $symptoms->random(min(2, $symptoms->count()))->pluck('id')->toArray()
                ]);
            }

            if (!$dept->moduleDocuments()->where('name', 'featured_image')->exists()) {
                ModuleDocument::create([
                    'model_type' => Department::class,
                    'model_id' => $dept->id,
                    'name' => 'featured_image',
                    'files' => ['https://raw.githubusercontent.com/mantinedev/mantine/master/.demo/images/bg-1.png'],
                ]);
            }
        });

        $symptoms->each(function ($symptom) {
            if (!$symptom->moduleDocuments()->where('name', 'featured_image')->exists()) {
                ModuleDocument::create([
                    'model_type' => Symptom::class,
                    'model_id' => $symptom->id,
                    'name' => 'featured_image',
                    'files' => ['https://raw.githubusercontent.com/mantinedev/mantine/master/.demo/images/bg-2.png'],
                ]);
            }
        });

        // 3. Create 5 Doctors if none exist
        if (Doctor::count() < 5) {
            Doctor::factory()->count(5)->create()->each(function ($doctor) use ($departments) {
                // Assign 'doctor' role to the associated user
                $doctor->user->role = 'doctor';
                $doctor->user->save();

                // Link to a department
                $doctor->departments()->attach($departments->random()->id, [
                    'id' => (string) Str::uuid(),
                    'role' => 'Consultant',
                    'order' => 1
                ]);

                // Add a fake avatar for doctor
                ModuleDocument::create([
                    'model_type' => User::class,
                    'model_id' => $doctor->user->id,
                    'name' => 'avatar',
                    'files' => ['https://i.pravatar.cc/150?u=' . $doctor->user->id],
                ]);

                // Create some availability slots for the doctor
                DoctorAvailability::create([
                    'doctor_id' => $doctor->id,
                    'day_of_week' => 'monday',
                    'start_time' => '09:00',
                    'end_time' => '12:00',
                    'capacity' => 5,
                    'consultation_type' => 'in-person',
                    'is_available' => true,
                    'opd_type' => 'general',
                    'consultation_fee' => 500
                ]);
            });
        }

        // 4. Create 5 Patients if none exist
        if (Patient::count() < 5) {
            Patient::factory()->count(5)->create()->each(function ($patient) {
                // Assign 'patient' role to the associated user
                $patient->user->role = 'patient';
                $patient->user->save();

                // Add a fake avatar for patient
                ModuleDocument::create([
                    'model_type' => User::class,
                    'model_id' => $patient->user->id,
                    'name' => 'avatar',
                    'files' => ['https://i.pravatar.cc/150?u=' . $patient->user->id],
                ]);
            });
        }
    }
}