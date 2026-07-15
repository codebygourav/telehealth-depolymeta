<?php

namespace App\Filament\Resources\Doctors\Pages;

use App\Filament\Resources\Doctors\DoctorResource;
use App\Models\DepartmentDoctor;
use App\Services\DoctorCredentialsService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Alignment;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class EditDoctor extends EditRecord
{
    protected static string $resource = DoctorResource::class;

    protected ?string $updatedPassword = null;

    public bool $showCredentialModal = false;

    public string $modalType = '';

    // ==== New property for assigning role ====
    public ?string $selectedRole = null;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Set initial role from user or null
        $this->selectedRole = $this->record->user?->roles()->first()?->name ?? null;

        // Check if redirected from Create page
        if (session('show_create_credentials_modal') && session('create_doctor_id') == $this->record->id) {
            $this->showCredentialModal = true;
            $this->modalType = 'create';
        }

        // Check if availability was just added
        if (session('availability_just_added') && session('availability_doctor_id') == $this->record->id) {
            // Generate new password for sending credentials
            $newPassword = Str::random(12);
            session(['availability_new_password' => $newPassword]);
            $this->showCredentialModal = true;
            $this->modalType = 'availability';
        }
    }

    protected string $view = 'filament.resources.doctors.pages.doctor-form';

    protected function getCancelFormAction(): Action
    {
        $url = $this->previousUrl ?? $this->getResourceUrl();

        return Action::make('cancel')
            ->label(__('filament-panels::resources/pages/edit-record.form.actions.cancel.label'))
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Confirm Cancellation')
            ->modalDescription('Are you sure you want to cancel? Any unsaved changes will be lost.')
            ->modalSubmitActionLabel('Yes, cancel')
            ->modalCancelActionLabel('No, keep editing')
            ->action(fn() => $this->redirect($url, navigate: \Filament\Support\Facades\FilamentView::hasSpaMode($url)));
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return \App\Filament\Resources\Doctors\Schemas\DoctorForm::mutateFormDataBeforeFill($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = \App\Filament\Resources\Doctors\Schemas\DoctorForm::mutateFormDataBeforeSave($data);
        $doctor = $this->record;

        if ($doctor->user) {
            if (isset($data['email'])) {
                $doctor->user->update([
                    'email' => $data['email'],
                    'name' => $data['first_name'] . ' ' . $data['last_name'],
                    'phone' => preg_replace('/[\s\-()]/', '', $data['phone'] ?? ''),
                ]);
            }

            if (! empty($data['avatar'])) {
                $doctor->user->update(['avatar' => $data['avatar']]);
            }

            if (! empty($data['update_password']) && ! empty($data['password'])) {
                $this->updatedPassword = $data['password'];
                $doctor->user->password = Hash::make($this->updatedPassword);
                $doctor->user->save();
            }
        }

        unset($data['password'], $data['update_password']);

        return $data;
    }

    protected function afterSave(): void
    {
        $doctor = $this->record;
        $data = $this->form->getState();

        // Handle department associations manually to ensure UUID generation and proper sync
        if (isset($data['departments'])) {
            $savedPivotIds = [];

            if (! empty($data['departments'])) {
                foreach ($data['departments'] as $item) {
                    if (! empty($item['id'])) {
                        $pivotData = [
                            'doctor_id' => $doctor->id,
                            'department_id' => $item['id'],
                            'role' => $item['pivot']['role'] ?? null,
                            'order' => $item['pivot']['order'] ?? (DepartmentDoctor::where('doctor_id', $doctor->id)->max('order') ?? 0) + 1,
                        ];

                        if (! empty($item['_pivot_id'])) {
                            // Update existing pivot record
                            DepartmentDoctor::where('id', $item['_pivot_id'])->update($pivotData);
                            $savedPivotIds[] = $item['_pivot_id'];
                        } else {
                            // Create new pivot record
                            $newPivot = DepartmentDoctor::create($pivotData);
                            $savedPivotIds[] = $newPivot->id;
                        }
                    }
                }
            }

            // Clean up any pivot records for this doctor that are no longer in the form (including old duplicates)
            DepartmentDoctor::where('doctor_id', $doctor->id)
                ->whereNotIn('id', $savedPivotIds)
                ->forceDelete();
        }
        // ==== Assign role to doctor ====
        if ($doctor->user && $this->selectedRole) {
            // Remove existing roles and assign new one if changed
            $currentRole = $doctor->user->roles()->pluck('name')->first();
            if ($currentRole !== $this->selectedRole) {
                $doctor->user->syncRoles([$this->selectedRole]);
            }
        }

        if ($this->updatedPassword && $this->record->user?->email) {
            session(['edit_doctor_password' => $this->updatedPassword]);
            $this->showCredentialModal = true;
            $this->modalType = 'update';
        }
    }

    public function sendCredentials(): void
    {
        $password = session('edit_doctor_password')
            ?? session('create_doctor_password')
            ?? session('availability_new_password');

        if (! $password) {
            Notification::make()->danger()->title('Error')->body('Password not found.')->send();
            $this->closeModal();

            return;
        }

        // If this is from availability modal, update the user's password first
        if ($this->modalType === 'availability' && session('availability_new_password')) {
            $this->record->user->password = Hash::make($password);
            $this->record->user->save();
        }

        $credentialsService = app(DoctorCredentialsService::class);

        if ($credentialsService->sendCredentials($this->record, $password)) {
            Notification::make()
                ->success()
                ->title('Credentials Sent!')
                ->body("Email sent to {$this->record->user->email}")
                ->send();
        } else {
            Notification::make()->danger()->title('Failed')->body('Could not send email.')->send();
        }

        $this->closeModal();
    }

    public function sendCredentialsWithNewPassword(): void
    {
        if (! $this->record->user?->email) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Doctor does not have an email address.')
                ->send();

            return;
        }

        $credentialsService = app(DoctorCredentialsService::class);

        // Generate new password and update user
        $newPassword = Str::random(12);
        $this->record->user->password = Hash::make($newPassword);
        $this->record->user->save();

        // Send credentials email
        if ($credentialsService->sendCredentials($this->record, $newPassword)) {
            Notification::make()
                ->success()
                ->title('Credentials Sent!')
                ->body("New password generated and email sent to {$this->record->user->email}")
                ->send();
        } else {
            Notification::make()
                ->danger()
                ->title('Failed')
                ->body('Could not send email.')
                ->send();
        }
    }

    public function closeModal(): void
    {
        $this->showCredentialModal = false;
        $this->modalType = '';
        session()->forget([
            'edit_doctor_password',
            'create_doctor_password',
            'show_create_credentials_modal',
            'create_doctor_id',
            'availability_just_added',
            'availability_doctor_id',
            'availability_new_password',
        ]);
    }

    protected function getHeaderActions(): array
    {
        $hasUser = $this->record->user !== null;

        return [
            Action::make('aiTraining')
                ->label('AI Training')
                ->icon('heroicon-o-cpu-chip')
                ->url(fn() => DoctorResource::getUrl('ai-training', ['record' => $this->record])),

            Action::make('manageAvailability')
                ->label('Manage Availability')
                ->icon('heroicon-o-calendar-days')
                ->url(fn() => DoctorResource::getUrl('availability', ['record' => $this->record])),

            Action::make('sendCredentials')
                ->label('Send Credentials')
                ->icon('heroicon-o-envelope')
                ->requiresConfirmation()
                ->modalHeading('Send Login Credentials')
                ->modalDescription(fn() => "Generate a new password and send login credentials to {$this->record->user?->email}?")
                ->modalSubmitActionLabel('Send Email')
                ->action(fn() => $this->sendCredentialsWithNewPassword())
                ->visible(fn() => $hasUser),

            // ==== Assign Role Action ====
            // Action::make('assignRole')
            //     ->label('Assign Role')
            //     ->icon('heroicon-o-user-circle')
            //     ->form([
            //         \Filament\Forms\Components\Select::make('role')
            //             ->label('Doctor Role')
            //             ->options(
            //                 function () {
            //                     return Role::query()
            //                         ->pluck('name', 'name')
            //                         ->toArray();
            //                 }
            //             )
            //             ->searchable()
            //             ->required(),
            //     ])
            //     ->action(function (array $data) {
            //         $this->selectedRole = $data['role'];
            //         if ($this->record->user && $this->selectedRole) {
            //             $this->record->user->syncRoles([$this->selectedRole]);
            //             Notification::make()
            //                 ->success()
            //                 ->title('Role Assigned')
            //                 ->body("Role '{$this->selectedRole}' has been assigned to {$this->record->user->email}")
            //                 ->send();
            //         } else {
            //             Notification::make()
            //                 ->danger()
            //                 ->title('Error')
            //                 ->body('No user or role selected.')
            //                 ->send();
            //         }
            //     })
            //     ->visible(fn() => $hasUser),

            ActionGroup::make([
                ViewAction::make()->icon('heroicon-o-eye'),
                DeleteAction::make()->icon('heroicon-o-trash')->requiresConfirmation(),
            ])
                ->label('Actions')
                ->color('gray')
                ->button(),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    public function getFooter(): ?View
    {
        return view('filament.doctors.credential-modal', [
            'show' => $this->showCredentialModal,
            'type' => $this->modalType,
            'email' => $this->record->user?->email ?? '',
        ]);
    }
}
