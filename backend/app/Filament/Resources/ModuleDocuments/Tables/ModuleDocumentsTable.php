<?php

namespace App\Filament\Resources\ModuleDocuments\Tables;

use Filament\Tables\Table;
use Filament\Actions\{
    BulkActionGroup,
    DeleteBulkAction,
    ForceDeleteBulkAction,
    RestoreBulkAction,
    ActionGroup,
    ViewAction,
    DeleteAction
};
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\Layout\{Stack, Split, Panel};
use Filament\Tables\Columns\ViewColumn;
use App\Models\ModuleDocument;
use App\Filament\Resources\ModuleDocuments\ModuleDocumentResource;

class ModuleDocumentsTable
{
    public static function configure(Table $table): Table
    {
        // Group module documents by name (e.g., prescription_pdf, signature, etc.)
        return $table
            ->extraAttributes([
                'class' => 'module-documents-grid-table',
            ])
            ->query(function () {
                // Get one representative document per name and linked model (e.g. Appointment)
                return ModuleDocument::query()
                    ->where('name', 'prescription_pdf') // Filter: Show only prescription pdf
                    ->whereIn('id', function ($q) {
                        $q->selectRaw('MAX(id)')
                            ->from('module_documents')
                            ->whereNull('deleted_at')
                            ->groupBy('name', 'model_type', 'model_id');
                    });
            })
            // Use grid layout for responsive cards
            ->columns([
                Stack::make([
                    ViewColumn::make('module_document_card')
                        ->view('filament.module-documents.table.card-view')
                        ->label(''),
                ])->extraAttributes(['class' => 'items-center!']),
            ])
            ->contentGrid([
                'sm' => 1,
                'md' => 2,
                'lg' => 3,
                'xl' => 4,
                '2xl' => 5,
            ])
            ->filters([
                SelectFilter::make('model_type')
                    ->label('Linked To (Model Type)')
                    ->options(function () {
                        return ModuleDocument::query()
                            ->select('model_type')
                            ->distinct()
                            ->pluck('model_type', 'model_type')
                            ->mapWithKeys(fn($type) => [$type => class_basename($type)])
                            ->toArray();
                    }),
                SelectFilter::make('name')
                    ->label('Document Type (Name)')
                    ->options(function () {
                        return ModuleDocument::query()
                            ->select('name')
                            ->distinct()
                            ->pluck('name', 'name')
                            ->mapWithKeys(fn($name) => [
                                $name => ucwords(str_replace(['_', '-'], ' ', $name))
                            ])
                            ->toArray();
                    }),
            ])
            ->recordUrl(fn(ModuleDocument $record): string => ModuleDocumentResource::getUrl('view', ['record' => $record]));
    }
}