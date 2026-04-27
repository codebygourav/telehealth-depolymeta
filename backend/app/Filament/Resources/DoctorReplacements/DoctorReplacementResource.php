<?php

namespace App\Filament\Resources\DoctorReplacements;

use App\Filament\Resources\DoctorReplacements\Pages\CreateDoctorReplacement;
use App\Filament\Resources\DoctorReplacements\Pages\EditDoctorReplacement;
use App\Filament\Resources\DoctorReplacements\Pages\ListDoctorReplacements;
use App\Filament\Resources\DoctorReplacements\Pages\ViewDoctorReplacement;
use App\Filament\Resources\DoctorReplacements\Schemas\DoctorReplacementForm;
use App\Filament\Resources\DoctorReplacements\Tables\DoctorReplacementTable;
use App\Models\DoctorReplacement;
use App\Traits\HasResourcePermissions;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Traits\HasCustomSidebar;

class DoctorReplacementResource extends Resource
{
    use HasResourcePermissions;
    use HasCustomSidebar;


    protected static ?string $model = DoctorReplacement::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationLabel = 'Doctor Replacements';

    protected static ?string $modelLabel = 'Doctor Replacement';

    protected static ?string $pluralModelLabel = 'Doctor Replacements';

    protected static string|\UnitEnum|null $navigationGroup = 'Doctor Management';

    protected static ?int $navigationSort = 15;

    public static function table(Table $table): Table
    {
        return DoctorReplacementTable::configure($table);
    }

    public static function form(Schema $schema): Schema
    {
        return DoctorReplacementForm::configure($schema);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDoctorReplacements::route('/'),
            'create' => CreateDoctorReplacement::route('/create'),
            'edit' => EditDoctorReplacement::route('/{record}/edit'),
            'view' => ViewDoctorReplacement::route('/{record}/view'),
        ];
    }
}