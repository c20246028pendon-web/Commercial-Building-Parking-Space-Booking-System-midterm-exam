<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreParkingTicketRequest;
use App\Http\Requests\UpdateParkingTicketRequest;
use App\Models\ParkingSlot;
use App\Models\ParkingTicket;
use App\Services\ParkingService;
use Illuminate\Http\JsonResponse;

class ParkingTicketController extends Controller
{
    public function index(): JsonResponse
    {
        $tickets = ParkingTicket::query()
            ->with(['driver', 'parkingSlot.parkingZone', 'payment'])
            ->latest('entry_time')
            ->paginate(15);

        return response()->json($tickets);
    }

    public function create(): JsonResponse
    {
        return response()->json([
            'vacant_slots' => ParkingSlot::query()
                ->where('status', ParkingSlot::STATUS_VACANT)
                ->with('parkingZone:id,name,hourly_rate')
                ->orderBy('slot_code')
                ->get(['id', 'parking_zone_id', 'slot_code']),
        ]);
    }

    public function store(StoreParkingTicketRequest $request, ParkingService $parkingService): JsonResponse
    {
        $ticket = $parkingService->createTicket($request->validated());

        return response()->json(['data' => $ticket], 201);
    }

    public function update(UpdateParkingTicketRequest $request, string $id, ParkingService $parkingService): JsonResponse
    {
        $ticket = $parkingService->updateTicket((int) $id, $request->validated());

        return response()->json(['data' => $ticket]);
    }
}
