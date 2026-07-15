<?php

namespace Database\Seeders;

use App\Models\Doctor;
use Illuminate\Database\Seeder;

class DoctorAiTrainingSeeder extends Seeder
{
    public function run(): void
    {
        $trainingByEmail = [
            'mjoseph@gmail.com' => [
                'pronunciation_dictionary' => [
                    ['doctor_says' => 'Panta', 'ai_converts_to' => 'Pantoprazole'],
                    ['doctor_says' => 'PCM', 'ai_converts_to' => 'Paracetamol'],
                    ['doctor_says' => 'Monocef', 'ai_converts_to' => 'Ceftriaxone'],
                    ['doctor_says' => 'Rozu', 'ai_converts_to' => 'Rosuvastatin'],
                ],
                'speech_word_corrections' => [
                    ['heard_word' => 'mail', 'corrected_word' => 'meal'],
                    ['heard_word' => 'achee', 'corrected_word' => 'Azee'],
                    ['heard_word' => 'pan to puzzle', 'corrected_word' => 'Pantoprazole'],
                    ['heard_word' => 'met four min', 'corrected_word' => 'Metformin'],
                ],
                'medicine_shortcuts' => [
                    ['medicine' => 'Pantoprazole 40mg', 'shortcut' => 'Panta', 'priority' => 5],
                    ['medicine' => 'Paracetamol 650', 'shortcut' => 'PCM', 'priority' => 5],
                    ['medicine' => 'Azithromycin 500', 'shortcut' => 'AZM', 'priority' => 4],
                    ['medicine' => 'Metformin', 'shortcut' => 'MET', 'priority' => 5],
                ],
                'common_diagnoses' => [
                    'Hypertension',
                    'Diabetes',
                    'Viral Fever',
                    'GERD',
                    'Migraine',
                    'Asthma',
                    'COPD',
                    'UTI',
                ],
                'frequently_used_instructions' => [
                    'Take after food',
                    'Take before breakfast',
                    'Continue for 5 days',
                    'Drink plenty of water',
                    'Review after 1 week',
                    'Steam inhalation',
                    'Bed rest',
                    'Monitor blood sugar',
                    'Low salt diet',
                ],
                'procedures_investigations' => [
                    'CBC',
                    'LFT',
                    'KFT',
                    'Chest X-Ray',
                    'ECG',
                    'MRI Brain',
                    'CT Scan',
                    '2D Echo',
                ],
            ],
            'kjoseph@gmail.com' => [
                'pronunciation_dictionary' => [
                    ['doctor_says' => 'Ecosprin', 'ai_converts_to' => 'Ecosprin'],
                    ['doctor_says' => 'Diclo', 'ai_converts_to' => 'Diclofenac'],
                    ['doctor_says' => 'Cal D', 'ai_converts_to' => 'Calcium with Vitamin D'],
                    ['doctor_says' => 'Aceclo', 'ai_converts_to' => 'Aceclofenac'],
                ],
                'speech_word_corrections' => [
                    ['heard_word' => 'mail', 'corrected_word' => 'meal'],
                    ['heard_word' => 'dick low', 'corrected_word' => 'Diclo'],
                    ['heard_word' => 'ace claw', 'corrected_word' => 'Aceclo'],
                    ['heard_word' => 'cal dee', 'corrected_word' => 'Cal D'],
                ],
                'medicine_shortcuts' => [
                    ['medicine' => 'Aceclofenac 100mg', 'shortcut' => 'Aceclo', 'priority' => 5],
                    ['medicine' => 'Diclofenac Gel', 'shortcut' => 'Diclo', 'priority' => 4],
                    ['medicine' => 'Calcium with Vitamin D', 'shortcut' => 'Cal D', 'priority' => 4],
                    ['medicine' => 'Ecosprin 75', 'shortcut' => 'Ecosprin', 'priority' => 4],
                ],
                'common_diagnoses' => [
                    'Osteoarthritis Knee',
                    'Low Back Pain',
                    'Cervical Spondylosis',
                    'Shoulder Impingement',
                    'Tennis Elbow',
                    'Ankle Sprain',
                    'Fracture Follow-up',
                    'Post-op Rehabilitation',
                ],
                'frequently_used_instructions' => [
                    'Apply ice pack for 15 minutes thrice daily',
                    'Avoid squatting and stairs',
                    'Use knee brace during walking',
                    'Start physiotherapy from tomorrow',
                    'Weight bearing as tolerated',
                    'Review after 10 days',
                    'Do ROM exercises twice daily',
                    'Avoid heavy lifting for 2 weeks',
                ],
                'procedures_investigations' => [
                    'X-Ray Knee AP/Lateral',
                    'X-Ray Lumbar Spine',
                    'MRI Knee',
                    'MRI Spine',
                    'Serum Vitamin D',
                    'CRP',
                    'ESR',
                    'Bone Density Scan (DEXA)',
                ],
            ],
        ];

        foreach ($trainingByEmail as $email => $profile) {
            $doctor = Doctor::query()
                ->whereHas('user', fn($query) => $query->where('email', $email))
                ->first();

            if (! $doctor) {
                $this->command?->warn("DoctorAiTrainingSeeder skipped: doctor not found for {$email}");
                continue;
            }

            $doctor->forceFill([
                'ai_training_profile' => $profile,
            ])->save();
        }
    }
}
