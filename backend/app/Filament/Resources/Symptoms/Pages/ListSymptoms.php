<?php

namespace App\Filament\Resources\Symptoms\Pages;

use App\Filament\Resources\Symptoms\SymptomResource;
use Filament\Actions\CreateAction;
use App\Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Actions\ActionGroup;
use App\Filament\Exports\SymptomExporter;
use App\Filament\Imports\SymptomImporter;

use Filament\Actions\Action;

class ListSymptoms extends ListRecords
{
    protected static string $resource = SymptomResource::class;

    protected function getHeaderActions(): array
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        return [
            ActionGroup::make([
                ExportAction::make()
                    ->exporter(SymptomExporter::class)
                    ->label('Export Symptoms')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray'),

                ImportAction::make()
                    ->importer(SymptomImporter::class)
                    ->label('Import Symptoms')
                    ->color('warning')
                    ->icon('heroicon-o-arrow-up-tray'),

                Action::make('downloadExample')
                    ->label('Download Example')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('info')
                    ->action(function () {
                        $callback = function () {
                            $headers = ['name', 'description', 'slug', 'featured_image'];
                            $exampleRow = ['Fever', 'High body temperature', 'fever', 'symptom/fever.jpg'];

                            $file = fopen('php://output', 'w');
                            fputcsv($file, $headers);
                            fputcsv($file, $exampleRow);
                            fclose($file);
                        };

                        return response()->streamDownload($callback, 'symptom_example.csv');
                    }),
            ])
                ->label('Actions')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('primary')
                ->button(),

            CreateAction::make()
                ->slideOver()
                ->visible(
                    fn() =>
                    $user?->hasRole('super_admin') ||
                        $user?->can('symptoms.create')
                ),
        ];
    }
}
