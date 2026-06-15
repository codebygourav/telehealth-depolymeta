<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\DoctorAvailability;
use App\Http\Resources\WordPress\DoctorAvailabilityResource;
use App\Services\DoctorAvailabilityService;
use App\Services\SlotCapacityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Tests\TestCase;

class WordPressAvailabilityApiTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    public function test_doctor_availability_resource_does_not_contain_blocked_before_now(): void
    {
        // 1. Mock SlotCapacityService
        $mockCapacityService = $this->createMock(SlotCapacityService::class);
        $mockCapacityService->method('summary')->willReturn([
            'booked_count' => 0,
            'available_slots' => 1,
            'is_full' => false,
        ]);
        $this->app->instance(SlotCapacityService::class, $mockCapacityService);

        // 2. Mock DoctorAvailabilityService
        $mockAvailabilityService = $this->createMock(DoctorAvailabilityService::class);
        $mockAvailabilityService->method('isDateBlocked')->willReturn(false);
        $this->app->instance(DoctorAvailabilityService::class, $mockAvailabilityService);

        // 3. Create mock models in-memory (no database save)
        $doctor = new Doctor();
        $doctor->id = 'test-doctor-id';

        $availability = new DoctorAvailability();
        $availability->setRelation('doctor', $doctor);
        $availability->date = '2026-06-10';
        $availability->start_time = '09:00:00';
        $availability->end_time = '13:00:00';
        $availability->capacity = 1;
        $availability->consultation_type = 'in-person';
        $availability->is_available = true;
        $availability->is_recurring = false;
        $availability->booking_cutoff_rules = null; // inherits global settings

        // Scenario A: now is within default 4 hours cutoff (e.g., 2026-06-10 06:00:00 is 3 hours before 09:00:00)
        Carbon::setTestNow(Carbon::parse('2026-06-10 06:00:00'));

        // Transform availability with DoctorAvailabilityResource
        $resource = new DoctorAvailabilityResource($availability);
        $arrayResponse = $resource->toArray(new Request());

        // Assert that blocked_before exists, but blocked_before_now does not
        $this->assertArrayHasKey('blocked_before', $arrayResponse);
        $this->assertArrayNotHasKey('blocked_before_now', $arrayResponse);

        // By default, since booking_cutoff_rules is null, it should fallback to global defaults (e.g. 4 hours)
        // Since we are within 4 hours, it should be true.
        $this->assertTrue($arrayResponse['blocked_before']['4_hours'] ?? false);

        // Scenario B: now is outside default 4 hours cutoff (e.g., 2026-06-10 04:00:00 is 5 hours before 09:00:00)
        Carbon::setTestNow(Carbon::parse('2026-06-10 04:00:00'));
        $arrayResponseOutside = (new DoctorAvailabilityResource($availability))->toArray(new Request());
        $this->assertFalse($arrayResponseOutside['blocked_before']['4_hours'] ?? true);

        // Now set custom cutoff rules in-memory
        $availability->booking_cutoff_rules = [['value' => 6, 'unit' => 'hours']];

        // Scenario C: now is within custom cutoff of 6 hours (e.g. 2026-06-10 04:00:00 is 5 hours before 09:00:00)
        Carbon::setTestNow(Carbon::parse('2026-06-10 04:00:00'));
        $resourceCustom = new DoctorAvailabilityResource($availability);
        $arrayResponseCustom = $resourceCustom->toArray(new Request());

        // Assert custom rules are shown instead of global and they are active (true)
        $this->assertArrayHasKey('blocked_before', $arrayResponseCustom);
        $this->assertArrayNotHasKey('blocked_before_now', $arrayResponseCustom);
        $this->assertTrue($arrayResponseCustom['blocked_before']['6_hours'] ?? false);
        $this->assertArrayNotHasKey('4_hours', $arrayResponseCustom['blocked_before']);

        // Scenario D: now is outside custom cutoff of 6 hours (e.g. 2026-06-10 02:00:00 is 7 hours before 09:00:00)
        Carbon::setTestNow(Carbon::parse('2026-06-10 02:00:00'));
        $arrayResponseCustomOutside = (new DoctorAvailabilityResource($availability))->toArray(new Request());
        $this->assertFalse($arrayResponseCustomOutside['blocked_before']['6_hours'] ?? true);
    }
}
