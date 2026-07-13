<?php

namespace App\Filament\Resources\Patients\Pages;

use App\Filament\Resources\Patients\PatientResource;
use App\Models\Patient;
use App\Services\PatientAuthAccountService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Alignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreatePatient extends CreateRecord
{
    protected ?string $createdPassword = null;

    public function getFormActionsAlignment(): Alignment|string
    {
        // Return just the enum or a string class name as required by the typehint
        // Here, default to Alignment::End which matches "right alignment"
        return Alignment::End;
    }

    protected static string $resource = PatientResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['create_user_account'] = true;

        return $data;
    }

    public function persistAccountStep(Get $get, Set $set): void
    {
        $draftPatientId = $get('draft_patient_id');
        $draftPatient = filled($draftPatientId) ? Patient::query()->find($draftPatientId) : null;

        $provisioned = DB::transaction(function () use ($get, $draftPatient) {
            return app(PatientAuthAccountService::class)->provision(
                patientData: $this->getAccountStepPayload($get),
                patient: $draftPatient,
                plainPassword: $get('user_password') ?: null,
            );
        });

        $set('draft_patient_id', $provisioned['patient']->getKey());
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $draftPatient = filled($data['draft_patient_id'] ?? null)
                ? Patient::query()->find($data['draft_patient_id'])
                : null;

            $provisioned = app(PatientAuthAccountService::class)->provision(
                patientData: $data,
                patient: $draftPatient,
                plainPassword: $data['user_password'] ?? null,
            );

            $this->createdPassword = $data['user_password'] ?? $provisioned['generated_password'];

            return $provisioned['patient'];
        });
    }

    protected function afterCreate(): void
    {
        if (! $this->createdPassword || ! $this->record->user?->email) {
            return;
        }

        session([
            'edit_patient_page_credentials_password' => $this->createdPassword,
            'edit_patient_page_patient_id' => $this->record->getKey(),
            'edit_patient_page_credentials_context' => 'create',
        ]);
    }

    protected function getAccountStepPayload(Get $get): array
    {
        return [
            'source' => $get('source') ?: 'internal',
            'first_name' => $get('first_name'),
            'last_name' => $get('last_name'),
            'email' => $get('email'),
            'mobile_no' => $get('mobile_no'),
            'create_user_account' => true,
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
