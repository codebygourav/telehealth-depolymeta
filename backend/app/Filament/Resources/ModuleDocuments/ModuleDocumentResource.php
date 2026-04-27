<?php

namespace App\Filament\Resources\ModuleDocuments;

use App\Filament\Resources\ModuleDocuments\Pages\ListModuleDocuments;
use App\Filament\Resources\ModuleDocuments\Pages\ViewModuleDocument;
use App\Filament\Resources\ModuleDocuments\Tables\ModuleDocumentsTable;
use App\Models\ModuleDocument;
use App\Traits\HasResourcePermissions;
use App\Traits\HasCustomSidebar;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Infolists\Components\ViewEntry;
use Illuminate\Database\Eloquent\Builder;
use BackedEnum;
use UnitEnum;

class ModuleDocumentResource extends Resource
{
    use HasResourcePermissions;
    use HasCustomSidebar;

    protected static ?string $model = ModuleDocument::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-paper-clip';

    protected static string|\UnitEnum|null $navigationGroup = 'Media';
    protected static ?int $navigationSort = 51;

    protected static ?string $recordTitleAttribute = 'name';

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                ViewEntry::make('document_details')
                    ->view('filament.module-documents.module-document-view')
                    ->state(fn($record) => $record)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return ModuleDocumentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListModuleDocuments::route('/'),
            'view' => ViewModuleDocument::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['model', 'creator']);
    }
}
