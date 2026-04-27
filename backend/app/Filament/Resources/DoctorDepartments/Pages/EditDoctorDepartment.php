<?php

namespace App\Filament\Resources\DoctorDepartments\Pages;

use Filament\Notifications\Notification;
use App\Filament\Resources\DoctorDepartments\DoctorDepartmentResource;
use App\Models\DepartmentDoctor;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Alignment;

class EditDoctorDepartment extends EditRecord
{
    protected static string $resource = DoctorDepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    public function getFormActionsAlignment(): Alignment|string
    {
        return Alignment::End;
    }

    protected function afterSave(): void
    {
        $this->saveSymptoms();
        // Tabs handled by relationship()
        // Media handled by Model
        $this->saveDoctors();
    }

    // saveTabs and saveMedia removed
    // convertUrlToRelativePath removed

    protected function saveSymptoms(): void
    {
        $department = $this->record;
        $data = $this->form->getState();

        if (isset($data['symptom_ids'])) {
            $department->symptom_ids = is_array($data['symptom_ids']) ? array_values($data['symptom_ids']) : [];
            $department->save();
        }
    }

    protected function saveDoctors(): void
    {
        $department = $this->record;
        $data = $this->form->getState();

        if (empty($data['doctors'])) {
            $department->doctors()->detach();
            return;
        }

        // Fetch existing orders from DB
        $existingOrders = DepartmentDoctor::where('department_id', $department->id)
            ->pluck('order', 'doctor_id')
            ->toArray();

        $orders = []; // order number => doctor_id

        $syncData = [];

        foreach ($data['doctors'] as $item) {
            if (empty($item['doctor_id'])) continue;

            $doctorId = $item['doctor_id'];
            $desiredOrder = max(1, (int)($item['order'] ?? 1));

            if (isset($orders[$desiredOrder])) {
                $otherDoctorId = $orders[$desiredOrder];
                $oldOrder = $existingOrders[$doctorId] ?? null;
                $orders[$desiredOrder] = $doctorId;

                if ($oldOrder) {
                    $orders[$oldOrder] = $otherDoctorId;
                } else {
                    $newFreeOrder = max(array_keys($orders)) + 1;
                    $orders[$newFreeOrder] = $otherDoctorId;
                }
            } else {
                $orders[$desiredOrder] = $doctorId;
            }

            $syncData[$doctorId] = [
                'role'  => $item['role'] ?? null,
                'order' => $desiredOrder,
            ];
        }

        // Ensure syncData uses the final orders after swaps
        foreach ($syncData as $doctorId => &$dataItem) {
            $dataItem['order'] = array_search($doctorId, $orders);
        }

        // Get existing doctor IDs for this department
        $existingDoctorIds = DepartmentDoctor::where('department_id', $department->id)
            ->pluck('doctor_id')
            ->toArray();

        // Get new doctor IDs from sync data
        $newDoctorIds = array_keys($syncData);

        // Detach doctors that are no longer in the list
        $doctorsToDetach = array_diff($existingDoctorIds, $newDoctorIds);
        if (!empty($doctorsToDetach)) {
            DepartmentDoctor::where('department_id', $department->id)
                ->whereIn('doctor_id', $doctorsToDetach)
                ->delete();
        }

        // Update or create doctor-department relationships
        foreach ($syncData as $doctorId => $pivotData) {
            DepartmentDoctor::updateOrCreate(
                [
                    'department_id' => $department->id,
                    'doctor_id' => $doctorId,
                ],
                [
                    'role' => $pivotData['role'],
                    'order' => $pivotData['order'],
                ]
            );
        }

        if (count($syncData) > 0) {
            Notification::make()
                ->title('Doctor order updated')
                ->body('Conflicting orders were automatically swapped.')
                ->success()
                ->send();
        }
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $department = $this->record;

        $data['doctors'] = $department->doctors()
            ->get()
            ->map(function ($doctor) {
                return [
                    'doctor_id' => $doctor->id,
                    'role'      => $doctor->pivot->role,
                    'order'     => $doctor->pivot->order,
                ];
            })
            ->toArray();

        return $data;
    }
}
