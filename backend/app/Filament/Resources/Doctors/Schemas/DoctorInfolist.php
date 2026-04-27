<?php

namespace App\Filament\Resources\Doctors\Schemas;

use App\Filament\Resources\Doctors\DoctorResource;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\{
    ImageEntry,
    TextEntry,
    RepeatableEntry,
    ViewEntry,
    // ViewEntry -- removed to prevent LogicException
};
use Filament\Schemas\Components\{
    Tabs,
    Tabs\Tab,
    Section,
    Grid,
    Flex,
};
use Illuminate\Support\Carbon;
use Filament\Actions\EditAction;
use Filament\Tables\Grouping\Group;
use GuzzleHttp\Psr7\Header;

class DoctorInfolist
{
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
    protected static function formatDepartmentsWithPivot($doctor)
    {
        if (!$doctor) return '—';

        $departments = $doctor->relationLoaded('departments')
            ? $doctor->departments
            : $doctor->departments()->get();

        if ($departments->isEmpty()) return '—';

        return $departments
            ->sortBy('pivot.order')
            ->map(fn($dept) => "{$dept->name} ({$dept->pivot->role})")
            ->join(', ');
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            // Doctor Profile Header
            ViewEntry::make('custom_view')
                ->view('filament.doctors.doctor-infolist')
                ->state(fn($record) => $record)
                ->columnSpanFull(),
        ]);
    }
}
