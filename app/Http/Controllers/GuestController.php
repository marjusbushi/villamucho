<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuestStoreRequest;
use App\Http\Requests\GuestUpdateRequest;
use App\Models\Guest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GuestController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Guest::select(
            'id', 'first_name', 'last_name', 'email', 'phone',
            'nationality', 'document_type', 'created_at'
        )->orderBy('last_name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('nationality')) {
            $query->where('nationality', $request->nationality);
        }

        return Inertia::render('Guests/Index', [
            'guests' => $query->paginate(15),
            'filters' => $request->only('search', 'nationality'),
            'totalGuests' => Guest::count(),
        ]);
    }

    public function show(Guest $guest): Response
    {
        $guest->load([
            'reservations' => fn($q) => $q
                ->with(['room:id,room_number,room_type_id', 'room.roomType:id,name'])
                ->orderByDesc('check_in_date'),
        ]);

        $stays = $guest->reservations;

        // Possible duplicates — same email / phone / document_number (excluding self).
        $duplicates = collect();
        if ($guest->email || $guest->phone || $guest->document_number) {
            $duplicates = Guest::where('id', '!=', $guest->id)
                ->where(function ($q) use ($guest) {
                    if ($guest->email) $q->orWhere('email', $guest->email);
                    if ($guest->phone) $q->orWhere('phone', $guest->phone);
                    if ($guest->document_number) $q->orWhere('document_number', $guest->document_number);
                })
                ->select('id', 'first_name', 'last_name', 'email', 'phone')
                ->limit(10)
                ->get();
        }

        return Inertia::render('Guests/Show', [
            'guest' => [
                'id' => $guest->id,
                'first_name' => $guest->first_name,
                'last_name' => $guest->last_name,
                'email' => $guest->email,
                'phone' => $guest->phone,
                'document_type' => $guest->document_type,
                'document_number' => $guest->document_number,
                'nationality' => $guest->nationality,
                'date_of_birth' => $guest->date_of_birth?->toDateString(),
                'notes' => $guest->notes,
            ],
            'stays' => $stays->map(fn($r) => [
                'id' => $r->id,
                'room' => $r->room?->room_number,
                'room_type' => $r->room?->roomType?->name,
                'check_in_date' => $r->check_in_date?->toDateString(),
                'check_out_date' => $r->check_out_date?->toDateString(),
                'nights' => $r->nights,
                'status' => $r->status,
                'total_amount' => (float) $r->total_amount,
            ]),
            'stats' => [
                'total_stays' => $stays->count(),
                'total_nights' => (int) $stays->sum(fn($r) => $r->nights),
                'lifetime_spend' => (float) $stays->whereNotIn('status', ['cancelled'])->sum('total_amount'),
            ],
            'duplicates' => $duplicates,
        ]);
    }

    public function store(GuestStoreRequest $request): RedirectResponse
    {
        Guest::create($request->validated());

        return back()->with('success', 'Mysafiri u regjistrua me sukses.');
    }

    public function update(GuestUpdateRequest $request, Guest $guest): RedirectResponse
    {
        $guest->update($request->validated());

        return back()->with('success', 'Te dhenat u perditesuan.');
    }

    public function destroy(Guest $guest): RedirectResponse
    {
        if (!auth()->user()->can('delete_guests')) {
            abort(403);
        }

        $guest->delete();

        return back()->with('success', 'Mysafiri u fshi.');
    }
}
