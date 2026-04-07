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
        return Inertia::render('Guests/Show', [
            'guest' => $guest,
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
