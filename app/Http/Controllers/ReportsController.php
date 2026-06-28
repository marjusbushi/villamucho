<?php

namespace App\Http\Controllers;

use App\Models\FolioItem;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\PosOrder;
use App\Models\PosShift;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReportsController extends Controller
{
    /** Reports hub — the catalog of every report. */
    public function index(): Response
    {
        return Inertia::render('Reports/Index', [
            'currency' => $this->currency(),
        ]);
    }

    /** Executive summary: revenue (room+F&B), occupancy, ADR, RevPAR, VAT, commission. */
    public function executive(Request $request): Response
    {
        [$from, $to, $days] = $this->range($request);

        $reservations = Reservation::whereBetween('check_in_date', [$from, $to])
            ->where('status', '!=', 'cancelled')
            ->get(['id', 'check_in_date', 'check_out_date', 'status', 'total_amount', 'commission_amount']);

        $roomRevenue = (float) $reservations->sum('total_amount');
        $nightsSold = (int) $reservations->sum(fn ($r) => $r->nights);
        $commission = (float) $reservations->sum('commission_amount');

        $posRevenue = (float) PosOrder::where('status', 'completed')
            ->whereBetween('created_at', ["{$from} 00:00:00", "{$to} 23:59:59"])->sum('total_amount');
        $posCount = PosOrder::where('status', 'completed')
            ->whereBetween('created_at', ["{$from} 00:00:00", "{$to} 23:59:59"])->count();

        $totalRevenue = $roomRevenue + $posRevenue;
        $roomsCount = Room::count();
        $availableRoomNights = $roomsCount * $days;
        $taxRate = (float) Setting::get('financial.tax_rate', 20);
        $vat = $taxRate > 0 ? round($totalRevenue - ($totalRevenue / (1 + $taxRate / 100)), 2) : 0.0;

        $byStatus = Reservation::whereBetween('check_in_date', [$from, $to])
            ->select('status', DB::raw('count(*) as count'), DB::raw('sum(total_amount) as revenue'))
            ->groupBy('status')->get()
            ->map(fn ($r) => ['status' => $r->status, 'count' => (int) $r->count, 'revenue' => (float) $r->revenue]);

        return Inertia::render('Reports/Executive', [
            'filters' => ['from' => $from, 'to' => $to],
            'summary' => [
                'room_revenue' => round($roomRevenue, 2),
                'pos_revenue' => round($posRevenue, 2),
                'total_revenue' => round($totalRevenue, 2),
                'commission' => round($commission, 2),
                'net_room_revenue' => round($roomRevenue - $commission, 2),
                'vat' => $vat,
                'net_revenue' => round($totalRevenue - $vat, 2),
                'reservation_count' => $reservations->count(),
                'pos_count' => $posCount,
                'nights_sold' => $nightsSold,
                'rooms_count' => $roomsCount,
                'days' => $days,
                'occupancy' => $availableRoomNights ? round($nightsSold / $availableRoomNights * 100, 1) : 0,
                'adr' => $nightsSold ? round($roomRevenue / $nightsSold, 2) : 0,
                'revpar' => $availableRoomNights ? round($roomRevenue / $availableRoomNights, 2) : 0,
            ],
            'byStatus' => $byStatus,
            'currency' => $this->currency(),
        ]);
    }

    /** Production by channel: bookings, revenue, commission, net, nights. */
    public function channels(Request $request): Response
    {
        [$from, $to] = $this->range($request);

        $rows = Reservation::whereBetween('check_in_date', [$from, $to])
            ->where('status', '!=', 'cancelled')
            ->get(['channel', 'total_amount', 'commission_amount', 'check_in_date', 'check_out_date'])
            ->groupBy(fn ($r) => $r->channel ?: 'manual')
            ->map(function ($group, $channel) {
                $revenue = (float) $group->sum('total_amount');
                $commission = (float) $group->sum('commission_amount');
                return [
                    'channel' => $channel,
                    'count' => $group->count(),
                    'nights' => (int) $group->sum(fn ($r) => $r->nights),
                    'revenue' => round($revenue, 2),
                    'commission' => round($commission, 2),
                    'net' => round($revenue - $commission, 2),
                ];
            })
            ->sortByDesc('revenue')->values();

        return Inertia::render('Reports/Channels', [
            'filters' => ['from' => $from, 'to' => $to],
            'rows' => $rows,
            'totals' => [
                'count' => (int) $rows->sum('count'),
                'nights' => (int) $rows->sum('nights'),
                'revenue' => round((float) $rows->sum('revenue'), 2),
                'commission' => round((float) $rows->sum('commission'), 2),
                'net' => round((float) $rows->sum('net'), 2),
            ],
            'currency' => $this->currency(),
        ]);
    }

    /** Outstanding balances (debtors): every non-cancelled stay that still owes money. */
    public function outstanding(): Response
    {
        $stays = Reservation::whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->with(['room:id,room_number', 'guest:id,first_name,last_name,phone'])
            ->get(['id', 'room_id', 'guest_id', 'status', 'check_in_date', 'check_out_date', 'total_amount']);

        $ids = $stays->pluck('id')->all();
        $folio = FolioItem::whereIn('reservation_id', $ids)
            ->select('reservation_id',
                DB::raw("SUM(CASE WHEN type NOT IN ('discount','room') THEN amount ELSE 0 END) as charges"),
                DB::raw("SUM(CASE WHEN type = 'discount' THEN amount ELSE 0 END) as discounts"))
            ->groupBy('reservation_id')->get()->keyBy('reservation_id');
        $pay = Payment::whereIn('reservation_id', $ids)
            ->select('reservation_id', DB::raw('SUM(amount) as paid'))
            ->groupBy('reservation_id')->get()->keyBy('reservation_id');

        $rows = $stays->map(function ($r) use ($folio, $pay) {
            $gross = round((float) $r->total_amount + (float) ($folio[$r->id]->charges ?? 0) - (float) ($folio[$r->id]->discounts ?? 0), 2);
            $paid = (float) ($pay[$r->id]->paid ?? 0);
            return [
                'id' => $r->id,
                'guest' => trim("{$r->guest?->first_name} {$r->guest?->last_name}") ?: 'Mysafir',
                'phone' => $r->guest?->phone,
                'room' => $r->room?->room_number,
                'status' => $r->status,
                'check_in' => $r->check_in_date?->toDateString(),
                'check_out' => $r->check_out_date?->toDateString(),
                'gross' => $gross,
                'paid' => round($paid, 2),
                'balance' => round($gross - $paid, 2),
            ];
        })->filter(fn ($r) => $r['balance'] > 0.009)->sortByDesc('balance')->values();

        return Inertia::render('Reports/Outstanding', [
            'rows' => $rows,
            'total' => round((float) $rows->sum('balance'), 2),
            'currency' => $this->currency(),
        ]);
    }

    /** Z-Report: closed cash-drawer shifts per staff/day with over/short. */
    public function shifts(Request $request): Response
    {
        [$from, $to] = $this->range($request);

        $shifts = PosShift::with('user:id,name')
            ->where('status', 'closed')
            ->whereBetween('closed_at', ["{$from} 00:00:00", "{$to} 23:59:59"])
            ->orderByDesc('closed_at')->get();

        return Inertia::render('Reports/Shifts', [
            'filters' => ['from' => $from, 'to' => $to],
            'shifts' => $shifts->map(fn ($s) => [
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
            'totals' => [
                'cash' => round((float) $shifts->sum('cash_sales'), 2),
                'card' => round((float) $shifts->sum('card_sales'), 2),
                'room_charge' => round((float) $shifts->sum('room_charge_sales'), 2),
                'total' => round((float) $shifts->sum('total_sales'), 2),
                'over_short' => round((float) $shifts->sum('over_short'), 2),
            ],
            'currency' => $this->currency(),
        ]);
    }

    /** Guest directory / CRM: per-guest stays, nights, spend and visit history. */
    public function guests(Request $request): Response
    {
        [$from, $to] = $this->range($request);

        $guests = Guest::with(['reservations' => function ($q) {
            $q->where('status', '!=', 'cancelled')
                ->select('id', 'guest_id', 'check_in_date', 'check_out_date', 'total_amount');
        }])->get(['id', 'first_name', 'last_name', 'email', 'phone', 'nationality']);

        $rows = $guests->map(function ($g) {
            $res = $g->reservations;
            $stays = $res->count();
            $nights = (int) $res->sum(fn ($r) => $r->nights);
            $totalSpent = (float) $res->sum('total_amount');
            $checkIns = $res->pluck('check_in_date')->filter();

            return [
                'id' => $g->id,
                'guest' => trim("{$g->first_name} {$g->last_name}") ?: 'Mysafir',
                'email' => $g->email,
                'phone' => $g->phone,
                'nationality' => $g->nationality ?: '—',
                'stays' => $stays,
                'nights' => $nights,
                'total_spent' => round($totalSpent, 2),
                'last_visit' => $checkIns->isNotEmpty() ? $checkIns->max()?->toDateString() : null,
                'first_seen' => $checkIns->isNotEmpty() ? $checkIns->min()?->toDateString() : null,
            ];
        })
            ->filter(fn ($r) => $r['stays'] > 0)
            ->sortByDesc('total_spent')
            ->values();

        return Inertia::render('Reports/Guests', [
            'rows' => $rows,
            'summary' => [
                'total_guests' => $rows->count(),
                'repeat_guests' => $rows->filter(fn ($r) => $r['stays'] >= 2)->count(),
                'total_nights' => (int) $rows->sum('nights'),
                'total_revenue' => round((float) $rows->sum('total_spent'), 2),
            ],
            'currency' => $this->currency(),
        ]);
    }

    /** POS sales (F&B): revenue by menu category + top items for a date range. */
    public function posSales(Request $request): Response
    {
        [$from, $to, $days] = $this->range($request);

        // Completed POS orders in the range (by created_at) — the universe for every figure below.
        $orderIds = PosOrder::where('status', 'completed')
            ->whereBetween('created_at', ["{$from} 00:00:00", "{$to} 23:59:59"])
            ->pluck('id');

        // (A) Sales by category: order_items -> menu_items -> menu_categories.
        $byCategory = DB::table('pos_order_items as oi')
            ->join('menu_items as mi', 'mi.id', '=', 'oi.menu_item_id')
            ->leftJoin('menu_categories as mc', 'mc.id', '=', 'mi.menu_category_id')
            ->whereIn('oi.pos_order_id', $orderIds)
            ->groupBy('mc.name')
            ->select(
                DB::raw("COALESCE(mc.name, 'Pa kategori') as category"),
                DB::raw('SUM(oi.quantity) as qty'),
                DB::raw('SUM(oi.total_price) as revenue')
            )
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($r) => [
                'category' => $r->category,
                'qty' => (int) $r->qty,
                'revenue' => round((float) $r->revenue, 2),
            ]);

        // (B) Top 15 items by revenue.
        $topItems = DB::table('pos_order_items as oi')
            ->join('menu_items as mi', 'mi.id', '=', 'oi.menu_item_id')
            ->leftJoin('menu_categories as mc', 'mc.id', '=', 'mi.menu_category_id')
            ->whereIn('oi.pos_order_id', $orderIds)
            ->groupBy('mi.name', 'mc.name')
            ->select(
                'mi.name as item',
                DB::raw("COALESCE(mc.name, 'Pa kategori') as category"),
                DB::raw('SUM(oi.quantity) as qty'),
                DB::raw('SUM(oi.total_price) as revenue')
            )
            ->orderByDesc('revenue')
            ->limit(15)
            ->get()
            ->map(fn ($r) => [
                'item' => $r->item,
                'category' => $r->category,
                'qty' => (int) $r->qty,
                'revenue' => round((float) $r->revenue, 2),
            ]);

        // (C) Summary KPIs.
        $orderCount = $orderIds->count();
        $totalRevenue = (float) PosOrder::whereIn('id', $orderIds)->sum('total_amount');
        $avgTicket = $orderCount ? $totalRevenue / $orderCount : 0.0;

        return Inertia::render('Reports/PosSales', [
            'filters' => ['from' => $from, 'to' => $to],
            'byCategory' => $byCategory,
            'topItems' => $topItems,
            'summary' => [
                'order_count' => $orderCount,
                'total_revenue' => round($totalRevenue, 2),
                'avg_ticket' => round($avgTicket, 2),
                'days' => $days,
            ],
            'currency' => $this->currency(),
        ]);
    }

    /** Arrivals manifest: every booked arrival in range with guest, room, pax, channel and balance owed. */
    public function arrivalsManifest(Request $request): Response
    {
        [$from, $to] = $this->range($request);

        $arrivals = Reservation::whereBetween('check_in_date', [$from, $to])
            ->whereIn('status', ['confirmed', 'checked_in', 'pending'])
            ->with(['room:id,room_number,room_type_id', 'room.roomType:id,name', 'guest:id,first_name,last_name,phone'])
            ->get([
                'id', 'room_id', 'guest_id', 'status',
                'check_in_date', 'check_out_date', 'total_amount',
                'adults', 'children', 'channel', 'notes',
            ]);

        $ids = $arrivals->pluck('id')->all();

        $folio = FolioItem::whereIn('reservation_id', $ids)
            ->select('reservation_id',
                DB::raw("SUM(CASE WHEN type NOT IN ('discount','room') THEN amount ELSE 0 END) as charges"),
                DB::raw("SUM(CASE WHEN type = 'discount' THEN amount ELSE 0 END) as discounts"))
            ->groupBy('reservation_id')->get()->keyBy('reservation_id');

        $pay = Payment::whereIn('reservation_id', $ids)
            ->select('reservation_id', DB::raw('SUM(amount) as paid'))
            ->groupBy('reservation_id')->get()->keyBy('reservation_id');

        $rows = $arrivals->map(function ($r) use ($folio, $pay) {
            $gross = round((float) $r->total_amount
                + (float) ($folio[$r->id]->charges ?? 0)
                - (float) ($folio[$r->id]->discounts ?? 0), 2);
            $paid = (float) ($pay[$r->id]->paid ?? 0);

            return [
                'id' => $r->id,
                'check_in' => $r->check_in_date?->toDateString(),
                'check_out' => $r->check_out_date?->toDateString(),
                'guest' => trim("{$r->guest?->first_name} {$r->guest?->last_name}") ?: 'Mysafir',
                'phone' => $r->guest?->phone,
                'room' => $r->room?->room_number,
                'room_type' => $r->room?->roomType?->name,
                'status' => $r->status,
                'nights' => (int) $r->nights,
                'pax' => (int) $r->adults + (int) $r->children,
                'adults' => (int) $r->adults,
                'children' => (int) $r->children,
                'channel' => $r->channel ?: 'manual',
                'balance' => round($gross - $paid, 2),
                'notes' => $r->notes,
            ];
        })
            ->sortBy([
                ['check_in', 'asc'],
                ['room', 'asc'],
            ])->values();

        return Inertia::render('Reports/ArrivalsManifest', [
            'filters' => ['from' => $from, 'to' => $to],
            'rows' => $rows,
            'totals' => [
                'count' => $rows->count(),
                'nights' => (int) $rows->sum('nights'),
                'pax' => (int) $rows->sum('pax'),
                'revenue' => round((float) $arrivals->sum('total_amount'), 2),
                'balance' => round((float) $rows->sum('balance'), 2),
            ],
            'currency' => $this->currency(),
        ]);
    }

    /** Departures manifest: stays checking out in range, with balance owed + open POS orders. */
    public function departuresManifest(Request $request): Response
    {
        [$from, $to] = $this->range($request);

        $stays = Reservation::whereBetween('check_out_date', [$from, $to])
            ->whereIn('status', ['checked_in', 'checked_out'])
            ->with(['room:id,room_number', 'guest:id,first_name,last_name,phone'])
            ->get(['id', 'room_id', 'guest_id', 'status', 'check_in_date', 'check_out_date', 'total_amount']);

        $ids = $stays->pluck('id')->all();

        $folio = FolioItem::whereIn('reservation_id', $ids)
            ->select('reservation_id',
                DB::raw("SUM(CASE WHEN type NOT IN ('discount','room') THEN amount ELSE 0 END) as charges"),
                DB::raw("SUM(CASE WHEN type = 'discount' THEN amount ELSE 0 END) as discounts"))
            ->groupBy('reservation_id')->get()->keyBy('reservation_id');

        $pay = Payment::whereIn('reservation_id', $ids)
            ->select('reservation_id', DB::raw('SUM(amount) as paid'))
            ->groupBy('reservation_id')->get()->keyBy('reservation_id');

        $openPos = PosOrder::whereIn('reservation_id', $ids)
            ->where('status', 'open')
            ->select('reservation_id', DB::raw('count(*) as open_count'))
            ->groupBy('reservation_id')->get()->keyBy('reservation_id');

        $rows = $stays->map(function ($r) use ($folio, $pay, $openPos) {
            $gross = round((float) $r->total_amount + (float) ($folio[$r->id]->charges ?? 0) - (float) ($folio[$r->id]->discounts ?? 0), 2);
            $paid = (float) ($pay[$r->id]->paid ?? 0);
            return [
                'id' => $r->id,
                'check_out' => $r->check_out_date?->toDateString(),
                'guest' => trim("{$r->guest?->first_name} {$r->guest?->last_name}") ?: 'Mysafir',
                'phone' => $r->guest?->phone,
                'room' => $r->room?->room_number,
                'status' => $r->status,
                'balance' => round($gross - $paid, 2),
                'open_pos_count' => (int) ($openPos[$r->id]->open_count ?? 0),
            ];
        })->sortBy('check_out')->values();

        return Inertia::render('Reports/DeparturesManifest', [
            'filters' => ['from' => $from, 'to' => $to],
            'rows' => $rows,
            'totals' => [
                'count' => $rows->count(),
                'outstanding' => round((float) $rows->sum('balance'), 2),
            ],
            'currency' => $this->currency(),
        ]);
    }

    /** Tempo & Pickup: on-the-books forward view — bookings, nights, revenue by horizon + next-14-day arrivals. */
    public function pace(Request $request): Response
    {
        $today = now()->startOfDay();
        $activeStatuses = ['confirmed', 'checked_in', 'pending'];

        $horizonDays = [7, 14, 30, 60, 90];
        $horizons = [];

        foreach ($horizonDays as $n) {
            $to = $today->copy()->addDays($n)->toDateString();

            $bucket = Reservation::whereIn('status', $activeStatuses)
                ->whereBetween('check_in_date', [$today->toDateString(), $to])
                ->get(['check_in_date', 'check_out_date', 'total_amount']);

            $bookings = $bucket->count();
            $nights = (int) $bucket->sum(fn ($r) => $r->nights);
            $revenue = round((float) $bucket->sum('total_amount'), 2);

            $horizons[] = [
                'days' => $n,
                'until' => $to,
                'bookings' => $bookings,
                'nights' => $nights,
                'revenue' => $revenue,
                'adr' => $nights ? round($revenue / $nights, 2) : 0,
            ];
        }

        // Next 14 days: distinct rooms occupied per day (overlap) + revenue arriving that day.
        $windowEnd = $today->copy()->addDays(14);

        $overlapping = Reservation::whereIn('status', $activeStatuses)
            ->whereDate('check_in_date', '<', $windowEnd->toDateString())
            ->whereDate('check_out_date', '>', $today->toDateString())
            ->get(['room_id', 'check_in_date', 'check_out_date', 'total_amount']);

        $arrivals = Reservation::whereIn('status', $activeStatuses)
            ->whereBetween('check_in_date', [$today->toDateString(), $windowEnd->copy()->subDay()->toDateString()])
            ->get(['check_in_date', 'total_amount'])
            ->groupBy(fn ($r) => $r->check_in_date->toDateString());

        $next14 = [];
        for ($i = 0; $i < 14; $i++) {
            $day = $today->copy()->addDays($i);
            $dayStr = $day->toDateString();

            $rooms = $overlapping->filter(function ($r) use ($dayStr) {
                return $r->check_in_date->toDateString() <= $dayStr
                    && $r->check_out_date->toDateString() > $dayStr;
            })->pluck('room_id')->unique()->count();

            $arrivingRevenue = round((float) ($arrivals[$dayStr] ?? collect())->sum('total_amount'), 2);

            $next14[] = [
                'date' => $dayStr,
                'rooms' => $rooms,
                'revenue' => $arrivingRevenue,
            ];
        }

        return Inertia::render('Reports/Pace', [
            'horizons' => $horizons,
            'next14' => $next14,
            'currency' => $this->currency(),
        ]);
    }

    /** Anulime & No-Show: cancellation rate + value, plus pending past-arrival no-show candidates (heuristic). */
    public function cancellations(Request $request): Response
    {
        [$from, $to] = $this->range($request);
        $today = now()->toDateString();

        // All reservations whose check-in falls in the range (any status) — denominator for the rate.
        $totalCount = Reservation::whereBetween('check_in_date', [$from, $to])->count();

        // Cancelled reservations within the range.
        $cancelled = Reservation::whereBetween('check_in_date', [$from, $to])
            ->where('status', 'cancelled')
            ->with(['room:id,room_number', 'guest:id,first_name,last_name'])
            ->orderByDesc('check_in_date')
            ->get(['id', 'room_id', 'guest_id', 'channel', 'check_in_date', 'check_out_date', 'total_amount']);

        $cancelledCount = $cancelled->count();
        $cancelledValue = (float) $cancelled->sum('total_amount');

        // No-show candidates (heuristic): still pending although arrival date has already passed.
        $noShows = Reservation::whereBetween('check_in_date', [$from, $to])
            ->where('status', 'pending')
            ->whereDate('check_in_date', '<', $today)
            ->with(['room:id,room_number', 'guest:id,first_name,last_name'])
            ->orderByDesc('check_in_date')
            ->get(['id', 'room_id', 'guest_id', 'channel', 'check_in_date', 'check_out_date', 'total_amount']);

        $mapRow = fn ($r) => [
            'id' => $r->id,
            'guest' => trim("{$r->guest?->first_name} {$r->guest?->last_name}") ?: 'Mysafir',
            'room' => $r->room?->room_number,
            'channel' => $r->channel ?: 'manual',
            'check_in' => $r->check_in_date?->toDateString(),
            'check_out' => $r->check_out_date?->toDateString(),
            'value' => round((float) $r->total_amount, 2),
        ];

        $cancelledRows = $cancelled->map($mapRow)->values();
        $noShowRows = $noShows->map($mapRow)->values();

        return Inertia::render('Reports/Cancellations', [
            'filters' => ['from' => $from, 'to' => $to],
            'summary' => [
                'cancelled_count' => $cancelledCount,
                'cancelled_value' => round($cancelledValue, 2),
                'total_count' => $totalCount,
                'cancellation_rate' => $totalCount ? round($cancelledCount / $totalCount * 100, 1) : 0,
                'no_show_count' => $noShowRows->count(),
                'no_show_value' => round((float) $noShowRows->sum('value'), 2),
            ],
            'cancelled' => $cancelledRows,
            'noShows' => $noShowRows,
            'currency' => $this->currency(),
        ]);
    }

    /** Arkëtime & Cash: money actually COLLECTED in range (payments + completed POS), by method + per day. */
    public function payments(Request $request): Response
    {
        [$from, $to] = $this->range($request);
        $start = "{$from} 00:00:00";
        $end = "{$to} 23:59:59";

        // Source 1: reservation payments, grouped by day + method (cross-DB: DATE()).
        $payRows = Payment::whereBetween('created_at', [$start, $end])
            ->select(
                DB::raw('DATE(created_at) as d'),
                'method',
                DB::raw('SUM(amount) as total')
            )
            ->groupBy(DB::raw('DATE(created_at)'), 'method')
            ->get();

        // Source 2: completed POS orders, grouped by day + payment_method (cash/card/room_charge).
        $posRows = PosOrder::where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->select(
                DB::raw('DATE(created_at) as d'),
                'payment_method',
                DB::raw('SUM(total_amount) as total')
            )
            ->groupBy(DB::raw('DATE(created_at)'), 'payment_method')
            ->get();

        // Build a per-day map: payments_cash, payments_card, pos_cash, pos_card, pos_room_charge.
        $byDay = [];
        $blank = fn () => [
            'payments_cash' => 0.0,
            'payments_card' => 0.0,
            'pos_cash' => 0.0,
            'pos_card' => 0.0,
            'pos_room_charge' => 0.0,
        ];

        foreach ($payRows as $r) {
            $day = (string) $r->d;
            $byDay[$day] ??= $blank();
            if ($r->method === 'cash') {
                $byDay[$day]['payments_cash'] += (float) $r->total;
            } elseif ($r->method === 'card') {
                $byDay[$day]['payments_card'] += (float) $r->total;
            }
        }

        foreach ($posRows as $r) {
            $day = (string) $r->d;
            $byDay[$day] ??= $blank();
            if ($r->payment_method === 'cash') {
                $byDay[$day]['pos_cash'] += (float) $r->total;
            } elseif ($r->payment_method === 'card') {
                $byDay[$day]['pos_card'] += (float) $r->total;
            } elseif ($r->payment_method === 'room_charge') {
                $byDay[$day]['pos_room_charge'] += (float) $r->total;
            }
        }

        ksort($byDay);

        // Per-day rows: date, payments_cash, payments_card, pos_total, total.
        $rows = collect($byDay)->map(function ($d, $day) {
            $posTotal = $d['pos_cash'] + $d['pos_card'] + $d['pos_room_charge'];
            $dayTotal = $d['payments_cash'] + $d['payments_card'] + $posTotal;

            return [
                'date' => $day,
                'payments_cash' => round($d['payments_cash'], 2),
                'payments_card' => round($d['payments_card'], 2),
                'pos_total' => round($posTotal, 2),
                'total' => round($dayTotal, 2),
            ];
        })->values();

        // Collected-by-method summary (combine payments + POS per method).
        $cash = (float) collect($byDay)->sum(fn ($d) => $d['payments_cash'] + $d['pos_cash']);
        $card = (float) collect($byDay)->sum(fn ($d) => $d['payments_card'] + $d['pos_card']);
        $roomCharge = (float) collect($byDay)->sum(fn ($d) => $d['pos_room_charge']);
        $grand = $cash + $card + $roomCharge;

        $byMethod = [
            ['method' => 'cash', 'label' => 'Kesh', 'amount' => round($cash, 2)],
            ['method' => 'card', 'label' => 'Kartë', 'amount' => round($card, 2)],
            ['method' => 'room_charge', 'label' => 'Faturë dhome', 'amount' => round($roomCharge, 2)],
        ];

        return Inertia::render('Reports/Payments', [
            'filters' => ['from' => $from, 'to' => $to],
            'rows' => $rows,
            'byMethod' => $byMethod,
            'totals' => [
                'payments_cash' => round((float) $rows->sum('payments_cash'), 2),
                'payments_card' => round((float) $rows->sum('payments_card'), 2),
                'pos_total' => round((float) $rows->sum('pos_total'), 2),
                'cash' => round($cash, 2),
                'card' => round($card, 2),
                'room_charge' => round($roomCharge, 2),
                'total' => round($grand, 2),
            ],
            'currency' => $this->currency(),
        ]);
    }

    /** Raport TVSH: VAT-inclusive breakdown (gross/net/vat) for room + F&B revenue, with per-month rows. */
    public function vat(Request $request): Response
    {
        [$from, $to, $days] = $this->range($request);

        $rate = (float) Setting::get('financial.tax_rate', 20);
        $divisor = 1 + $rate / 100;

        // Room revenue — by check_in_date (excl cancelled). Fetch dates+amounts, group by month in PHP (cross-DB safe).
        $rooms = Reservation::whereBetween('check_in_date', [$from, $to])
            ->where('status', '!=', 'cancelled')
            ->get(['check_in_date', 'total_amount']);

        // POS revenue — completed orders by created_at.
        $pos = PosOrder::where('status', 'completed')
            ->whereBetween('created_at', ["{$from} 00:00:00", "{$to} 23:59:59"])
            ->get(['created_at', 'total_amount']);

        $monthly = [];
        $add = function (string $month, float $amount) use (&$monthly) {
            if (! isset($monthly[$month])) {
                $monthly[$month] = 0.0;
            }
            $monthly[$month] += $amount;
        };

        foreach ($rooms as $r) {
            $month = substr((string) ($r->check_in_date instanceof \Carbon\CarbonInterface ? $r->check_in_date->toDateString() : $r->check_in_date), 0, 7);
            $add($month, (float) $r->total_amount);
        }
        foreach ($pos as $o) {
            $month = substr((string) ($o->created_at instanceof \Carbon\CarbonInterface ? $o->created_at->toDateString() : $o->created_at), 0, 7);
            $add($month, (float) $o->total_amount);
        }

        $roomRevenue = (float) $rooms->sum('total_amount');
        $posRevenue = (float) $pos->sum('total_amount');
        $gross = $roomRevenue + $posRevenue;
        $vat = $divisor > 0 ? round($gross - ($gross / $divisor), 2) : 0.0;
        $net = round($gross - $vat, 2);

        ksort($monthly);
        $rows = [];
        foreach ($monthly as $month => $g) {
            $g = round($g, 2);
            $v = $divisor > 0 ? round($g - ($g / $divisor), 2) : 0.0;
            $rows[] = [
                'month' => $month,
                'gross' => $g,
                'vat' => $v,
                'net' => round($g - $v, 2),
            ];
        }

        return Inertia::render('Reports/Vat', [
            'filters' => ['from' => $from, 'to' => $to],
            'summary' => [
                'gross' => round($gross, 2),
                'net' => $net,
                'vat' => $vat,
                'rate' => $rate,
                'room_revenue' => round($roomRevenue, 2),
                'pos_revenue' => round($posRevenue, 2),
            ],
            'rows' => $rows,
            'currency' => $this->currency(),
        ]);
    }

    /** from/to (default = current month) + inclusive day count. */
    private function range(Request $request): array
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->endOfMonth()->toDateString());
        $days = Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1;

        return [$from, $to, max(1, (int) $days)];
    }

    private function currency(): string
    {
        return Setting::get('financial.default_currency_symbol', '€');
    }
}
