<?php

namespace Tests\Feature;

use App\Models\DoctorAvailability;
use Tests\TestCase;

class DoctorAvailabilityBlockTest extends TestCase
{
    /**
     * Test DoctorAvailability isBlockedOnDate method.
     */
    public function test_availability_is_blocked_on_date_correctly(): void
    {
        $availability = new DoctorAvailability();
        $availability->is_available = true;
        $availability->is_recurring = true;
        $availability->blocked_dates = ['2026-06-03', '2026-06-10'];

        $this->assertTrue($availability->isBlockedOnDate('2026-06-03'));
        $this->assertTrue($availability->isBlockedOnDate('2026-06-10'));
        $this->assertFalse($availability->isBlockedOnDate('2026-06-04'));

        // Test with new structured format
        $availability->blocked_dates = [
            ['date' => '2026-06-03', 'start_time' => '16:00:00', 'end_time' => '21:00:00'],
            ['date' => '2026-06-10', 'start_time' => '09:00:00', 'end_time' => '13:00:00']
        ];
        $this->assertTrue($availability->isBlockedOnDate('2026-06-03'));
        $this->assertTrue($availability->isBlockedOnDate('2026-06-10'));
        $this->assertFalse($availability->isBlockedOnDate('2026-06-04'));

        // If not available generally, it should be blocked on any date
        $availability->is_available = false;
        $this->assertTrue($availability->isBlockedOnDate('2026-06-04'));

        // Test with slot-level times specified
        $availability->is_available = true;
        $availability->start_time = '16:00';
        $availability->end_time = '21:00';
        $availability->blocked_dates = [
            ['date' => '2026-06-03', 'start_time' => '16:00:00', 'end_time' => '21:00:00'],
            ['date' => '2026-06-10', 'start_time' => '09:00:00', 'end_time' => '13:00:00']
        ];

        // Should be blocked on 2026-06-03 because the times match exactly
        $this->assertTrue($availability->isBlockedOnDate('2026-06-03'));

        // Should NOT be blocked on 2026-06-10 because the times do not match (09:00-13:00 vs 16:00-21:00)
        $this->assertFalse($availability->isBlockedOnDate('2026-06-10'));
    }
}
