<?php

namespace App\Filament\Resources\Symptoms;

use App\Filament\Resources\Symptoms\Pages\ListSymptoms;
use App\Filament\Resources\Symptoms\Schemas\SymptomForm;
use App\Filament\Resources\Symptoms\Schemas\SymptomInfolist;
use App\Filament\Resources\Symptoms\Tables\SymptomsTable;
use App\Models\Symptom;
use App\Traits\HasResourcePermissions;
use Illuminate\Support\Facades\Auth;
use App\Traits\HasCustomSidebar;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class SymptomResource extends Resource
{
    use HasResourcePermissions;
    use HasCustomSidebar;
    protected static ?string $model = Symptom::class;

    protected static ?string $navigationLabel = 'Symptoms';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-heart';
    protected static string|\UnitEnum|null $navigationGroup = 'Doctor Management';
    protected static ?int $navigationSort = 14;
    protected static ?string $recordTitleAttribute = 'Symtom';

    public static function shouldRegisterNavigation(): bool
    {
        return check_permission('symptoms.view_any');
    }

    // Permission methods are now provided by HasResourcePermissions trait

    /** ------------------------------
     *  SCOPED QUERY USING MODEL SCOPE
     * ------------------------------*/
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $user = Auth::user();
        $query = parent::getEloquentQuery()
            ->with([
                'creator:id,name',
                'updater:id,name',
                'deleter:id,name',
            ]);

        if (!$user) {
            return $query->whereRaw('1=0');
        }
        if (check_permission('symptoms.view_any')) {
            return $query;
        }
        return $query->visibleTo($user);
    }

    /** ------------------------------
     *  FILAMENT CONFIGURATION
     * ------------------------------*/
    public static function form(Schema $schema): Schema
    {
        return SymptomForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SymptomInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SymptomsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSymptoms::route('/'),
        ];
    }
}
