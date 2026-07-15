<?php

namespace App\Filament\Resources\Doctors;

use App\Filament\Resources\Doctors\Pages\CreateDoctor;
use App\Filament\Resources\Doctors\Pages\EditDoctor;
use App\Filament\Resources\Doctors\Pages\ListDoctors;
use App\Filament\Resources\Doctors\Pages\ManageDoctorAvailability;
use App\Filament\Resources\Doctors\Pages\ManageDoctorAiTraining;
use App\Filament\Resources\Doctors\Pages\ViewDoctor;
use App\Filament\Resources\Doctors\Schemas\DoctorForm;
use App\Filament\Resources\Doctors\Tables\DoctorsTable;
use App\Models\Doctor;
use App\Traits\HasCustomSidebar;
use App\Traits\HasResourcePermissions;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use BackedEnum;
use App\Filament\Resources\Doctors\Schemas\DoctorInfolist;

class DoctorResource extends Resource
{
    use HasResourcePermissions;
    use HasCustomSidebar;

    protected static ?string $model = Doctor::class;

    public static function getSidebarOptions(): array
    {
        return [
            'icon'  => 'heroicon-o-user-group',
            'group' => 'Doctor Management',
            'sort'  => 11,
        ];
    }

    protected static ?string $recordTitleAttribute = 'user.name';

    public static function shouldRegisterNavigation(): bool
    {
        return check_permission('doctors.view_any');
    }

    public static function requiredPermission(): string
    {
        return 'doctor_manager';
    }

    public static function table(Table $table): Table
    {
        return DoctorsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'creator:id,name',
                'updater:id,name',
                'deleter:id,name',
                'user:id,name,email,phone', // Eager load user for table columns (avatar is accessed via InteractsWithModuleDocuments trait)
            ]);

        // Apply permission-based filtering
        return static::filterQueryByOwnership($query);
    }

    public static function form(Schema $schema): Schema
    {
        return DoctorForm::configure($schema);
    }



    public static function infolist(Schema $schema): Schema
    {
        return DoctorInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDoctors::route('/'),
            'create' => CreateDoctor::route('/create'),
            'edit' => EditDoctor::route('/{record}/edit'),
            'ai-training' => ManageDoctorAiTraining::route('/{record}/ai-training'),
            'availability' => ManageDoctorAvailability::route('/{record}/availability'),
            'view' => ViewDoctor::route('/{record:slug}/view'),
        ];
    }
    // Permission methods are now provided by HasResourcePermissions trait


    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
