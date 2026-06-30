<?php

namespace App\Http\Controllers;

use App\Jobs\PushRoomTypeAri;
use App\Models\AuditLog;
use App\Models\RateOverride;
use App\Models\RoomType;
use App\Models\Setting;
use App\Services\SmartPricing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class SmartPricingController extends Controller
{
    public function index(Request $request): Response
    {
        $types = RoomType::orderBy('name')->get(['id', 'name', 'base_price']);

        $base = [
            'roomTypes' => $types->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values(),
            'settings' => SmartPricing::settings(),
            'currency' => Setting::get('financial.default_currency_symbol', '€'),
        ];

        if ($types->isEmpty()) {
            $today = Carbon::today()->startOfMonth();

            return Inertia::render('Pricing/Smart', array_merge($base, [
                'selectedTypeId' => null, 'days' => [],
                'month' => $today->toDateString(),
                'prevMonth' => $today->copy()->subMonth()->toDateString(),
                'nextMonth' => $today->copy()->addMonth()->toDateString(),
            ]));
        }

        $selected = $types->firstWhere('id', (int) $request->input('room_type_id')) ?? $types->first();

        $month = $request->filled('month')
            ? Carbon::parse($request->input('month'))->startOfMonth()
            : Carbon::today()->startOfMonth();
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();

        return Inertia::render('Pricing/Smart', array_merge($base, [
            'selectedTypeId' => $selected->id,
            'month' => $from->toDateString(),
            'prevMonth' => $from->copy()->subMonth()->toDateString(),
            'nextMonth' => $from->copy()->addMonth()->toDateString(),
            'days' => SmartPricing::calendar($selected, $from, $to),
        ]));
    }

    /** Accept a suggestion → set the price for that single date + room type. */
    public function apply(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'room_type_id' => ['required', 'exists:room_types,id'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
        ]);

        // whereDate matches on the date part (the column may carry a 00:00:00 time), so a
        // re-apply UPDATES the existing row instead of hitting the unique(date,type) index.
        $override = RateOverride::whereDate('date', $data['date'])
            ->where('room_type_id', $data['room_type_id'])
            ->first()
            ?? new RateOverride(['date' => $data['date'], 'room_type_id' => $data['room_type_id']]);

        $override->price = $data['price'];
        $override->created_by = auth()->id();
        $override->save();

        AuditLog::record('pricing.smart_apply', $override, $data);

        // Price changed for this date -> re-push that room type to Channex.
        PushRoomTypeAri::dispatch((int) $data['room_type_id']);

        return back()->with('success', 'Çmimi u aplikua për këtë datë.');
    }

    /** Remove a date override → revert that date to the seasonal/base price. */
    public function remove(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'room_type_id' => ['required', 'exists:room_types,id'],
        ]);

        RateOverride::whereDate('date', $data['date'])
            ->where('room_type_id', $data['room_type_id'])
            ->delete();

        // Price reverted for this date -> re-push that room type to Channex.
        PushRoomTypeAri::dispatch((int) $data['room_type_id']);

        return back()->with('success', 'Çmimi u rikthye te tarifa normale.');
    }
}
