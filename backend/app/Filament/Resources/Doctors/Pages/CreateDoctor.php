<?php

namespace App\Filament\Resources\Doctors\Pages;

use App\Filament\Resources\Doctors\DoctorResource;
use App\Models\DepartmentDoctor;
use App\Models\User;
use App\Services\DoctorCredentialsService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class CreateDoctor extends CreateRecord
{
    protected ?string $plainPassword = null;

    protected static string $resource = DoctorResource::class;

    protected static bool $canCreateAnother = false;

    protected string $view = 'filament.resources.doctors.pages.doctor-form';

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        $url = $this->previousUrl ?? $this->getResourceUrl();

        return \Filament\Actions\Action::make('cancel')
            ->label(__('filament-panels::resources/pages/create-record.form.actions.cancel.label'))
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Confirm Cancellation')
            ->modalDescription('Are you sure you want to cancel? Any unsaved changes will be lost.')
            ->modalSubmitActionLabel('Yes, cancel')
            ->modalCancelActionLabel('No, keep editing')
            ->action(fn () => $this->redirect($url, navigate: \Filament\Support\Facades\FilamentView::hasSpaMode($url)));
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return \App\Filament\Resources\Doctors\Schemas\DoctorForm::mutateFormDataBeforeSave($data);
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $this->plainPassword = $data['password'] ?? Str::random(12);

            // Check if user already exists
            $user = User::where('email', $data['email'])->first();

            if (! $user) {
                $user = User::create([
                    'name' => ($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''),
                    'email' => $data['email'],
                    'password' => Hash::make($this->plainPassword),
                    'phone' => $data['phone'] ?? null,
                    'email_verified_at' => now(),
                    'avatar' => $data['avatar'] ?? null,
                    'status' => \App\Enums\DoctorStatus::ACTIVE->value,
                ]);

                // Assign 'doctor' role to the user
                if (method_exists($user, 'assignRole')) {
                    $user->assignRole('doctor');
                } elseif (class_exists(Role::class)) {
                    $roleClass = Role::class;
                    $doctorRole = $roleClass::where('name', 'doctor')->first();
                    if ($doctorRole) {
                        $user->roles()->attach($doctorRole);
                    }
                }
            }

            $data['user_id'] = $user->id;
            unset($data['password'], $data['update_password']);

            // Create the Doctor record
            return $this->getModel()::create($data);
        });
    }

    protected function afterCreate(): void
    {
        $data = $this->form->getRawState() ?? [];
        $doctor = $this->record;

        // Handle department associations
        if (! empty($data['departments'])) {
            $savedPivotIds = [];
            foreach ($data['departments'] as $item) {
                if (! empty($item['id'])) {
                    $newPivot = DepartmentDoctor::create([
                        'doctor_id' => $doctor->id,
                        'department_id' => $item['id'],
                        'role' => $item['pivot']['role'] ?? null,
                        'order' => $item['pivot']['order'] ?? (DepartmentDoctor::where('doctor_id', $doctor->id)->max('order') ?? 0) + 1,
                    ]);
                    $savedPivotIds[] = $newPivot->id;
                }
            }
        }

        // Refresh and check for active availability
        $doctor->refresh();
        $credentialsService = app(DoctorCredentialsService::class);

        // Store data in session for Edit page to show modal
        if ($credentialsService->hasActiveAvailability($doctor)) {
            session([
                'show_create_credentials_modal' => true,
                'create_doctor_password' => $this->plainPassword,
                'create_doctor_id' => $doctor->id,
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
