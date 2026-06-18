<?php

namespace App\Filament\Resources\VaccinationDocuments;

use App\Enums\VaccinationDocumentType;
use App\Filament\Concerns\ConfiguresSlideOverSections;
use App\Filament\Resources\VaccinationDocuments\Pages\ListVaccinationDocuments;
use App\Filament\Resources\VaccinationDocuments\Pages\ViewVaccinationDocument;
use App\Filament\Resources\VaccinationDocuments\Tables\VaccinationDocumentsTable;
use App\Models\PatientVaccination;
use App\Models\VaccinationDocument;
use App\Traits\HasCustomSidebar;
use App\Traits\HasResourcePermissions;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class VaccinationDocumentResource extends Resource
{
    use ConfiguresSlideOverSections;
    use HasCustomSidebar;
    use HasResourcePermissions;

    protected static ?string $model = VaccinationDocument::class;

    protected static ?string $navigationLabel = 'Vaccination Documents';

    protected static ?string $slug = 'vaccination-documents';

    public static function getSidebarOptions(): array
    {
        return [
            'label' => 'Vaccination Documents',
            'icon' => 'heroicon-o-document-text',
            'sort' => 9,
            'group' => 'Vaccination',
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        return check_permission(['vaccination-documents.view_any', 'vaccination-documents.view', 'vaccination-documents.manage_own'])
            || (is_object($user) && method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['super_admin', 'admin', 'doctor_manager', 'receptionist', 'doctor']));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            ViewEntry::make('document_details')
                ->view('filament.vaccination-documents.vaccination-document-view')
                ->state(fn($record) => $record)
                ->columnSpanFull(),
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components(static::wrapSlideOverForm([
            static::slideOverSection('Vaccination Document', [
                Select::make('patient_vaccination_id')
                    ->label('Patient Vaccine Dose')
                    ->helperText('Choose the dose this document belongs to.')
                    ->options(fn() => PatientVaccination::query()
                        ->with(['patient', 'vaccination'])
                        ->latest()
                        ->limit(200)
                        ->get()
                        ->mapWithKeys(fn(PatientVaccination $dose) => [
                            $dose->id => trim(($dose->patient?->first_name ?? '') . ' ' . ($dose->patient?->last_name ?? '')) . ' — ' . ($dose->vaccination?->name ?? 'Vaccine'),
                        ]))
                    ->searchable()
                    ->required(),
                Select::make('document_type')
                    ->label('Document Type')
                    ->options(VaccinationDocumentType::options())
                    ->default(VaccinationDocumentType::CERTIFICATE->value)
                    ->required(),
                FileUpload::make('document')
                    ->label('Document File')
                    ->disk('public')
                    ->directory('vaccinations/documents')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(5120)
                    ->required(),
                TextInput::make('certificate_number')
                    ->maxLength(255),
            ], 'Attach a certificate, prescription, scan, or consent form to a patient vaccine dose.', icon: 'heroicon-o-document-text'),
        ]));
    }

    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return VaccinationDocumentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVaccinationDocuments::route('/'),
            'view' => ViewVaccinationDocument::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['patientVaccination.patient', 'patientVaccination.vaccination', 'patientVaccination.doctor'])
            ->withoutGlobalScopes();
    }
}
