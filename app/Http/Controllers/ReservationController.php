<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReservationStoreRequest;
use App\Http\Requests\ReservationUpdateRequest;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReservationController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Reservation::select(
            'id', 'room_id', 'guest_id', 'check_in_date', 'check_out_date',
            'status', 'total_amount', 'adults', 'children', 'created_at'
        )
            ->with(['room:id,room_number', 'room.roomType:id,name', 'guest:id,first_name,last_name'])
            ->orderByDesc('check_in_date');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('guest', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        return Inertia::render('Reservations/Index', [
            'reservations' => $query->paginate(15),
            'rooms' => Room::select('id', 'room_number', 'room_type_id')
                ->with('roomType:id,name,base_price')
                ->orderBy('room_number')
                ->get(),
            'guests' => Guest::select('id', 'first_name', 'last_name', 'email', 'phone')
                ->orderBy('last_name')
                ->get(),
            'filters' => $request->only('status', 'search'),
            'stats' => [
                'total' => Reservation::count(),
                'pending' => Reservation::where('status', 'pending')->count(),
                'confirmed' => Reservation::where('status', 'confirmed')->count(),
                'checked_in' => Reservation::where('status', 'checked_in')->count(),
            ],
        ]);
    }

    public function store(ReservationStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $room = Room::with('roomType')->findOrFail($data['room_id']);

        $checkIn = now()->parse($data['check_in_date']);
        $checkOut = now()->parse($data['check_out_date']);
        $nights = $checkIn->diffInDays($checkOut);

        $data['total_amount'] = $room->roomType->base_price * $nights;
        $data['created_by'] = auth()->id();
        $data['status'] = $data['status'] ?? 'pending';

        Reservation::create($data);

        return back()->with('success', 'Rezervimi u krijua me sukses.');
    }

    public function update(ReservationUpdateRequest $request, Reservation $reservation): RedirectResponse
    {
        $data = $request->validated();
        $room = Room::with('roomType')->findOrFail($data['room_id']);

        $checkIn = now()->parse($data['check_in_date']);
        $checkOut = now()->parse($data['check_out_date']);
        $nights = $checkIn->diffInDays($checkOut);
        $data['total_amount'] = $room->roomType->base_price * $nights;

        $reservation->update($data);

        return back()->with('success', 'Rezervimi u perditesua.');
    }

    public function checkIn(Reservation $reservation): RedirectResponse
    {
        if ($reservation->status !== 'confirmed') {
            return back()->with('error', 'Vetem rezervimet e konfirmuara mund te bejne check-in.');
        }

        DB::transaction(function () use ($reservation) {
            $reservation->update(['status' => 'checked_in']);
            $reservation->room->update(['status' => 'occupied']);
        });

        return back()->with('success', "Check-in per dhomen {$reservation->room->room_number} u krye.");
    }

    public function checkOut(Reservation $reservation): RedirectResponse
    {
        if ($reservation->status !== 'checked_in') {
            return back()->with('error', 'Vetem mysafiret brenda mund te bejne check-out.');
        }

        DB::transaction(function () use ($reservation) {
            $reservation->update(['status' => 'checked_out']);
            $reservation->room->update(['status' => 'cleaning']);
        });

        return back()->with('success', "Check-out per dhomen {$reservation->room->room_number} u krye.");
    }

    public function cancel(Reservation $reservation): RedirectResponse
    {
        if (in_array($reservation->status, ['checked_in', 'checked_out'])) {
            return back()->with('error', 'Nuk mund te anulohet nje rezervim aktiv ose i perfunduar.');
        }

        $reservation->update(['status' => 'cancelled']);

        return back()->with('success', 'Rezervimi u anulua.');
    }

    public function destroy(Reservation $reservation): RedirectResponse
    {
        if (!auth()->user()->can('delete_reservations')) {
            abort(403);
        }

        $reservation->delete();

        return back()->with('success', 'Rezervimi u fshi.');
    }
}
