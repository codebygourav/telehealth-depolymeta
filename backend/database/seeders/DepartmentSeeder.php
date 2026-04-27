<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Department, DepartmentTab, Symptom};
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        // Disable foreign key constraints and truncate for a clean seed
        Schema::disableForeignKeyConstraints();
        Department::truncate();
        DepartmentTab::truncate();
        Schema::enableForeignKeyConstraints();

        $departments = [
            [
                'name' => 'Cardiology',
                'description' => 'Heart and circulatory system health and diseases.',
                'stamp' => 'stamps/cardiology_stamp.png',
                'symptoms' => ['Chest Pain', 'High Fever'],
                'is_tab_layout' => true,
                'tabs' => [
                    ['tab_title' => 'Overview', 'tab_content' => 'Comprehensive care for your heart.'],
                    ['tab_title' => 'Services', 'tab_content' => 'ECG, Echo, Angiography and more.'],
                ],
            ],
            [
                'name' => 'Neurology',
                'description' => 'Specialized care for disorders of the nervous system.',
                'stamp' => 'stamps/neurology_stamp.png',
                'symptoms' => ['Severe Headache', 'Blurred Vision'],
                'is_tab_layout' => true,
                'tabs' => [
                    ['tab_title' => 'Core Focus', 'tab_content' => 'Brain, spinal cord and nerves.'],
                ],
            ],
            [
                'name' => 'Orthopedics',
                'description' => 'Musculoskeletal system including bones, joints, and ligaments.',
                'stamp' => 'stamps/orthopedics_stamp.png',
                'symptoms' => ['Joint Pain'],
                'is_tab_layout' => false,
            ],
            [
                'name' => 'Pediatrics',
                'description' => 'Medical care for infants, children, and adolescents.',
                'stamp' => 'stamps/pediatrics_stamp.png',
                'symptoms' => ['High Fever', 'Persistent Cough'],
                'is_tab_layout' => true,
                'tabs' => [
                    ['tab_title' => 'Child Care', 'tab_content' => 'Dedicated care for the little ones.'],
                ],
            ],
            [
                'name' => 'Dermatology',
                'description' => 'Expert treatment for skin, hair, and nail conditions.',
                'stamp' => 'stamps/dermatology_stamp.png',
                'symptoms' => ['Skin Rash'],
                'is_tab_layout' => false,
            ],
            [
                'name' => 'Ophthalmology',
                'description' => 'Diagnosis and treatment of eye disorders.',
                'stamp' => 'stamps/ophthalmology_stamp.png',
                'symptoms' => ['Blurred Vision'],
                'is_tab_layout' => true,
                'tabs' => [
                    ['tab_title' => 'Vision Clinic', 'tab_content' => 'Advanced eye care solutions.'],
                ],
            ],
            [
                'name' => 'Gastroenterology',
                'description' => 'Digestive system and its disorders.',
                'stamp' => 'stamps/gastro_stamp.png',
                'symptoms' => ['Abdominal Pain'],
                'is_tab_layout' => false,
            ],
            [
                'name' => 'Oncology',
                'description' => 'Prevention, diagnosis, and treatment of cancer.',
                'stamp' => 'stamps/oncology_stamp.png',
                'symptoms' => ['Persistent Cough'],
                'is_tab_layout' => true,
                'tabs' => [
                    ['tab_title' => 'Therapy', 'tab_content' => 'Compassionate cancer care.'],
                ],
            ],
            [
                'name' => 'Psychiatry',
                'description' => 'Diagnosis, prevention, and treatment of mental disorders.',
                'stamp' => 'stamps/psychiatry_stamp.png',
                'symptoms' => ['Acute Anxiety'],
                'is_tab_layout' => false,
            ],
            [
                'name' => 'ENT',
                'description' => 'Care for Ear, Nose, and Throat conditions.',
                'stamp' => 'stamps/ent_stamp.png',
                'symptoms' => ['Sore Throat'],
                'is_tab_layout' => true,
                'tabs' => [
                    ['tab_title' => 'Otolaryngology', 'tab_content' => 'Specialized ENT services.'],
                ],
            ],
        ];

        foreach ($departments as $data) {
            $tabs = $data['tabs'] ?? [];
            $symptomNames = $data['symptoms'] ?? [];

            // Get symptom IDs from the Symptoms table
            $symptomIds = Symptom::whereIn('name', $symptomNames)->pluck('id')->toArray();

            // Prepare department data
            $departmentData = [
                'description' => $data['description'],
                'is_tab_layout' => $data['is_tab_layout'],
                'department_stamp' => $data['stamp'],
                'symptom_ids' => $symptomIds,
            ];

            // Create or update department
            $department = Department::updateOrCreate(
                ['name' => $data['name']],
                $departmentData
            );

            // Create tabs if any
            if ($data['is_tab_layout'] && !empty($tabs)) {
                // Clear existing tabs for this department
                $department->tabs()->delete();

                foreach ($tabs as $index => $tab) {
                    $department->tabs()->create([
                        'tab_title' => $tab['tab_title'],
                        'tab_content' => $tab['tab_content'],
                        'order' => $index + 1,
                    ]);
                }
            }
        }
    }
}