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
        $imagePool = [
            '/images/cmc-telehealth.png',
            '/images/default.png',
            '/images/deploymeta.png',
            '/images/cmc.png',
            '/images/cmc-telehealth-black.png',
        ];
        $videoPool = [
            'https://www.youtube.com/watch?v=ysz5S6PUM-U',
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4',
        ];

        foreach (DisplayEventCategory::cases() as $index => $category) {
            $isVideo = $index % 3 === 1;
            $isLink = $index % 3 === 2;

            $mediaUrl = $isVideo
                ? $videoPool[$index % count($videoPool)]
                : ($isLink ? null : $imagePool[$index % count($imagePool)]);

            $event = DisplayEvent::updateOrCreate(
                ['slug' => 'display-' . $category->value . '-all-doctors'],
                [
                    'title' => $category->label() . ' Update',
                    'category' => $category->value,
                    'media_type' => $isVideo ? 'video' : ($isLink ? 'link' : 'image'),
                    'media_url' => $mediaUrl,
                    'description' => $category->label() . ' content visible across all OPD display screens.',
                    'link' => $isLink ? 'https://example.com/' . $category->value : ($isVideo ? $mediaUrl : null),
                    'display_order' => $index + 1,
                    'starts_at' => now()->subDay(),
                    'ends_at' => now()->addDays(30),
                    'is_active' => true,
                ]
            );

            $event->doctors()->sync($doctorIds);
        }
    }
}
