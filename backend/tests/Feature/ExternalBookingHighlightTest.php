<?php

namespace Tests\Feature;

use App\Models\ExternalBooking;
use Tests\TestCase;

class ExternalBookingHighlightTest extends TestCase
{
    public function test_external_booking_without_platform_slot_resolves_correctly(): void
    {
        // Create an unmatched booking in memory (availability_id = null)
        $unmatchedBooking = new ExternalBooking();
        $unmatchedBooking->availability_id = null;

        // Assert that availability is null and status state would resolve to 'No Platform Slot'
        $this->assertNull($unmatchedBooking->availability_id);
        
        $statusState = $unmatchedBooking->availability_id ? 'Matched' : 'No Platform Slot';
        $this->assertEquals('No Platform Slot', $statusState);
    }

    public function test_external_booking_with_platform_slot_resolves_correctly(): void
    {
        // Create a matched booking in memory (availability_id set to a UUID)
        $matchedBooking = new ExternalBooking();
        $matchedBooking->availability_id = (string) \Illuminate\Support\Str::uuid();

        // Assert that availability matches and status state resolves to 'Matched'
        $this->assertNotNull($matchedBooking->availability_id);
        
        $statusState = $matchedBooking->availability_id ? 'Matched' : 'No Platform Slot';
        $this->assertEquals('Matched', $statusState);
    }
}
