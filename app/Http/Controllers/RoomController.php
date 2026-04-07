<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoomStoreRequest;
use App\Http\Requests\RoomUpdateRequest;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Room::select('id', 'room_type_id', 'room_number', 'floor', 'status', 'notes')
            ->with('roomType:id,name,base_price')
            ->orderBy('room_number');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('floor')) {
            $query->where('floor', $request->floor);
        }

        if ($request->filled('room_type_id')) {
            $query->where('room_type_id', $request->room_type_id);
        }

        return Inertia::render('Rooms/Index', [
            'rooms' => $query->paginate(20),
            'roomTypes' => RoomType::select('id', 'name', 'base_price', 'max_occupancy')->get(),
            'filters' => $request->only('status', 'floor', 'room_type_id'),
            'stats' => [
                'total' => Room::count(),
                'available' => Room::where('status', 'available')->count(),
                'occupied' => Room::where('status', 'occupied')->count(),
                'cleaning' => Room::where('status', 'cleaning')->count(),
                'maintenance' => Room::where('status', 'maintenance')->count(),
            ],
        ]);
    }

    public function store(RoomStoreRequest $request): RedirectResponse
    {
        Room::create($request->validated());

        return back()->with('success', 'Dhoma u shtua me sukses.');
    }

    public function update(RoomUpdateRequest $request, Room $room): RedirectResponse
    {
        $room->update($request->validated());

        return back()->with('success', 'Dhoma u perditesua me sukses.');
    }

    public function destroy(Room $room): RedirectResponse
    {
        if (!auth()->user()->can('delete_rooms')) {
            abort(403);
        }

        $room->delete();

        return back()->with('success', 'Dhoma u fshi.');
    }

    public function updateStatus(Request $request, Room $room): RedirectResponse
    {
        if (!auth()->user()->can('update_rooms')) {
            abort(403);
        }

        $request->validate([
            'status' => ['required', 'in:available,occupied,cleaning,maintenance'],
        ]);

        $room->update(['status' => $request->status]);

        return back()->with('success', "Statusi u ndryshua ne {$request->status}.");
    }
}
