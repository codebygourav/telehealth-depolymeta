<?php

namespace App\Filament\Resources\DoctorDepartments;

use App\Filament\Resources\DoctorDepartments\Pages\CreateDoctorDepartment;
use App\Filament\Resources\DoctorDepartments\Pages\EditDoctorDepartment;
use App\Filament\Resources\DoctorDepartments\Pages\ListDoctorDepartments;
use App\Filament\Resources\DoctorDepartments\Schemas\DoctorDepartmentForm;
use App\Filament\Resources\DoctorDepartments\Tables\DoctorDepartmentsTable;
use App\Filament\Resources\DoctorDepartments\Pages\ViewDoctorDepartment;
use App\Filament\Resources\DoctorDepartments\Schemas\DoctorDepartmentsInfolist;
use App\Models\Department;
use App\Traits\HasResourcePermissions;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;
use App\Traits\HasCustomSidebar;

class DoctorDepartmentResource extends Resource
{
    use HasResourcePermissions;
    use HasCustomSidebar;
    protected static ?string $model = Department::class;
    protected static ?string $slug = 'department';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office'; // building-office icon for navigation
    protected static string|\UnitEnum|null $navigationGroup = 'Doctor Management';

    public static function shouldRegisterNavigation(): bool
    {
        return check_permission('doctor_departments.view_any');
    }

    // Permission methods are now provided by HasResourcePermissions trait


    public static function form(Schema $schema): Schema
    {
        return DoctorDepartmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DoctorDepartmentsInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DoctorDepartmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDoctorDepartments::route('/'),
            'create' => CreateDoctorDepartment::route('/create'),
            'edit' => EditDoctorDepartment::route('/{record}/edit'),
            'view' => ViewDoctorDepartment::route('/{record:slug}'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->withTrashed(); // <-- REQUIRED FOR RESTORE/FORCE DELETE TO SHOW
    }
}
