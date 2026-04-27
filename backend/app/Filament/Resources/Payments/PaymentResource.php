<?php

namespace App\Filament\Resources\Payments;

use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\Pages\ViewPayment;
use App\Filament\Resources\Payments\Tables\PaymentsTable;
use App\Filament\Resources\Payments\Schemas\PaymentInfolist;
use App\Models\Payment;
use App\Traits\HasResourcePermissions;
use App\Traits\HasCustomSidebar;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use BackedEnum;
use UnitEnum;

class PaymentResource extends Resource
{
    use HasResourcePermissions;
    use HasCustomSidebar;

    protected static ?string $slug = 'payments';

    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|BackedEnum|null $activeNavigationIcon = 'heroicon-s-credit-card';

    protected static string|\UnitEnum|null $navigationGroup = 'Appointments & Finance';
    protected static ?int $navigationSort = 41;

    protected static ?string $recordTitleAttribute = 'transaction_id';

    public static function infolist(Schema $schema): Schema
    {
        return PaymentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentsTable::configure($table);
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
            'index' => ListPayments::route('/'),
            'view' => ViewPayment::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['appointment.patient.user', 'appointment.doctor.user'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
