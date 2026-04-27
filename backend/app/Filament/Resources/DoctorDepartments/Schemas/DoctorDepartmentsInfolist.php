<?php

namespace App\Filament\Resources\DoctorDepartments\Schemas;

use Filament\Schemas\Schema;
use Filament\Infolists\Components\{
    TextEntry,
    ViewEntry,
    RepeatableEntry,
    SpatieMediaLibraryImageEntry
};
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

class DoctorDepartmentsInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            ViewEntry::make('department_view')
                ->view('filament.pages.departments.show')
                ->columnSpanFull(),
        ]);
    }
}
