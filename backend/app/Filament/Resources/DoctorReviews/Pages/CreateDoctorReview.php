<?php

namespace App\Filament\Resources\DoctorReviews\Pages;

use App\Filament\Resources\DoctorReviews\DoctorReviewResource;
use App\Models\FakerPatient;
use Filament\Resources\Pages\CreateRecord;

class CreateDoctorReview extends CreateRecord
{
    protected static string $resource = DoctorReviewResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // If review type is 'fake', create a faker patient and set faker_patient_id
        if (isset($data['review_type']) && $data['review_type'] === 'fake') {
            // Create the faker patient
            $fakerPatient = new FakerPatient([
                'name' => $data['faker_name'] ?? '',
                'age' => $data['faker_age'] ?? null,
                'address' => $data['faker_address'] ?? null,
            ]);

            // Set avatar if provided (will be saved via trait)
            if (!empty($data['faker_avatar'])) {
                $fakerPatient->avatar = $data['faker_avatar'];
            }

            // Save the faker patient (this will trigger trait to save avatar to module_documents)
            $fakerPatient->save();

            // Set the faker_patient_id and clear patient_id
            $data['faker_patient_id'] = $fakerPatient->id;
            $data['patient_id'] = null;

            // Remove the temporary faker fields from data
            unset($data['faker_name'], $data['faker_age'], $data['faker_address'], $data['faker_avatar']);
        } else {
            // For original reviews, ensure faker_patient_id is null
            $data['faker_patient_id'] = null;
        }

        // Ensure review_type is set (default to 'original' if not set)
        if (!isset($data['review_type'])) {
            $data['review_type'] = 'original';
        }

        return $data;
    }
}
