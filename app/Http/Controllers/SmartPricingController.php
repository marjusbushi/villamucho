<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\RateOverride;
use App\Models\Setting;
use App\Services\SmartPricing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SmartPricingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Pricing/Smart', [
            'suggestions' => SmartPricing::suggestions(),
            'settings' => SmartPricing::settings(),
            'currency' => Setting::get('financial.default_currency_symbol', '€'),
        ]);
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

        return back()->with('success', 'Çmimi u rikthye te tarifa normale.');
    }
}
