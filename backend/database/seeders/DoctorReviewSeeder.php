<?php

namespace Database\Seeders;

use App\Models\{Doctor, DoctorReview, Patient, FakerPatient};
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/*
 * Custom static data for both fake and original reviews.
 */

class DoctorReviewSeeder extends Seeder
{
    public function run(): void
    {
        // Fetch all doctors & patients
        $doctors = Doctor::with('user')->get();
        $patients = Patient::with('user')
            ->get();

        if ($doctors->isEmpty()) {
            $this->command->warn('No doctors found. Please seed doctors first.');
            return;
        }

        if ($patients->isEmpty()) {
            $this->command->warn('No patients found. Some original reviews will be skipped.');
        }

        $this->command->info('Creating doctor reviews using custom data...');

        // Custom test data for fake patients and reviews
        $customFakeReviewData = [
            [
                'faker_patient' => [
                    'name' => 'Amit Kumar',
                    'age' => 42,
                    'address' => 'Delhi, India'
                ],
                'title' => 'Very Compassionate and Skilled',
                'content' => '{doctor} was very compassionate throughout my checkup. Highly recommend!',
                'rating' => 5,
                'is_featured' => true,
            ],
            [
                'faker_patient' => [
                    'name' => 'Preeti Sharma',
                    'age' => 36,
                    'address' => 'Lucknow, UP'
                ],
                'title' => 'Quick Diagnosis',
                'content' => '{doctor} quickly diagnosed my issue and prescribed effective treatment.',
                'rating' => 4,
                'is_featured' => true,
            ],
            [
                'faker_patient' => [
                    'name' => 'Karan Singh',
                    'age' => 55,
                    'address' => 'Jalandhar, Punjab'
                ],
                'title' => 'Highly Professional',
                'content' => 'Great experience with {doctor}. She listened to my concerns patiently.',
                'rating' => 5,
                'is_featured' => true,
            ],
            [
                'faker_patient' => [
                    'name' => 'Meena Dubey',
                    'age' => 61,
                    'address' => 'Mumbai, Maharashtra'
                ],
                'title' => 'Detailed Explanation',
                'content' => '{doctor} explained every step of the procedure in detail.',
                'rating' => 4,
                'is_featured' => false,
            ],
            [
                'faker_patient' => [
                    'name' => 'Vikram Patel',
                    'age' => 30,
                    'address' => 'Ahmedabad, Gujarat'
                ],
                'title' => 'Friendly and Helpful',
                'content' => 'I felt very comfortable with {doctor}. Would visit again.',
                'rating' => 5,
                'is_featured' => false,
            ],
        ];

        // Custom test data for original reviews (from real patients)
        $customOriginalReviewData = [
            [
                'title' => 'Outstanding Service',
                'content' => '{doctor} provided outstanding consultation and support!',
                'rating' => 5,
                'is_featured' => true,
            ],
            [
                'title' => 'Very Satisfied',
                'content' => 'It was a great pleasure to meet {doctor}. My problem was understood well.',
                'rating' => 4,
                'is_featured' => true,
            ],
            [
                'title' => 'Efficient and Knowledgeable',
                'content' => '{doctor} handled my appointment with utmost efficiency.',
                'rating' => 5,
                'is_featured' => false,
            ],
            [
                'title' => 'Caring Attitude',
                'content' => 'I appreciated {doctor}\'s caring attitude during my consultation.',
                'rating' => 4,
                'is_featured' => false,
            ],
        ];

        // Create FAKE reviews from static data
        $this->command->info('Creating fake reviews (custom static data)...');
        foreach ($customFakeReviewData as $i => $fake) {
            $doctor = $doctors->random();

            // Store faker patient
            $fakerPatient = new FakerPatient();
            $fakerPatient->id = Str::uuid();
            $fakerPatient->name = $fake['faker_patient']['name'];
            $fakerPatient->age = $fake['faker_patient']['age'];
            $fakerPatient->address = $fake['faker_patient']['address'];
            $fakerPatient->save();

            // Create review
            $review = new DoctorReview();
            $review->id = Str::uuid();
            $review->review_type = 'fake';
            $review->doctor_id = $doctor->id;
            $review->faker_patient_id = $fakerPatient->id;
            $review->patient_id = null;
            $review->title = $fake['title'];
            $review->content = str_replace('{doctor}', $doctor->user->name ?? 'Doctor', $fake['content']);
            $review->rating = $fake['rating'];
            $review->is_active = true;
            $review->is_featured = $fake['is_featured'];
            // Random date within last ~3 months
            $review->created_at = Carbon::now()->subDays(rand(1, 90));
            $review->updated_at = Carbon::now()->subDays(rand(1, 90));
            $review->save();

            $this->command->line("  ✓ Created FAKE review: {$review->title} for {$doctor->user->name}");
        }

        // Create ORIGINAL reviews based on static data and real patients
        $this->command->info('Creating original reviews (custom static data)...');

        if ($patients->isEmpty()) {
            $this->command->warn('  ⚠ Skipping original reviews - no patients available');
        } else {
            $patientCount = min(count($customOriginalReviewData), $patients->count());
            $usedPatientIds = [];

            for ($i = 0; $i < $patientCount; $i++) {
                $doctor = $doctors->random();
                // Ensure each review uses a unique patient (if possible)
                $patient = $patients->whereNotIn('id', $usedPatientIds)->random();
                $usedPatientIds[] = $patient->id;
                $template = $customOriginalReviewData[$i];

                $review = new DoctorReview();
                $review->id = Str::uuid();
                $review->review_type = 'original';
                $review->doctor_id = $doctor->id;
                $review->patient_id = $patient->id;
                $review->faker_patient_id = null;
                $review->title = $template['title'];
                $review->content = str_replace('{doctor}', $doctor->user->name ?? 'Doctor', $template['content']);
                $review->rating = $template['rating'];
                $review->is_active = true;
                $review->is_featured = $template['is_featured'];
                $review->created_at = Carbon::now()->subDays(rand(1, 90));
                $review->updated_at = Carbon::now()->subDays(rand(1, 90));
                $review->save();

                $patientName = $patient->first_name . ' ' . $patient->last_name;
                $this->command->line("  ✓ Created ORIGINAL review: {$review->title} from {$patientName} for {$doctor->user->name}");
            }
        }

        // Summary output
        $totalReviews = DoctorReview::count();
        $fakeReviews = DoctorReview::where('review_type', 'fake')->count();
        $originalReviews = DoctorReview::where('review_type', 'original')->count();

        $this->command->info("✓ Review seeding completed using custom data!");
        $this->command->info("  Total reviews: {$totalReviews}");
        $this->command->info("  Fake reviews: {$fakeReviews}");
        $this->command->info("  Original reviews: {$originalReviews}");
    }
}