<?php

namespace App\Console\Commands;

use App\Services\PaidAppointmentSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SyncPaidAppointments extends Command
{
    protected $signature = 'paid-appointments:sync';

    protected $description = 'Sync paid appointments from the separate paid appointment database into external_bookings';

    public function handle(PaidAppointmentSyncService $syncService): int
    {
        if (! Schema::connection('paid_appointments')->hasTable('paid_appointment')) {
            $this->error('paid_appointment table does not exist on the paid_appointments database connection.');

            return self::FAILURE;
        }

        $results = $syncService->syncExternalPaidAppointments();

        $this->info(sprintf(
            'Paid appointment sync complete. Created: %d, Updated: %d, Unchanged: %d, Skipped: %d.',
            $results['created'],
            $results['updated'],
            $results['unchanged'],
            $results['skipped'],
        ));

        foreach ($results['errors'] as $error) {
            $this->warn($error);
        }

        return self::SUCCESS;
    }
}
