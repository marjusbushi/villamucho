<?php

namespace App\Services;

use App\Models\RateOverride;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Setting;
use Carbon\Carbon;

/**
 * Occupancy-based price suggestions ("Çmim Inteligjent"). For each upcoming date and room
 * type it computes occupancy (booked of that type / total of that type) and suggests raising
 * or lowering the price off the seasonal/base reference. Suggest-only: nothing changes until
 * the owner accepts a row (which writes a RateOverride). Thresholds are tunable via settings.
 */
class SmartPricing
{
    public static function settings(): array
    {
        return [
            'horizon_days' => (int) Setting::get('pricing.smart.horizon_days', 60),
            'low_threshold' => (float) Setting::get('pricing.smart.low_threshold', 40),
            'low_adj' => (float) Setting::get('pricing.smart.low_adj', -15),
            'high_threshold' => (float) Setting::get('pricing.smart.high_threshold', 70),
            'high_adj' => (float) Setting::get('pricing.smart.high_adj', 15),
            'peak_threshold' => (float) Setting::get('pricing.smart.peak_threshold', 90),
            'peak_adj' => (float) Setting::get('pricing.smart.peak_adj', 30),
            'lastminute_days' => (int) Setting::get('pricing.smart.lastminute_days', 5),
            'lastminute_adj' => (float) Setting::get('pricing.smart.lastminute_adj', -10),
        ];
    }

    /** The percentage adjustment for a given occupancy + lead time. */
    public static function adjustmentFor(float $occupancyPct, int $daysUntil, array $s): float
    {
        $adj = 0.0;

        if ($occupancyPct >= $s['peak_threshold']) {
            $adj = $s['peak_adj'];
        } elseif ($occupancyPct >= $s['high_threshold']) {
            $adj = $s['high_adj'];
        } elseif ($occupancyPct < $s['low_threshold']) {
            $adj = $s['low_adj'];
        }

        // Last-minute & still empty → discount further so the night isn't lost.
        if ($occupancyPct < $s['low_threshold'] && $daysUntil <= $s['lastminute_days']) {
            $adj += $s['lastminute_adj'];
        }

        return $adj;
    }

    /**
     * Actionable suggestions across the horizon.
     *
     * @return array<int,array{date:string,room_type_id:int,room_type_name:string,total:int,booked:int,occupancy_pct:int,current_price:float,suggested_price:float,adjustment_pct:float,has_override:bool,days_until:int}>
     */
    public static function suggestions(?int $days = null): array
    {
        $s = self::settings();
        $days = $days ?? $s['horizon_days'];

        $today = Carbon::today();
        $end = $today->copy()->addDays($days);

        $types = RoomType::all();
        $roomsByType = Room::where('status', '!=', 'maintenance')->get(['id', 'room_type_id'])->groupBy('room_type_id');

        // Reservations overlapping the horizon, by room type.
        $reservations = Reservation::whereNotIn('status', ['cancelled', 'checked_out'])
            ->whereDate('check_out_date', '>', $today->toDateString())
            ->whereDate('check_in_date', '<', $end->toDateString())
            ->with('room:id,room_type_id')
            ->get(['id', 'room_id', 'check_in_date', 'check_out_date']);

        // Existing overrides in the horizon (= the currently applied price for those dates).
        $overrides = RateOverride::whereDate('date', '>=', $today->toDateString())
            ->whereDate('date', '<', $end->toDateString())
            ->get()
            ->keyBy(fn ($o) => $o->room_type_id.'|'.$o->date->toDateString());

        $rows = [];

        foreach ($types as $type) {
            $total = ($roomsByType[$type->id] ?? collect())->count();
            if ($total === 0) {
                continue;
            }

            for ($d = $today->copy()->addDay(); $d->lt($end); $d->addDay()) {
                $dateStr = $d->toDateString();

                $booked = $reservations
                    ->filter(fn ($r) => optional($r->room)->room_type_id === $type->id
                        && $d->betweenIncluded($r->check_in_date, $r->check_out_date->copy()->subDay()))
                    ->pluck('room_id')->unique()->count();

                $occ = (int) round($booked / $total * 100);
                $daysUntil = (int) $today->diffInDays($d);
                $adj = self::adjustmentFor($occ, $daysUntil, $s);

                if ($adj == 0.0) {
                    continue; // occupancy in the neutral band → no suggestion
                }

                $reference = RoomPricing::seasonPrice($type, $d);
                if ($reference <= 0) {
                    continue; // never suggest a price off a zero/unset reference
                }

                $suggested = round($reference * (1 + $adj / 100), 2);
                $override = $overrides->get($type->id.'|'.$dateStr);
                $current = $override ? (float) $override->price : $reference;

                if (abs($suggested - $current) < 0.01) {
                    continue; // already where the suggestion would put it → not actionable
                }

                $rows[] = [
                    'date' => $dateStr,
                    'room_type_id' => $type->id,
                    'room_type_name' => $type->name,
                    'total' => $total,
                    'booked' => $booked,
                    'occupancy_pct' => $occ,
                    'current_price' => round($current, 2),
                    'suggested_price' => $suggested,
                    'adjustment_pct' => $adj,
                    'has_override' => (bool) $override,
                    'days_until' => $daysUntil,
                ];
            }
        }

        return $rows;
    }
}
