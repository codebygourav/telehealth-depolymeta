<?php

namespace App\Filament\Resources\Appointments;

use App\Filament\Resources\Appointments\Pages\{ListAppointments, ViewAppointment};
use App\Filament\Resources\Appointments\Schemas\{AppointmentForm, AppointmentInfolist};
use App\Filament\Resources\Appointments\Tables\AppointmentsTable;
use App\Models\Appointment;
use App\Traits\{HasCustomSidebar, HasResourcePermissions};
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\{Builder, SoftDeletingScope};
use Illuminate\Support\Facades\Auth;

class AppointmentResource extends Resource
{
    use HasResourcePermissions;
    use HasCustomSidebar;

    protected static ?string $model = Appointment::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|\BackedEnum|null $activeNavigationIcon = 'heroicon-s-rectangle-stack';
    protected static string|\UnitEnum|null $navigationGroup = 'Appointments & Finance';
    protected static ?int $navigationSort = 40;
    protected static ?string $recordTitleAttribute = 'Appointment';

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function form(Schema $schema): Schema
    {
        return AppointmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AppointmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AppointmentsTable::configure($table);
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
            'index' => ListAppointments::route('/'),
            'view'   => ViewAppointment::route('/{record}'),
        ];
    }

    // Permission methods - custom logic for appointments
    // Note: canCreate and canEdit are intentionally disabled as appointments are managed via API
    public static function canViewAny(): bool
    {
        // Use the standard permission check from HasResourcePermissions trait
        // Menu will only show if user has view, view_any, or manage_own permission
        $slug = static::getPermissionSlug();
        return check_permission(["{$slug}.view", "{$slug}.view_any", "{$slug}.manage_own"]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'creator:id,name',
                'updater:id,name',
                'deleter:id,name',
                'patient:id,user_id,first_name,last_name',
                'doctor:id,user_id,first_name,last_name',
                'doctor.user:id,name,email',
            ]);

        // Apply permission-based filtering
        return static::filterQueryByOwnership($query);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
