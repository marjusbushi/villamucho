<?php

namespace App\Http\Controllers;

use App\Models\PosOrder;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Setting;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $today = today();

        $totalRooms = Room::count();
        $occupied = Room::where('status', 'occupied')->count();

        $arrivals = Reservation::whereDate('check_in_date', $today)
            ->whereIn('status', ['confirmed', 'pending'])
            ->with(['room:id,room_number', 'guest:id,first_name,last_name'])
            ->orderBy('check_in_date')
            ->get();

        $departures = Reservation::whereDate('check_out_date', $today)
            ->where('status', 'checked_in')
            ->with(['room:id,room_number', 'guest:id,first_name,last_name'])
            ->get();

        $map = fn($r) => [
            'id' => $r->id,
            'guest' => trim("{$r->guest?->first_name} {$r->guest?->last_name}"),
            'room' => $r->room?->room_number,
            'status' => $r->status,
        ];

        return Inertia::render('Dashboard', [
            'stats' => [
                'occupancy' => $totalRooms ? (int) round($occupied / $totalRooms * 100) : 0,
                'occupied' => $occupied,
                'total_rooms' => $totalRooms,
                'in_house' => Reservation::where('status', 'checked_in')->count(),
                'arrivals' => $arrivals->count(),
                'departures' => $departures->count(),
                'to_clean' => Room::where('status', 'cleaning')->count(),
                'open_pos' => PosOrder::where('status', 'open')->count(),
                'pos_revenue_today' => (float) PosOrder::where('status', 'completed')
                    ->whereDate('created_at', $today)->sum('total_amount'),
            ],
            'arrivals' => $arrivals->map($map),
            'departures' => $departures->map($map),
            'currency' => Setting::get('financial.default_currency_symbol', '€'),
        ]);
    }
}
