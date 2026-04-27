<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Services\WherebyService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class VideoConsultationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating video consultations for existing appointments...');

        $wherebyService = app(WherebyService::class);

        if (!$wherebyService->isConfigured()) {
            $this->command->error('Whereby API key is not configured. Please set WHEREBY_API_KEY in your .env file.');
            $this->command->warn('You can also configure it in Settings > Third Party API > Whereby Video Consultation');
            return;
        }

        // Test API connection
        $this->command->info('Testing Whereby API connection...');
        $apiKey = config('services.whereby.api_key', '');
        if (empty($apiKey)) {
            $this->command->error('WHEREBY_API_KEY is empty. Please configure it first.');
            $this->command->warn('Run: php artisan config:clear (if you just updated settings)');
            return;
        }

        // Validate JWT token format (basic check)
        $parts = explode('.', $apiKey);
        if (count($parts) !== 3) {
            $this->command->warn('Warning: API key does not appear to be a valid JWT token format (should have 3 parts separated by dots)');
        }

        $this->command->info('API Key found: ' . substr($apiKey, 0, 20) . '... (length: ' . strlen($apiKey) . ' chars)');

        // Try to make a test API call to verify the key works
        try {
            $testResponse = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(10)->get(config('services.whereby.base_url', 'https://api.whereby.dev/v1') . '/meetings');

            if ($testResponse->status() === 401 || $testResponse->status() === 403) {
                $this->command->error('API Key authentication failed. Please verify your WHEREBY_API_KEY is correct.');
                $this->command->warn('Status: ' . $testResponse->status());
                return;
            } elseif ($testResponse->successful()) {
                $this->command->info('✓ API connection test successful');
            } else {
                $this->command->warn('API connection test returned status: ' . $testResponse->status());
            }
        } catch (\Exception $e) {
            $this->command->warn('Could not test API connection: ' . $e->getMessage());
            $this->command->warn('Continuing anyway...');
        }

        // Get all video consultations that don't have a video consultation record yet
        $videoAppointments = Appointment::where('consultation_type', 'video')
            ->whereDoesntHave('videoConsultation')
            ->with(['patient', 'doctor'])
            ->get();

        if ($videoAppointments->isEmpty()) {
            $this->command->warn('No video appointments found. Creating some test video appointments first...');

            // Create some test video appointments for today
            $this->createTestVideoAppointments();

            // Get them again
            $videoAppointments = Appointment::where('consultation_type', 'video')
                ->whereDoesntHave('videoConsultation')
                ->with(['patient', 'doctor'])
                ->get();
        }

        $created = 0;
        $failed = 0;

        foreach ($videoAppointments as $appointment) {
            try {
                // Check if appointment has required relationships
                if (!$appointment->patient_id || !$appointment->doctor_id) {
                    $failed++;
                    $this->command->warn("✗ Skipping appointment {$appointment->slug}: Missing patient or doctor");
                    continue;
                }

                $videoConsultation = $wherebyService->createVideoConsultation($appointment);

                if ($videoConsultation) {
                    $created++;
                    $this->command->info("✓ Created video consultation for appointment: {$appointment->slug}");
                } else {
                    $failed++;
                    // Check logs for more details
                    $this->command->error("✗ Failed to create video consultation for appointment {$appointment->slug}");
                    $this->command->warn("  → Check logs for detailed error. Common issues:");
                    $this->command->warn("     - Invalid or expired WHEREBY_API_KEY");
                    $this->command->warn("     - Network connectivity issues");
                    $this->command->warn("     - Whereby API rate limits");
                }
            } catch (\Exception $e) {
                $failed++;
                $this->command->error("✗ Failed to create video consultation for appointment {$appointment->slug}");
                $this->command->error("  → Error: " . $e->getMessage());
                if ($this->command->getOutput()->isVerbose()) {
                    $this->command->error("  → Trace: " . $e->getTraceAsString());
                }
            }
        }

        $this->command->info("\nVideo consultations seeding completed!");
        $this->command->info("- Created: {$created}");
        $this->command->info("- Failed: {$failed}");
    }

    /**
     * Create test video appointments if none exist
     */
    private function createTestVideoAppointments()
    {
        $appointments = Appointment::where('consultation_type', 'video')
            ->whereDate('appointment_date', Carbon::today())
            ->count();

        if ($appointments > 0) {
            return; // Already has video appointments
        }

        // Get existing appointments and convert some to video type for today
        $todayAppointments = Appointment::whereDate('appointment_date', Carbon::today())
            ->where('consultation_type', '!=', 'video')
            ->limit(3)
            ->get();

        foreach ($todayAppointments as $appointment) {
            $appointment->update(['consultation_type' => 'video']);
        }

        if ($todayAppointments->isEmpty()) {
            $this->command->warn('No appointments found for today. Please create appointments first.');
        }
    }
}
