<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Advertisement;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class AdvertisementSeeder extends Seeder
{
    public function run(): void
    {
        $sourceDir = public_path('advertisments');
        $targetDir = storage_path('app/public/advertisements');

        if (!File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        $advertisements = [
            [
                'id' => (string) Str::uuid(),
                'title' => 'Free First Consultation',
                'slug' => 'free-first-consultation',
                'description' => 'Get your first consultation absolutely free',
                'link' => 'https://example.com/free-consultation',
                'image' => 'diet-plan.png',
                'is_active' => true,
                'created_by' => null,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'title' => '20% Off on Video Consultation',
                'slug' => '20-off-on-video-consultation',
                'description' => 'Avail flat 20% discount on online consultation',
                'link' => 'https://example.com/video-offer',
                'image' => 'prescription.jpg',
                'is_active' => true,
                'created_by' => null,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'title' => 'Book Hospital Visit',
                'slug' => 'book-hospital-visit',
                'description' => 'Consult doctors directly at the hospital',
                'link' => 'https://example.com/hospital-visit',
                'image' => 'vaccination.png',
                'is_active' => true,
                'created_by' => null,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        ];

        foreach ($advertisements as $adData) {
            $imageFile = $adData['image'];
            unset($adData['image']);

            $sourcePath = $sourceDir . '/' . $imageFile;
            $targetPath = $targetDir . '/' . $imageFile;

            if (File::exists($sourcePath)) {
                File::copy($sourcePath, $targetPath);
            }

            $existingId = Advertisement::withTrashed()
                ->where('slug', $adData['slug'])
                ->value('id');

            if ($existingId) {
                $adData['id'] = $existingId;
            }

            $advertisement = Advertisement::withTrashed()->updateOrCreate(
                ['slug' => $adData['slug']],
                $adData
            );

            if ($advertisement->trashed()) {
                $advertisement->restore();
            }

            if ($imageFile && File::exists($targetPath)) {
                $advertisement->image = 'advertisements/' . $imageFile;
                $advertisement->save();
            }
        }
    }
}
