<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreParkingSlotRequest;
use App\Http\Requests\UpdateParkingSlotRequest;
use App\Models\ParkingSlot;
use App\Models\ParkingZone;
use App\Services\ParkingService;
use Illuminate\Http\JsonResponse;

class ParkingSlotController extends Controller
{
    public function index(): JsonResponse
    {
        $slots = ParkingSlot::query()
            ->with('parkingZone')
            ->orderBy('parking_zone_id')
            ->orderBy('slot_code')
            ->paginate(15);

        return response()->json($slots);
    }

    public function create(): JsonResponse
    {
        return response()->json([
            'zones' => ParkingZone::query()->orderBy('name')->get(['id', 'name', 'hourly_rate']),
        ]);
    }

    public function store(StoreParkingSlotRequest $request, ParkingService $parkingService): JsonResponse
    {
        $slot = $parkingService->createSlot($request->validated());

        return response()->json(['data' => $slot], 201);
    }

    public function update(UpdateParkingSlotRequest $request, string $id, ParkingService $parkingService): JsonResponse
    {
        $slot = $parkingService->updateSlot((int) $id, $request->validated());

        return response()->json(['data' => $slot]);
    }
}
