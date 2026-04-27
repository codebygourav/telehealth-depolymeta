<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Advertisement;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class AdvertisementSeeder extends Seeder
{
    public function run(): void
    {
        Advertisement::truncate();

        // Ensure advertisement directory exists
        $advertisementDir = storage_path('app/public/advertisement');
        if (!File::exists($advertisementDir)) {
            File::makeDirectory($advertisementDir, 0755, true);
        }

        // Copy default image to advertisement folder if it doesn't exist
        $defaultImagePath = public_path('images/user-avatar.png');
        $targetImagePath = storage_path('app/public/advertisement/user-avatar.png');

        if (File::exists($defaultImagePath)) {
            if (!File::exists($targetImagePath)) {
                File::copy($defaultImagePath, $targetImagePath);
            }
        }

        $advertisements = [
            [
                'id' => (string) Str::uuid(),
                'title' => 'Free First Consultation',
                'slug' => 'free-first-consultation',
                'description' => 'Get your first consultation absolutely free',
                'link' => 'https://example.com/free-consultation',
                'image' => 'advertisement/user-avatar.png',
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
                'image' => 'advertisement/user-avatar.png',
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
                'image' => 'advertisement/user-avatar.png',
                'is_active' => true,
                'created_by' => null,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        ];

        // Create advertisements and save images using the trait
        foreach ($advertisements as $adData) {
            $image = $adData['image'];
            unset($adData['image']); // Remove image from data array

            $advertisement = Advertisement::create($adData);

            // Set image which will be saved via the trait
            if ($image) {
                $advertisement->image = $image;
                $advertisement->save(); // Trigger savePendingModuleDocuments
            }
        }
    }
}
