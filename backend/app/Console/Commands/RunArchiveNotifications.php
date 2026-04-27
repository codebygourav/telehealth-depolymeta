<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RunArchiveNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:archive {months=6}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archive old notifications to the notifications_archive table';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $monthsToKeep = (int) $this->argument('months');
        $cutoffDate = Carbon::now()->subMonths($monthsToKeep);

        $this->info("Starting to archive notifications older than {$cutoffDate->toDateTimeString()}");
        Log::info("Starting to archive notifications older than {$cutoffDate->toDateTimeString()}");

        $archivedCount = 0;

        while (true) {
            // Fetch older notifications limit 1000
            $notifications = DB::table('notifications')
                ->where('created_at', '<', $cutoffDate)
                ->limit(1000)
                ->get();

            if ($notifications->isEmpty()) {
                break;
            }

            $archiveData = [];
            $idsToDelete = [];

            foreach ($notifications as $notification) {
                $data = (array) $notification;
                $data['is_archived'] = true; // explicitly mark as archived

                $archiveData[] = $data;
                $idsToDelete[] = $notification->id;
            }

            DB::transaction(function () use ($archiveData, $idsToDelete) {
                // Insert into archive
                DB::table('notifications_archive')->insert($archiveData);
                // Delete from original table
                DB::table('notifications')->whereIn('id', $idsToDelete)->delete();
            });

            $archivedCount += count($idsToDelete);
        }

        $this->info("Finished archiving {$archivedCount} notifications to the 'notifications_archive' table.");
        Log::info("Finished archiving {$archivedCount} notifications to the 'notifications_archive' table.");
    }
}
