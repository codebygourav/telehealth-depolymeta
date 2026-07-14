<?php

namespace App\Filament\Resources\Medicines;

use App\Filament\Resources\Medicines\Pages\{CreateMedicine, EditMedicine, ListMedicines};
use App\Filament\Resources\Medicines\Schemas\MedicineForm;
use App\Filament\Resources\Medicines\Tables\MedicinesTable;
use App\Models\Medicine;
use App\Traits\HasResourcePermissions;
use App\Traits\HasCustomSidebar;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MedicineResource extends Resource
{
    use HasResourcePermissions;
    use HasCustomSidebar;
    protected static ?string $model = Medicine::class;

    public static function getSidebarOptions(): array
    {
        return [
            'label' => 'Medicines',
            'icon'  => 'heroicon-o-beaker',
            'sort'  => 999,
            'group' => 'Medicine',
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return check_permission('medicines.view_any');
    }

    // Permission methods are now provided by HasResourcePermissions trait

    public static function form(Schema $schema): Schema
    {
        return MedicineForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MedicinesTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMedicines::route('/'),
            'create' => CreateMedicine::route('/create'),
            'edit' => EditMedicine::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'creator:id,name',
                'updater:id,name',
                'deleter:id,name',
            ]);

        // Apply permission-based filtering
        return static::filterQueryByOwnership($query);
    }
}
