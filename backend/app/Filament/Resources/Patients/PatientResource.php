<?php

namespace App\Filament\Resources\Patients;

use App\Filament\Resources\Patients\Pages\CreatePatient;
use App\Filament\Resources\Patients\Pages\{EditPatient, ViewPatient};
use App\Filament\Resources\Patients\Pages\ListPatients;
use App\Filament\Resources\Patients\Schemas\PatientForm;
use App\Filament\Resources\Patients\Schemas\PatientInfolist;
use App\Filament\Resources\Patients\Tables\PatientsTable;
use App\Models\Patient;
use App\Traits\HasCustomSidebar;
use App\Traits\HasResourcePermissions;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PatientResource extends Resource
{
    use HasResourcePermissions;
    use HasCustomSidebar;

    protected static ?string $model = Patient::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';
    protected static string|BackedEnum|null $activeNavigationIcon = 'heroicon-s-user-circle';
    protected static ?int $navigationSort = 3;
    protected static ?string $recordTitleAttribute = 'Patient';

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function requiredPermission(): string
    {
        return 'patient_manager';
    }

    public static function form(Schema $schema): Schema
    {
        return PatientForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PatientsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return check_permission('patients.create');
    }

    public static function canDelete($record): bool
    {
        if (check_permission('patients.delete_any')) {
            return true;
        }

        if (check_permission('patients.delete')) {
            return static::isOwnRecord($record);
        }

        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return PatientInfolist::configure($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'creator:id,name',
                'updater:id,name',
                'deleter:id,name',
                'user:id,name,email,phone,email_verified_at,status', // Eager load user for table columns (avatar is accessed via InteractsWithModuleDocuments trait)
            ]);

        // Apply permission-based filtering
        return static::filterQueryByOwnership($query);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPatients::route('/'),
            'create' => CreatePatient::route('/create'),
            'edit' => EditPatient::route('/{record}/edit'),
            'view' => ViewPatient::route('/{record:slug}/view'),
        ];
    }

    // Permission methods are now provided by HasResourcePermissions trait

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}