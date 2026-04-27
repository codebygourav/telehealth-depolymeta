<?php

namespace App\Filament\Resources\DoctorReviews\Pages;

use App\Filament\Resources\DoctorReviews\DoctorReviewResource;
use App\Models\FakerPatient;
use Filament\Resources\Pages\EditRecord;

class EditDoctorReview extends EditRecord
{
    protected static string $resource = DoctorReviewResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;

        // If it's a fake review, populate the faker fields
        if ($record->review_type === 'fake' && $record->faker_patient_id) {
            $fakerPatient = $record->fakerPatient;
            if ($fakerPatient) {
                $data['faker_name'] = $fakerPatient->name;
                $data['faker_age'] = $fakerPatient->age;
                $data['faker_address'] = $fakerPatient->address;
                $data['faker_avatar'] = $fakerPatient->avatar; // Load avatar from trait
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->record;

        // If review type is 'fake', update or create faker patient
        if (isset($data['review_type']) && $data['review_type'] === 'fake') {
            if ($record->faker_patient_id) {
                // Update existing faker patient
                $fakerPatient = FakerPatient::find($record->faker_patient_id);
                if ($fakerPatient) {
                    $fakerPatient->name = $data['faker_name'] ?? '';
                    $fakerPatient->age = $data['faker_age'] ?? null;
                    $fakerPatient->address = $data['faker_address'] ?? null;

                    // Update avatar if provided (will be saved via trait)
                    if (isset($data['faker_avatar'])) {
                        $fakerPatient->avatar = $data['faker_avatar'];
                    }

                    $fakerPatient->save(); // This will trigger trait to save avatar to module_documents
                }
            } else {
                // Create new faker patient
                $fakerPatient = new FakerPatient([
                    'name' => $data['faker_name'] ?? '',
                    'age' => $data['faker_age'] ?? null,
                    'address' => $data['faker_address'] ?? null,
                ]);

                // Set avatar if provided (will be saved via trait)
                if (!empty($data['faker_avatar'])) {
                    $fakerPatient->avatar = $data['faker_avatar'];
                }

                $fakerPatient->save(); // This will trigger trait to save avatar to module_documents
                $data['faker_patient_id'] = $fakerPatient->id;
            }

            // Clear patient_id for fake reviews
            $data['patient_id'] = null;

            // Remove the temporary faker fields from data
            unset($data['faker_name'], $data['faker_age'], $data['faker_address'], $data['faker_avatar']);
        } else {
            // For original reviews, ensure faker_patient_id is null
            $data['faker_patient_id'] = null;
        }

        // Ensure review_type is set
        if (!isset($data['review_type'])) {
            $data['review_type'] = 'original';
        }

        return $data;
    }
}
