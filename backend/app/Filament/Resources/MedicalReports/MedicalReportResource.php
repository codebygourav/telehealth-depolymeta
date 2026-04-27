<?php

namespace App\Filament\Resources\MedicalReports;

use App\Filament\Resources\MedicalReports\Pages\ListMedicalReports;
use App\Filament\Resources\MedicalReports\Pages\ViewMedicalReport;
use App\Filament\Resources\MedicalReports\Tables\MedicalReportsTable;
use App\Filament\Resources\MedicalReports\Schemas\MedicalReportInfolist;
use App\Models\MedicalReport;
use App\Traits\HasResourcePermissions;
use App\Traits\HasCustomSidebar;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use BackedEnum;
use UnitEnum;

class MedicalReportResource extends Resource
{
    use HasResourcePermissions;
    use HasCustomSidebar;

    protected static ?string $slug = 'medical-reports';
    protected static string|\UnitEnum|null $navigationGroup = 'Media';
    protected static ?int $navigationSort = 50;
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-s-document-chart-bar';

    protected static ?string $model = MedicalReport::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function infolist(Schema $schema): Schema
    {
        return MedicalReportInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MedicalReportsTable::configure($table);
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
            'index' => ListMedicalReports::route('/'),
            'view' => ViewMedicalReport::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['patient', 'doctor.user', 'appointment'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        return static::filterQueryByOwnership($query);
    }
}
