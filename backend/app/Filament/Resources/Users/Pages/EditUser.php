<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\Patient;
use App\Services\PatientAuthAccountService;
use App\Services\PatientCredentialsService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Alignment;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected ?string $updatedPassword = null;

    protected ?Patient $credentialPatient = null;

    public bool $showCredentialPrompt = false;

    public string $credentialPromptContext = 'update';

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->restoreCredentialPrompt();
    }

    public function getFormActionsAlignment(): Alignment|string
    {
        // Return just the enum or a string class name as required by the typehint
        // Here, default to Alignment::End which matches "right alignment"
        return Alignment::End;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (!empty($data['roles'])) {
            $data['role'] = is_array($data['roles']) ? $data['roles'][0] : $data['roles'];
        }

        $this->updatedPassword = filled($data['password'] ?? null)
            ? $data['password']
            : null;

        if ($this->updatedPassword) {
            $data['password'] = Hash::make($this->updatedPassword);
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            $record->update($data);

            if (! empty($record->role)) {
                $record->syncRoles([$record->role]);
            }

            if ($record->hasRole('patient')) {
                $this->credentialPatient = app(PatientAuthAccountService::class)->ensurePatientProfileForUser($record);
            }

            return $record->refresh();
        });
    }

    protected function afterSave(): void
    {
        if (! $this->updatedPassword || ! $this->getCredentialPatient()?->user?->email) {
            return;
        }

        $this->openCredentialPrompt($this->getCredentialPatient(), $this->updatedPassword, 'update');
    }

    protected function getCredentialPatient(): ?Patient
    {
        return $this->credentialPatient ?? $this->record->patient;
    }

    public function sendPendingCredentials(): void
    {
        $patientId = session('edit_patient_credentials_patient_id');
        $password = session('edit_patient_credentials_password');
        $patient = $patientId ? Patient::query()->with('user')->find($patientId) : $this->getCredentialPatient();

        if (! $patient || ! $password) {
            Notification::make()
                ->danger()
                ->title('Unable to send credentials')
                ->body('A patient profile or updated password was not found.')
                ->send();

            $this->closeCredentialPrompt();

            return;
        }

        $this->notifyCredentialDelivery(
            sent: app(PatientCredentialsService::class)->sendCredentials($patient, $password),
            email: $patient->user?->email ?? $patient->email,
        );

        $this->closeCredentialPrompt();
        $this->updatedPassword = null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendCredentials')
                ->label('Send Credentials')
                ->icon('heroicon-o-envelope')
                ->modalHeading('Send Login Credentials')
                ->modalDescription(fn () => 'Choose a password option and send patient login credentials to ' . ($this->getCredentialPatient()?->user?->email ?? $this->record->email ?? 'this patient') . '.')
                ->modalSubmitActionLabel('Send Email')
                ->form([
                    ToggleButtons::make('password_mode')
                        ->label('Password Option')
                        ->options([
                            'custom' => 'Use a custom password',
                            'generated' => 'Generate a new password automatically',
                        ])
                        ->colors([
                            'custom' => 'success',
                            'generated' => 'success',
                        ])
                        ->default('generated')
                        ->extraAttributes(['class' => 'credential-password-mode'])
                        ->grouped()
                        ->live()
                        ->required(),
                    TextInput::make('custom_password')
                        ->label('Custom Password')
                        ->password()
                        ->revealable()
                        ->minLength(6)
                        ->required(fn (callable $get) => $get('password_mode') === 'custom')
                        ->visible(fn (callable $get) => $get('password_mode') === 'custom'),
                ])
                ->action(fn (array $data) => $this->sendCredentialsWithPassword($data))
                ->visible(fn () => ($this->record->hasRole('patient') || $this->record->patient) && filled($this->record->email)),
            ViewAction::make(),
        ];
    }

    public function getFooter(): ?View
    {
        return view('filament.patients.credential-prompt-modal', [
            'show' => $this->showCredentialPrompt,
            'context' => $this->credentialPromptContext,
            'email' => $this->getCredentialPatient()?->user?->email ?? $this->record->email ?? '',
        ]);
    }

    public function closeCredentialPrompt(): void
    {
        $this->showCredentialPrompt = false;
        $this->credentialPromptContext = 'update';

        session()->forget([
            'edit_patient_credentials_password',
            'edit_patient_credentials_patient_id',
            'edit_patient_credentials_context',
        ]);
    }

    protected function restoreCredentialPrompt(): void
    {
        $patient = $this->getCredentialPatient();

        if (! $patient || session('edit_patient_credentials_patient_id') != $patient->getKey() || ! session('edit_patient_credentials_password')) {
            return;
        }

        $this->showCredentialPrompt = true;
        $this->credentialPromptContext = session('edit_patient_credentials_context', 'update');
    }

    protected function openCredentialPrompt(?Patient $patient, string $password, string $context): void
    {
        if (! $patient) {
            return;
        }

        session([
            'edit_patient_credentials_password' => $password,
            'edit_patient_credentials_patient_id' => $patient->getKey(),
            'edit_patient_credentials_context' => $context,
        ]);

        $this->showCredentialPrompt = true;
        $this->credentialPromptContext = $context;
    }

    protected function sendCredentialsWithPassword(array $data): void
    {
        $password = ($data['password_mode'] ?? 'generated') === 'custom'
            ? (string) $data['custom_password']
            : Str::random(12);

        $patient = $this->syncPatientCredentials($password);

        if (! $patient?->user?->email) {
            Notification::make()
                ->danger()
                ->title('Unable to send credentials')
                ->body('A patient profile or email address was not found.')
                ->send();

            return;
        }

        $this->notifyCredentialDelivery(
            sent: app(PatientCredentialsService::class)->sendCredentials($patient, $password),
            email: $patient->user->email,
        );
    }

    protected function syncPatientCredentials(string $password): ?Patient
    {
        $patient = DB::transaction(function () use ($password) {
            $patient = app(PatientAuthAccountService::class)->ensurePatientProfileForUser($this->record);

            $this->record->forceFill([
                'password' => Hash::make($password),
            ])->save();

            return $patient->fresh('user');
        });

        $this->credentialPatient = $patient;

        return $patient;
    }

    protected function notifyCredentialDelivery(bool $sent, string $email): void
    {
        $notification = Notification::make()
            ->title($sent ? 'Credentials sent' : 'Failed to send credentials')
            ->body($sent
                ? "Login credentials were emailed to {$email}."
                : 'The credentials email could not be sent.');

        if ($sent) {
            $notification->success();
        } else {
            $notification->danger();
        }

        $notification->send();
    }
}
