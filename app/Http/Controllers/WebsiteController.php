<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WebsiteController extends Controller
{
    public function home(): Response
    {
        $roomTypes = RoomType::select('id', 'name', 'description', 'base_price', 'max_occupancy', 'amenities')
            ->withCount('rooms')
            ->get();

        return Inertia::render('Website/Home', [
            'roomTypes' => $roomTypes,
            'hotel' => Setting::getGroup('hotel'),
        ]);
    }

    public function rooms(): Response
    {
        $roomTypes = RoomType::select('id', 'name', 'description', 'base_price', 'max_occupancy', 'amenities')
            ->withCount(['rooms', 'rooms as available_count' => fn($q) => $q->where('status', 'available')])
            ->get();

        return Inertia::render('Website/Rooms', [
            'roomTypes' => $roomTypes,
        ]);
    }

    public function bookingForm(Request $request): Response
    {
        $roomTypes = RoomType::select('id', 'name', 'base_price', 'max_occupancy')
            ->get();

        return Inertia::render('Website/Book', [
            'roomTypes' => $roomTypes,
            'preselectedType' => $request->input('room_type'),
            'hotel' => Setting::getGroup('hotel'),
        ]);
    }

    public function checkAvailability(Request $request)
    {
        $request->validate([
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'room_type_id' => ['nullable', 'exists:room_types,id'],
        ]);

        $query = Room::select('id', 'room_number', 'room_type_id', 'floor')
            ->with('roomType:id,name,base_price,max_occupancy,amenities')
            ->where('status', '!=', 'maintenance');

        if ($request->filled('room_type_id')) {
            $query->where('room_type_id', $request->room_type_id);
        }

        $rooms = $query->get()->filter(function ($room) use ($request) {
            return Reservation::isRoomAvailable($room->id, $request->check_in, $request->check_out);
        })->values();

        $nights = now()->parse($request->check_in)->diffInDays($request->check_out);

        return response()->json([
            'rooms' => $rooms->map(fn($r) => [
                'id' => $r->id,
                'room_number' => $r->room_number,
                'floor' => $r->floor,
                'room_type' => $r->roomType->name,
                'price_per_night' => $r->roomType->base_price,
                'total_price' => $r->roomType->base_price * $nights,
                'max_occupancy' => $r->roomType->max_occupancy,
                'amenities' => $r->roomType->amenities,
            ]),
            'nights' => $nights,
        ]);
    }

    public function submitBooking(Request $request): RedirectResponse
    {
        $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'adults' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $room = Room::with('roomType')->findOrFail($request->room_id);

        if (!Reservation::isRoomAvailable($room->id, $request->check_in, $request->check_out)) {
            return back()->with('error', 'Kjo dhome nuk eshte me e disponueshme per keto data.');
        }

        $nights = now()->parse($request->check_in)->diffInDays($request->check_out);

        // Find or create guest
        $guest = Guest::firstOrCreate(
            ['email' => $request->email],
            [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
            ]
        );

        $reservation = Reservation::create([
            'room_id' => $room->id,
            'guest_id' => $guest->id,
            'check_in_date' => $request->check_in,
            'check_out_date' => $request->check_out,
            'status' => 'pending',
            'total_amount' => $room->roomType->base_price * $nights,
            'adults' => $request->adults,
            'notes' => $request->notes,
            'created_by' => 1, // system/website
        ]);

        return redirect()->route('website.booking.confirmation', $reservation->id);
    }

    public function bookingConfirmation(Reservation $reservation): Response
    {
        $reservation->load(['room.roomType', 'guest']);

        return Inertia::render('Website/BookingConfirmation', [
            'reservation' => $reservation,
            'hotel' => Setting::getGroup('hotel'),
        ]);
    }

    public function about(): Response
    {
        return Inertia::render('Website/About', [
            'hotel' => Setting::getGroup('hotel'),
        ]);
    }

    public function contact(): Response
    {
        return Inertia::render('Website/Contact', [
            'hotel' => Setting::getGroup('hotel'),
        ]);
    }

    public function submitContact(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        // For now, just log it. Later: send email or save to DB
        \Illuminate\Support\Facades\Log::info('Contact form submission', $request->only('name', 'email', 'message'));

        return back()->with('success', 'Faleminderit! Mesazhi juaj u derua me sukses.');
    }
}
