<?php

namespace App\Http\Controllers;

use App\Models\PosOrder;
use App\Models\PosShift;
use App\Models\Reservation;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReportsController extends Controller
{
    public function index(Request $request): Response
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->endOfMonth()->toDateString());

        // Reservations are attributed to their check-in date (arrival-based revenue).
        $reservations = Reservation::whereBetween('check_in_date', [$from, $to])
            ->whereNotIn('status', ['cancelled'])
            ->get(['id', 'check_in_date', 'check_out_date', 'status', 'total_amount']);

        $roomRevenue = (float) $reservations->sum('total_amount');
        $nightsSold = (int) $reservations->sum(fn($r) => $r->nights);

        // Completed POS orders within the range.
        $posOrders = PosOrder::where('status', 'completed')
            ->whereBetween('created_at', ["{$from} 00:00:00", "{$to} 23:59:59"])
            ->get(['id', 'total_amount', 'payment_method']);

        $posRevenue = (float) $posOrders->sum('total_amount');

        $byStatus = Reservation::whereBetween('check_in_date', [$from, $to])
            ->select('status', DB::raw('count(*) as count'), DB::raw('sum(total_amount) as revenue'))
            ->groupBy('status')
            ->get();

        // Closed cash-drawer shifts (Z-reports) closed within the range.
        $shifts = PosShift::with('user:id,name')
            ->where('status', 'closed')
            ->whereBetween('closed_at', ["{$from} 00:00:00", "{$to} 23:59:59"])
            ->orderByDesc('closed_at')
            ->get();

        return Inertia::render('Reports/Index', [
            'filters' => ['from' => $from, 'to' => $to],
            'summary' => [
                'reservation_count' => $reservations->count(),
                'room_revenue' => $roomRevenue,
                'nights_sold' => $nightsSold,
                'pos_count' => $posOrders->count(),
                'pos_revenue' => $posRevenue,
                'total_revenue' => $roomRevenue + $posRevenue,
            ],
            'byStatus' => $byStatus->map(fn($r) => [
                'status' => $r->status,
                'count' => (int) $r->count,
                'revenue' => (float) $r->revenue,
            ]),
            'shifts' => $shifts->map(fn($s) => [
                'id' => $s->id,
                'user' => $s->user?->name,
                'opened_at' => $s->opened_at?->format('d/m H:i'),
                'closed_at' => $s->closed_at?->format('d/m H:i'),
                'opening_float' => (float) $s->opening_float,
                'cash_sales' => (float) $s->cash_sales,
                'card_sales' => (float) $s->card_sales,
                'room_charge_sales' => (float) $s->room_charge_sales,
                'total_sales' => (float) $s->total_sales,
                'expected_cash' => (float) $s->expected_cash,
                'counted_cash' => (float) $s->counted_cash,
                'over_short' => (float) $s->over_short,
            ]),
            'currency' => Setting::get('financial.default_currency_symbol', '€'),
        ]);
    }
}
