<?php

namespace App\Filament\Resources\Patients\Pages;

use App\Filament\Resources\Patients\PatientResource;
use App\Models\Patient;
use App\Services\PatientAuthAccountService;
use App\Services\PatientCredentialsService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EditPatient extends EditRecord
{
    protected static string $resource = PatientResource::class;

    protected ?string $userPassword = null;

    public bool $showCredentialPrompt = false;

    public string $credentialPromptContext = 'update';

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->restoreCredentialPrompt();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendCredentials')
                ->label('Send Credentials')
                ->icon('heroicon-o-envelope')
                ->modalHeading('Send Login Credentials')
                ->modalDescription(fn () => 'Choose a password option and send patient login credentials to ' . ($this->record->user?->email ?? $this->record->email ?? 'this patient') . '.')
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
                ->visible(fn () => filled($this->record->user?->email ?: $this->record->email)),
            ActionGroup::make([
                ViewAction::make()
                    ->icon('heroicon-o-eye'),
                DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->visible(fn () => PatientResource::canDelete($this->record))
                    ->requiresConfirmation(),
            ])
                ->icon('heroicon-o-ellipsis-vertical')
                ->label('Actions')
                ->color('gray')
                ->button(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['create_user_account'] = true;
        $data['draft_patient_id'] = $this->record->getKey();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['create_user_account'] = true;

        if (! empty($data['user_password'])) {
            $this->userPassword = $data['user_password'];
        }

        return $data;
    }

    public function persistAccountStep(Get $get, Set $set): void
    {
        $patient = DB::transaction(function () use ($get) {
            return app(PatientAuthAccountService::class)->provision(
                patientData: $this->getAccountStepPayload($get),
                patient: $this->record,
                plainPassword: $get('user_password') ?: null,
            )['patient'];
        });

        $this->record = $patient;
        $set('draft_patient_id', $patient->getKey());
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            return app(PatientAuthAccountService::class)->provision(
                patientData: $data,
                patient: $record,
                plainPassword: $this->userPassword,
            )['patient'];
        });
    }

    protected function afterSave(): void
    {
        if (! $this->userPassword || ! $this->record->user?->email) {
            return;
        }

        $this->openCredentialPrompt($this->record, $this->userPassword, 'update');
    }

    public function sendPendingCredentials(): void
    {
        $patientId = session('edit_patient_page_patient_id');
        $password = session('edit_patient_page_credentials_password');
        $patient = $patientId ? Patient::query()->with('user')->find($patientId) : $this->record->fresh('user');

        if (! $patient?->user?->email || ! $password) {
            Notification::make()
                ->danger()
                ->title('Unable to send credentials')
                ->body('A patient email or updated password was not found.')
                ->send();

            $this->closeCredentialPrompt();

            return;
        }

        $this->notifyCredentialDelivery(
            sent: app(PatientCredentialsService::class)->sendCredentials($patient, $password),
            email: $patient->user->email,
        );

        $this->closeCredentialPrompt();
        $this->userPassword = null;
    }

    public function closeCredentialPrompt(): void
    {
        $this->showCredentialPrompt = false;
        $this->credentialPromptContext = 'update';

        session()->forget([
            'edit_patient_page_credentials_password',
            'edit_patient_page_patient_id',
            'edit_patient_page_credentials_context',
        ]);
    }

    protected function getAccountStepPayload(Get $get): array
    {
        return [
            'source' => $get('source') ?: ($this->record->source ?: 'internal'),
            'first_name' => $get('first_name'),
            'last_name' => $get('last_name'),
            'email' => $get('email'),
            'mobile_no' => $get('mobile_no'),
            'create_user_account' => true,
        ];
    }

    public function getFooter(): ?View
    {
        return view('filament.patients.credential-prompt-modal', [
            'show' => $this->showCredentialPrompt,
            'context' => $this->credentialPromptContext,
            'email' => $this->record->user?->email ?? $this->record->email ?? '',
        ]);
    }

    protected function restoreCredentialPrompt(): void
    {
        if (session('edit_patient_page_patient_id') != $this->record->getKey() || ! session('edit_patient_page_credentials_password')) {
            return;
        }

        $this->showCredentialPrompt = true;
        $this->credentialPromptContext = session('edit_patient_page_credentials_context', 'update');
    }

    protected function openCredentialPrompt(Patient $patient, string $password, string $context): void
    {
        session([
            'edit_patient_page_credentials_password' => $password,
            'edit_patient_page_patient_id' => $patient->getKey(),
            'edit_patient_page_credentials_context' => $context,
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
                ->body('A patient login account or email address was not found.')
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
        $patient = $this->record->fresh('user');

        if (! $patient) {
            return null;
        }

        if (! $patient->user) {
            $patient = DB::transaction(function () use ($patient, $password) {
                return app(PatientAuthAccountService::class)->provision(
                    patientData: [
                        'source' => $patient->source ?: 'internal',
                        'first_name' => $patient->first_name,
                        'last_name' => $patient->last_name,
                        'email' => $patient->email,
                        'mobile_no' => $patient->mobile_no,
                        'create_user_account' => true,
                    ],
                    patient: $patient,
                    plainPassword: $password,
                )['patient'];
            });

            $this->record = $patient->fresh('user');

            return $this->record;
        }

        $patient->user->forceFill([
            'password' => Hash::make($password),
        ])->save();

        $this->record = $patient->fresh('user');

        return $this->record;
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