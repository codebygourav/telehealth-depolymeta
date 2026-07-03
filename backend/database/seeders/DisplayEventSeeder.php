<?php

namespace Database\Seeders;

use App\Enums\DisplayEventCategory;
use App\Models\DisplayEvent;
use App\Models\Doctor;
use Illuminate\Database\Seeder;

class DisplayEventSeeder extends Seeder
{
    public function run(): void
    {
        $doctors = Doctor::orderBy('first_name')->get();

        if ($doctors->isEmpty()) {
            $this->command?->warn('DisplayEventSeeder skipped: no doctors found.');
            return;
        }

        $doctorIds = $doctors->pluck('id')->all();
        $youtubeUrl = 'https://youtu.be/Y_VHZmMJVgA?si=8iFfe-K5xroek08z';

        $event = DisplayEvent::updateOrCreate(
            ['slug' => 'display-opd-youtube-spotlight'],
            [
                'title' => 'OPD Spotlight Video',
                'category' => DisplayEventCategory::ADVERTISEMENT->value,
                'media_type' => 'video',
                'media_url' => $youtubeUrl,
                'link' => null,
                'description' => 'Primary OPD display spotlight video.',
                'display_order' => 1,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addDays(30),
                'is_active' => true,
                'autoplay' => true,
                'loop' => true,
                'muted' => true,
                'open_in_new_tab' => false,
            ]
        );

        $event->doctors()->sync($doctorIds);
    }
}
