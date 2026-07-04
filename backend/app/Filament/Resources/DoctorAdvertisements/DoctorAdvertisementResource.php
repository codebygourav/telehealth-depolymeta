<?php

namespace App\Filament\Resources\DoctorAdvertisements;

use App\Filament\Resources\DoctorAdvertisements\Pages\CreateDoctorAdvertisement;
use App\Filament\Resources\DoctorAdvertisements\Pages\EditDoctorAdvertisement;
use App\Filament\Resources\DoctorAdvertisements\Pages\ListDoctorAdvertisements;
use App\Filament\Resources\DoctorAdvertisements\Pages\ViewDoctorAdvertisement;
use App\Filament\Resources\DoctorAdvertisements\Schemas\DoctorAdvertisementForm;
use App\Filament\Resources\DoctorAdvertisements\Schemas\DoctorAdvertisementInfolist;
use App\Filament\Resources\DoctorAdvertisements\Tables\DoctorAdvertisementsTable;
use App\Models\DisplayEvent;
use App\Traits\HasCustomSidebar;
use App\Traits\HasResourcePermissions;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DoctorAdvertisementResource extends Resource
{
    use HasCustomSidebar;
    use HasResourcePermissions;

    protected static ?string $model = DisplayEvent::class;

    protected static ?string $slug = 'display-content-manager';

    protected static ?string $navigationLabel = 'Display Content Manager';

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|\UnitEnum|null $navigationGroup = 'Token Queue Display';

    protected static ?int $navigationSort = 96;

    public static function getSidebarOptions(): array
    {
        return [
            'label' => 'Display Content Manager',
            'icon' => 'heroicon-o-photo',
            'sort' => 95,
            'group' => 'Token Queue Display',
            'visible' => true,
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return DoctorAdvertisementForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DoctorAdvertisementInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DoctorAdvertisementsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with([
                'doctors:id,first_name,last_name,doctor_code',
                'creator:id,name',
                'updater:id,name',
                'deleter:id,name',
            ]);
    }


    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDoctorAdvertisements::route('/'),
            'create' => CreateDoctorAdvertisement::route('/create'),
            'view' => ViewDoctorAdvertisement::route('/{record}'),
            'edit' => EditDoctorAdvertisement::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
