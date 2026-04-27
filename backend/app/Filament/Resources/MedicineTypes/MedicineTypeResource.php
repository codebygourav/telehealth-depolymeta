<?php

namespace App\Filament\Resources\MedicineTypes;

use App\Filament\Resources\MedicineTypes\Pages\ListMedicineTypes;
use App\Filament\Resources\MedicineTypes\Schemas\MedicineTypeForm;
use App\Filament\Resources\MedicineTypes\Tables\MedicineTypesTable;
use App\Models\MedicineType;
use App\Traits\HasResourcePermissions;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\Alignment;

class MedicineTypeResource extends Resource
{
    use HasResourcePermissions;
    protected static ?string $model = MedicineType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'MedicineType';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
    public function getFormActionsAlignment(): Alignment|string
    {
        // Make header actions (the button) float to the right
        return Alignment::End;
    }

    // Permission methods are now provided by HasResourcePermissions trait
    // Override only if you need custom behavior

    public static function form(Schema $schema): Schema
    {
        return MedicineTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MedicineTypesTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        // If there is an infolist like in MedicineResource, add here
        return $schema;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMedicineTypes::route('/'),
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

        if (check_permission('medicine-types.view_any')) {
            return $query;
        }

        return $query->whereRaw('0=1');
    }
}
