<?php

namespace App\Filament\Resources\Vendors;

use App\Filament\Resources\Vendors\Pages\CreateVendor;
use App\Filament\Resources\Vendors\Pages\EditVendor;
use App\Filament\Resources\Vendors\Pages\ListVendors;
use App\Filament\Resources\Vendors\Schemas\VendorForm;
use App\Filament\Resources\Vendors\Tables\VendorsTable;
use App\Models\Vendor;
use App\Traits\HasResourcePermissions;
use BackedEnum;
use App\Traits\HasCustomSidebar;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VendorResource extends Resource
{
    use HasResourcePermissions;
    use HasCustomSidebar;

    protected static ?string $model = Vendor::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?string $recordTitleAttribute = 'Vendor';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
    public static function getSidebarOptions(): array
    {
        return [
            'icon'  => 'heroicon-o-building-office',
            'group' => '',
            'sort'  => 11,
        ];
    }


    public static function form(Schema $schema): Schema
    {
        return VendorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VendorsTable::configure($table);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'creator:id,name',
                'updater:id,name',
                'deleter:id,name',
                'user:id,name,email',
            ]);

        // Filter by permissions - if user doesn't have view_any, show only own records
        return static::filterQueryByOwnership($query);
    }

    // Permission methods are now provided by HasResourcePermissions trait

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVendors::route('/'),
            'create' => CreateVendor::route('/create'),
            'edit' => EditVendor::route('/{record}/edit'),
        ];
    }
    public static function getModel(): string
    {
        return \App\Models\Vendor::class; // This must match
    }
}
