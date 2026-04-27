<?php

namespace App\Filament\Resources\MedicineCategories;

use App\Filament\Resources\MedicineCategories\Pages\ListMedicineCategories;
use App\Filament\Resources\MedicineCategories\Schemas\MedicineCategoryForm;
use App\Filament\Resources\MedicineCategories\Tables\MedicineCategoriesTable;
use App\Models\MedicineCategory;
use App\Traits\HasResourcePermissions;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MedicineCategoryResource extends Resource
{
    use HasResourcePermissions;
    protected static ?string $model = MedicineCategory::class;
    protected static ?string $navigationLabel = 'Categories';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    // Permission methods are now provided by HasResourcePermissions trait

    /** QUERY RESTRICTIONS */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'creator:id,name',
                'updater:id,name',
                'deleter:id,name',
            ]);

        if (check_permission('medicine-categories.view_any')) {
            return $query;
        }

        return $query->whereRaw('1=0');
    }

    public static function form(Schema $schema): Schema
    {
        return MedicineCategoryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return MedicineCategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMedicineCategories::route('/'),
        ];
    }
}
