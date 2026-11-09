<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\ParkingSlot;
use App\Models\ParkingTicket;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ParkingService
{
    public function createSlot(array $data): ParkingSlot
    {
        return DB::transaction(function () use ($data) {
            $slotCode = $this->cleanUppercase($data['slot_code']);
            $zoneId = (int) $data['parking_zone_id'];

            if (ParkingSlot::query()->where('parking_zone_id', $zoneId)->where('slot_code', $slotCode)->exists()) {
                throw ValidationException::withMessages([
                    'slot_code' => 'This slot code is already used in the selected parking zone.',
                ]);
            }

            return ParkingSlot::create([
                'parking_zone_id' => $zoneId,
                'slot_code' => $slotCode,
                'status' => ParkingSlot::STATUS_VACANT,
            ])->load('parkingZone');
        });
    }

    public function updateSlot(int $id, array $data): ParkingSlot
    {
        return DB::transaction(function () use ($id, $data) {
            $slot = ParkingSlot::query()->lockForUpdate()->findOrFail($id);

            if ($slot->status === ParkingSlot::STATUS_OCCUPIED) {
                throw ValidationException::withMessages([
                    'parking_slot' => 'An occupied slot cannot be changed.',
                ]);
            }

            $zoneId = (int) ($data['parking_zone_id'] ?? $slot->parking_zone_id);
            $slotCode = $this->cleanUppercase($data['slot_code'] ?? $slot->slot_code);

            $duplicate = ParkingSlot::query()
                ->where('parking_zone_id', $zoneId)
                ->where('slot_code', $slotCode)
                ->where('id', '!=', $slot->id)
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'slot_code' => 'This slot code is already used in the selected parking zone.',
                ]);
            }

            $slot->update([
                'parking_zone_id' => $zoneId,
                'slot_code' => $slotCode,
            ]);

            return $slot->refresh()->load('parkingZone');
        });
    }

    public function createTicket(array $data): ParkingTicket
    {
        return DB::transaction(function () use ($data) {
            $slot = ParkingSlot::query()
                ->with('parkingZone')
                ->lockForUpdate()
                ->findOrFail($data['parking_slot_id']);

            if ($slot->status !== ParkingSlot::STATUS_VACANT) {
                throw ValidationException::withMessages([
                    'parking_slot_id' => 'The selected parking slot is not vacant.',
                ]);
            }

            $driver = $this->saveDriver($data['driver_name'], $data['vehicle_plate']);
            $ticket = ParkingTicket::create([
                'driver_id' => $driver->id,
                'parking_slot_id' => $slot->id,
                'entry_time' => Carbon::parse($data['entry_time']),
                'hourly_rate' => $slot->parkingZone->hourly_rate,
            ]);

            $slot->update(['status' => ParkingSlot::STATUS_OCCUPIED]);

            return $ticket->load(['driver', 'parkingSlot.parkingZone', 'payment']);
        });
    }

    public function updateTicket(int $id, array $data): ParkingTicket
    {
        return DB::transaction(function () use ($id, $data) {
            $ticket = ParkingTicket::query()->lockForUpdate()->findOrFail($id);
            $ticket->load(['driver', 'parkingSlot']);
            $wasClosed = $ticket->exit_time !== null;

            if ($wasClosed && collect(array_keys($data))->diff(['payment_status'])->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'parking_ticket' => 'A closed parking ticket cannot be changed.',
                ]);
            }

            if (! $wasClosed) {
                if (isset($data['driver_name'], $data['vehicle_plate'])) {
                    $driver = $this->saveDriver($data['driver_name'], $data['vehicle_plate']);
                    $ticket->driver_id = $driver->id;
                }

                if (isset($data['entry_time'])) {
                    $ticket->entry_time = Carbon::parse($data['entry_time']);
                }

                if (isset($data['parking_slot_id']) && (int) $data['parking_slot_id'] !== $ticket->parking_slot_id) {
                    $newSlot = ParkingSlot::query()
                        ->with('parkingZone')
                        ->lockForUpdate()
                        ->findOrFail($data['parking_slot_id']);

                    if ($newSlot->status !== ParkingSlot::STATUS_VACANT) {
                        throw ValidationException::withMessages([
                            'parking_slot_id' => 'The selected parking slot is not vacant.',
                        ]);
                    }

                    $ticket->parkingSlot()->lockForUpdate()->firstOrFail()
                        ->update(['status' => ParkingSlot::STATUS_VACANT]);
                    $newSlot->update(['status' => ParkingSlot::STATUS_OCCUPIED]);
                    $ticket->parking_slot_id = $newSlot->id;
                    $ticket->hourly_rate = $newSlot->parkingZone->hourly_rate;
                }

                if (array_key_exists('exit_time', $data) && $data['exit_time'] !== null) {
                    $exitTime = Carbon::parse($data['exit_time']);
                    $entryTime = Carbon::parse($ticket->entry_time);
                    $durationSeconds = $exitTime->getTimestamp() - $entryTime->getTimestamp();

                    if ($durationSeconds < 0) {
                        throw ValidationException::withMessages([
                            'exit_time' => 'The exit time must be after the entry time.',
                        ]);
                    }

                    $ticket->exit_time = $exitTime;
                    $ticket->total_hours = (int) ceil($durationSeconds / 3600);
                }

                $ticket->save();

                if ($ticket->exit_time !== null) {
                    $ticket->parkingSlot()->lockForUpdate()->firstOrFail()
                        ->update(['status' => ParkingSlot::STATUS_VACANT]);

                    $fee = round($ticket->total_hours * (float) $ticket->hourly_rate, 2);
                    $payment = Payment::updateOrCreate(
                        ['parking_ticket_id' => $ticket->id],
                        [
                            'total_fee' => $fee,
                            'status' => $data['payment_status'] ?? 'Pending',
                        ],
                    );
                } elseif (array_key_exists('payment_status', $data)) {
                    throw ValidationException::withMessages([
                        'payment_status' => 'A ticket must be closed before its payment status can be set.',
                    ]);
                }
            } elseif (array_key_exists('payment_status', $data)) {
                $payment = $ticket->payment()->firstOrFail();
                $payment->update(['status' => $data['payment_status']]);
            }

            return $ticket->refresh()->load(['driver', 'parkingSlot.parkingZone', 'payment']);
        });
    }

    private function saveDriver(string $name, string $vehiclePlate): Driver
    {
        $driver = Driver::query()->firstOrNew([
            'vehicle_plate' => $this->cleanUppercase($vehiclePlate),
        ]);
        $driver->name = $this->cleanString($name);
        $driver->save();

        return $driver;
    }

    private function cleanUppercase(string $value): string
    {
        return mb_strtoupper($this->cleanString($value));
    }

    private function cleanString(string $value): string
    {
        return trim(strip_tags($value));
    }
}
