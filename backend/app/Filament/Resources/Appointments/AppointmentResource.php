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
    protected static ?int $navigationSort = 10;
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
        if (Auth::user()?->hasRole('patient')) {
            return false;
        }

        // Use the standard permission check from HasResourcePermissions trait
        // Menu will only show if user has view, view_any, or manage_own permission
        $slug = static::getPermissionSlug();
        return check_permission(["{$slug}.view", "{$slug}.view_any", "{$slug}.manage_own"]);
    }

    public static function canView($record): bool
    {
        if (Auth::user()?->hasRole('patient')) {
            return false;
        }

        return parent::canView($record);
    }

    public static function getEloquentQuery(): Builder
    {
        $patientColumns = [
            'id',
            'user_id',
            'first_name',
            'last_name',
            'email',
            'mobile_no',
            'alternate_no',
            'date_of_birth',
            'existing_patient_id',
            'address',
            'pincode',
            'area',
            'city',
            'landmark',
            'state',
            'nationality',
            'marital_status',
            'father_name',
            'husband_name',
            'wife_name',
            'gender',
            'age',
            'blood_group',
        ];

        $query = parent::getEloquentQuery()
            ->with([
                'creator:id,name',
                'updater:id,name',
                'deleter:id,name',
                'patient' => fn($query) => $query->select($patientColumns),
                'doctor:id,user_id,first_name,last_name,years_experience',
                'doctor.user:id,name,email,phone',
                'availability:id,opd_type',
                'payment:id,appointment_id,amount,payment_method,status,transaction_id,razorpay_payment_id,razorpay_order_id,invoice_id,contact,email,captured,created_at',
                'paymentWaiver:id,name',
                'videoConsultation:id,appointment_id,room_url,host_url,participate_url,room_id,status,started_at,ended_at',
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
