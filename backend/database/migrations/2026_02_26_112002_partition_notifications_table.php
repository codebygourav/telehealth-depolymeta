<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql' || DB::getDriverName() === 'mariadb') {
            // Drop current primary key and recreate it with created_at included
            // MySQL partitioning requires the partitioning key to be part of the primary key
            DB::statement('ALTER TABLE notifications DROP PRIMARY KEY, ADD PRIMARY KEY (id, created_at)');

            // Dynamically generate partition strings up to +5 years in the future
            $startYear = 2024;
            $endYear = (int) date('Y') + 10;
            
            $partitions = [];
            for ($year = $startYear; $year <= $endYear; $year++) {
                $nextYear = $year + 1;
                // Get the UNIX timestamp for Jan 1 of the NEXT year, UTC
                $timestamp = Carbon::create($nextYear, 1, 1, 0, 0, 0, 'UTC')->timestamp;
                $partitions[] = "PARTITION p{$year} VALUES LESS THAN ({$timestamp})";
            }
            $partitions[] = "PARTITION p_future VALUES LESS THAN MAXVALUE";

            $partitionString = implode(",\n                    ", $partitions);

            // Apply partitioning by RANGE on UNIX_TIMESTAMP(created_at)
            // This is allowed dynamically and is safe against timezone errors (Error 1486)
            DB::statement("
                ALTER TABLE notifications PARTITION BY RANGE (UNIX_TIMESTAMP(created_at)) (
                    {$partitionString}
                )
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql' || DB::getDriverName() === 'mariadb') {
            // Remove partitioning
            DB::statement('ALTER TABLE notifications REMOVE PARTITIONING');

            // Restore original primary key
            DB::statement('ALTER TABLE notifications DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        }
    }
};
