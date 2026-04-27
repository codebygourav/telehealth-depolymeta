<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Symptom;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class SymptomSeeder extends Seeder
{
    public function run(): void
    {
        $symptoms = [
            [
                'name' => 'Chest Pain',
                'description' => 'Discomfort or pain in the chest area, potentially related to heart or lung issues.',
            ],
            [
                'name' => 'Severe Headache',
                'description' => 'Intense pain in the head, may be associated with migraines or neurological conditions.',
            ],
            [
                'name' => 'Joint Pain',
                'description' => 'Aches and soreness in junctions of bones, common in arthritis or orthopedic injuries.',
            ],
            [
                'name' => 'High Fever',
                'description' => 'Elevated body temperature often indicating an underlying infection or inflammation.',
            ],
            [
                'name' => 'Skin Rash',
                'description' => 'Redness, itching, or eruption on the skin, often seen in dermatological conditions.',
            ],
            [
                'name' => 'Blurred Vision',
                'description' => 'Decrease in eyesight clarity, which could be related to eye strain or optical disorders.',
            ],
            [
                'name' => 'Abdominal Pain',
                'description' => 'Discomfort in the stomach region, commonly associated with digestive issues.',
            ],
            [
                'name' => 'Persistent Cough',
                'description' => 'Ongoing cough that may indicate respiratory infections or chronic conditions.',
            ],
            [
                'name' => 'Acute Anxiety',
                'description' => 'Feelings of worry, nervousness, or unease, typically about an imminent event.',
            ],
            [
                'name' => 'Sore Throat',
                'description' => 'Pain, scratchiness or irritation of the throat that often worsens when you swallow.',
            ],
        ];

        foreach ($symptoms as $data) {
            Symptom::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                ]
            );
        }
    }
}
