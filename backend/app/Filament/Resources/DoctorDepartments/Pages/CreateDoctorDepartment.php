<?php

namespace App\Filament\Resources\DoctorDepartments\Pages;

use App\Filament\Resources\DoctorDepartments\DoctorDepartmentResource;
use App\Models\DepartmentDoctor;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Alignment;
use Filament\Notifications\Notification;

class CreateDoctorDepartment extends CreateRecord
{
    protected static string $resource = DoctorDepartmentResource::class;

    public function getFormActionsAlignment(): Alignment|string
    {
        return Alignment::End;
    }

    protected function afterCreate(): void
    {
        $this->saveAfterForm();
    }

    protected function afterSave(): void
    {
        $this->saveAfterForm();
    }

    protected function saveAfterForm(): void
    {
        $this->saveSymptoms();
        // Tabs are handled by relationship() in form
        // Media is handled by Model events
        $this->saveDoctors();
    }
    protected function saveDoctors(): void
    {
        $department = $this->record;
        $data = $this->form->getState();

        if (empty($data['doctors'])) {
            $department->doctors()->detach();
            return;
        }

        // Fetch existing doctors in the department
        $existing = DepartmentDoctor::where('department_id', $department->id)
            ->get()
            ->keyBy('doctor_id');

        // Collect new orders
        $orders = [];

        foreach ($data['doctors'] as $item) {
            if (empty($item['doctor_id'])) continue;

            $doctorId = $item['doctor_id'];
            $newOrder = (int) ($item['order'] ?? 1);

            // Check if some doctor already has this order
            $conflict = collect($orders)->search(fn($order) => $order === $newOrder);

            if ($conflict !== false) {
                // Swap: assign conflicting doctor the previous order of the current doctor
                $orders[$conflict] = $existing[$doctorId]->order ?? $newOrder;
            }

            $orders[$doctorId] = $newOrder;
        }

        // Create or update pivot records using DepartmentDoctor model to ensure UUID generation
        foreach ($data['doctors'] as $item) {
            if (empty($item['doctor_id'])) continue;

            $doctorId = $item['doctor_id'];
            DepartmentDoctor::updateOrCreate(
                [
                    'department_id' => $department->id,
                    'doctor_id' => $doctorId,
                ],
                [
                    'role'  => $item['role'] ?? null,
                    'order' => $orders[$doctorId],
                ]
            );
        }

        Notification::make()
            ->title('Doctor order updated')
            ->body('Conflicting doctor orders were automatically swapped.')
            ->success()
            ->send();
    }



    // saveFeaturedImage and saveTabsAndMedia removed

    protected function saveSymptoms(): void
    {
        $department = $this->record;
        $data = $this->form->getState();

        if (isset($data['symptom_ids'])) {
            $department->symptom_ids = is_array($data['symptom_ids']) ? array_values($data['symptom_ids']) : [];
            $department->save();
        }
    }
}