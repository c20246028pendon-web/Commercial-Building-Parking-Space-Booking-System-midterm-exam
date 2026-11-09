<?php

namespace Tests\Feature;

use App\Models\ParkingSlot;
use App\Models\ParkingTicket;
use App\Models\ParkingZone;
use App\Services\ParkingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ParkingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_closing_calculates_rounded_hours_and_releases_slot(): void
    {
        $service = new ParkingService;
        $zone = ParkingZone::query()->create([
            'name' => 'North Wing',
            'hourly_rate' => 5.50,
        ]);
        $slot = $service->createSlot([
            'parking_zone_id' => $zone->id,
            'slot_code' => 'a-01',
        ]);
        $ticket = $service->createTicket([
            'driver_name' => ' <b>Jane Doe</b> ',
            'vehicle_plate' => 'ab-123',
            'parking_slot_id' => $slot->id,
            'entry_time' => '2026-01-15 08:00:00',
        ]);

        $this->assertSame('A-01', $slot->slot_code);
        $this->assertSame('AB-123', $ticket->driver->vehicle_plate);
        $this->assertSame('Jane Doe', $ticket->driver->name);
        $this->assertSame(ParkingSlot::STATUS_OCCUPIED, $slot->fresh()->status);

        $closedTicket = $service->updateTicket($ticket->id, [
            'exit_time' => '2026-01-15 09:01:00',
        ]);

        $this->assertSame(2, $closedTicket->total_hours);
        $this->assertSame('11.00', $closedTicket->payment->total_fee);
        $this->assertSame('Pending', $closedTicket->payment->status);
        $this->assertSame(ParkingSlot::STATUS_VACANT, $slot->fresh()->status);
    }

    public function test_a_parked_slot_cannot_be_assigned_to_a_second_ticket(): void
    {
        $service = new ParkingService;
        $zone = ParkingZone::query()->create([
            'name' => 'South Wing',
            'hourly_rate' => 4,
        ]);
        $slot = $service->createSlot([
            'parking_zone_id' => $zone->id,
            'slot_code' => 's-02',
        ]);
        $service->createTicket([
            'driver_name' => 'First Driver',
            'vehicle_plate' => 'FIRST-1',
            'parking_slot_id' => $slot->id,
            'entry_time' => '2026-01-15 08:00:00',
        ]);

        try {
            $service->createTicket([
                'driver_name' => 'Second Driver',
                'vehicle_plate' => 'SECOND-2',
                'parking_slot_id' => $slot->id,
                'entry_time' => '2026-01-15 08:30:00',
            ]);
            $this->fail('An occupied slot should not be assigned to a second ticket.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('parking_slot_id', $exception->errors());
        }

        $this->assertSame(1, ParkingTicket::query()->count());
    }
}
