<?php

namespace Database\Seeders;

use App\Models\DisplayScreen;
use App\Models\Doctor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DisplayScreenSeeder extends Seeder
{
    public function run(): void
    {
        $doctors = Doctor::query()
            ->with('user:id,email')
            ->get()
            ->filter(fn (Doctor $doctor) => $this->isTargetDoctor($doctor))
            ->values();

        if ($doctors->isEmpty()) {
            $this->command?->warn('DisplayScreenSeeder skipped: target doctors not found.');
            return;
        }

        foreach ($doctors as $doctor) {
            $name = 'Display Screen - Dr. ' . trim($doctor->first_name . ' ' . $doctor->last_name);
            $slug = 'display-screen-' . Str::slug($doctor->first_name . '-' . $doctor->last_name);

            DisplayScreen::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'description' => 'Dedicated 50/50 OPD display screen for Dr. ' . trim($doctor->first_name . ' ' . $doctor->last_name) . '.',
                    'is_active' => true,
                    'settings' => [
                        'screen_name' => $name,
                        'screen_location' => $doctor->address_line1 ?: 'Ground Floor OPD',
                        'password' => '123',
                        'doctor_mode' => 'single',
                        'selected_doctors' => [$doctor->id],
                        'show_doctor_list_from_appointments' => true,
                        'display_mode' => 'split_ads',
                        'default_notice' => 'Please keep your token ready and wait for your turn.',
                        'refresh_seconds' => 20,
                        'doctor_rotation_seconds' => 12,
                        'popup_enabled' => true,
                        'popup_duration_seconds' => 8,
                        'ad_popup_enabled' => true,
                        'show_ads_panel' => true,
                        'same_time_card_columns' => 2,
                        'voice_enabled' => true,
                    ],
                ]
            );
        }
    }

    private function isTargetDoctor(Doctor $doctor): bool
    {
        $email = strtolower((string) ($doctor->user?->email ?? ''));

        return in_array($email, ['mjoseph@gmail.com', 'kjoseph@gmail.com'], true);
    }
}
