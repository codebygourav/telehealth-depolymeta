<?php

namespace App\Filament\Resources\DoctorDepartments\Pages;

use App\Filament\Resources\DoctorDepartments\DoctorDepartmentResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use App\Filament\Resources\Pages\ListRecords;
use App\Filament\Imports\DepartmentImporter;
use App\Filament\Exports\DepartmentExporter;

class ListDoctorDepartments extends ListRecords
{
    protected static string $resource = DoctorDepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ImportAction::make()
                    ->importer(DepartmentImporter::class)
                    ->label('Import Departments')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success'),
                ExportAction::make()
                    ->exporter(DepartmentExporter::class)
                    ->label('Export Departments')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info'),
                Action::make('downloadSample')
                    ->label('Download Sample')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->action(fn () => $this->downloadSample()),
            ])
            ->label('Import/Export')
            ->icon('heroicon-o-arrow-path')
            ->button(),
            
            CreateAction::make()->visible(fn() => DoctorDepartmentResource::canCreate() || auth()->user()?->hasRole('super_admin') || auth()->user()?->can('create_departments')),
        ];
    }

    public function downloadSample()
    {
        $headers = [
            'Department Name', 
            'Status', 
            'Description', 
            'Tab Layout', 
            'Symptoms', 
            'Additional Information (JSON)', 
            'FAQs (JSON)', 
            'Publications (JSON)', 
            'Tabs (JSON)',
            'Featured Image',
            'Department Stamp',
            'Doctors (JSON)'
        ];

        $samples = [
            [
                'Neurology', 
                'active', 
                'Specialized care for disorders of the nervous system.', 
                'Yes', 
                'Blurred Vision, Severe Headache', 
                '[]', 
                '[]', 
                '[]', 
                '[{"title":"Core Focus","content":"Brain, spinal cord and nerves.","order":1,"gallery":["brain_scan.jpg"]}]',
                'neurology_featured.jpg',
                'neurology_stamp.png',
                '[{"email":"doctor@example.com","role":"Head of Department","order":1}]'
            ],
            [
                'Orthopedics', 
                'active', 
                'Focus on the musculoskeletal system.', 
                'No', 
                'Joint Pain, Bone Fracture', 
                '[{"content":"<p>Comprehensive orthopedic care including surgery and rehabilitation.<\/p>"}]', 
                '[{"question":"Do you handle sports injuries?","answer":"Yes, we specialize in sports medicine and injury recovery."}]', 
                '[{"publication_name":"Bone Health Guide","publication_date":"2023-11-15","publication_description":"A patient guide to maintaining bone density."}]', 
                '[]',
                'orthopedics_featured.jpg',
                ''
            ]
        ];

        return response()->streamDownload(function () use ($headers, $samples) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($samples as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 'department_import_sample.csv');
    }
}
