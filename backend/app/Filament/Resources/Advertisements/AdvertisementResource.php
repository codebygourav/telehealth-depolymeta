<?php

namespace App\Filament\Resources\Advertisements;

use App\Filament\Resources\Advertisements\Pages\CreateAdvertisement;
use App\Filament\Resources\Advertisements\Pages\EditAdvertisement;
use App\Filament\Resources\Advertisements\Pages\ListAdvertisements;
use App\Filament\Resources\Advertisements\Pages\ViewAdvertisement;
use App\Filament\Resources\Advertisements\Schemas\AdvertisementForm;
use App\Filament\Resources\Advertisements\Schemas\AdvertisementInfolist;
use App\Filament\Resources\Advertisements\Tables\AdvertisementsTable;
use App\Models\Advertisement;
use App\Traits\HasResourcePermissions;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\HasCustomSidebar;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AdvertisementResource extends Resource
{
    use HasResourcePermissions;
    use HasCustomSidebar;
    protected static ?string $model = Advertisement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'title';
    protected static ?int $navigationSort = 4;
    public static function form(Schema $schema): Schema
    {
        return AdvertisementForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AdvertisementInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdvertisementsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'creator:id,name',
                'updater:id,name',
                'deleter:id,name',
            ]);
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
            'index' => ListAdvertisements::route('/'),
            // 'create' => CreateAdvertisement::route('/create'),
            // 'view' => ViewAdvertisement::route('/{record}'),
            // 'edit' => EditAdvertisement::route('/{record}/edit'),
        ];
    }

    // Permission methods are now provided by HasResourcePermissions trait
    // Override only if you need custom behavior

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
