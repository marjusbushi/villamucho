<?php

namespace App\Services;

use App\Models\RateOverride;
use App\Models\RoomType;
use App\Models\Season;
use Carbon\Carbon;

class RoomPricing
{
    /**
     * Price a stay night-by-night, applying the highest-priority season that
     * covers each night and has a rate for this room type; otherwise the
     * room type's base_price. Correctly prices stays that cross seasons.
     *
     * @return array{nights:int,total:float,base:float,breakdown:array<int,array{date:string,price:float}>}
     */
    public static function quote(RoomType $roomType, string|Carbon $checkIn, string|Carbon $checkOut): array
    {
        return self::quoteMany([$roomType], $checkIn, $checkOut)[$roomType->id];
    }

    /**
     * Price several room types from the same canonical rate table in one batch.
     * Both the direct website and OTA sync ultimately use this resolver, so a
     * date override can never mean one price on the website and another on an OTA.
     *
     * @param  iterable<int, RoomType>  $roomTypes
     * @return array<int, array{nights:int,total:float,base:float,breakdown:array<int,array{date:string,price:float}>}>
     */
    public static function quoteMany(iterable $roomTypes, string|Carbon $checkIn, string|Carbon $checkOut): array
    {
        $types = collect($roomTypes)->filter(fn ($type) => $type instanceof RoomType)->keyBy('id');
        if ($types->isEmpty()) {
            return [];
        }

        $start = $checkIn instanceof Carbon ? $checkIn->copy() : Carbon::parse($checkIn);
        $end = $checkOut instanceof Carbon ? $checkOut->copy() : Carbon::parse($checkOut);

        // Guard: a non-positive range (check-out on/before check-in) is not a stay.
        // Return a clearly-zero quote instead of silently pricing money to 0 inside the loop.
        if ($end->lte($start)) {
            return $types->mapWithKeys(fn (RoomType $type) => [
                $type->id => [
                    'nights' => 0,
                    'total' => 0.0,
                    'base' => (float) $type->base_price,
                    'breakdown' => [],
                ],
            ])->all();
        }

        $typeIds = $types->keys()->all();
        $lastNight = $end->copy()->subDay();

        // ALL seasons, highest priority first (ties broken by id). We KEEP seasons that
        // have no rate for this type: the highest-priority season COVERING a night governs
        // that night, and if it carries no per-type rate the night falls back to base_price
        // — i.e. an empty matrix cell means "use the base price", exactly as the Cmimet UI
        // promises ("Bosh = perdoret cmimi bazё"). (Do NOT pre-filter by rate, or a
        // lower-priority overlapping season would silently win the night.)
        $seasons = Season::query()
            ->whereDate('start_date', '<=', $lastNight->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->orderByDesc('priority')
            ->orderBy('id')
            ->with(['rates' => fn ($query) => $query->whereIn('room_type_id', $typeIds)])
            ->get();
        $ratesBySeason = $seasons->mapWithKeys(fn (Season $season) => [
            $season->id => $season->rates->keyBy('room_type_id'),
        ]);

        // Per-DATE overrides (e.g. accepted Smart Pricing suggestions) win over any season.
        // whereDate compares only the date part (the column may carry a 00:00:00 time).
        $overridesByType = RateOverride::whereIn('room_type_id', $typeIds)
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<', $end->toDateString())
            ->get()
            ->groupBy('room_type_id')
            ->map(fn ($rows) => $rows->keyBy(fn ($override) => $override->date->toDateString()));

        $quotes = [];
        foreach ($types as $type) {
            $base = (float) $type->base_price;
            $overrides = $overridesByType->get($type->id, collect());
            $total = 0.0;
            $breakdown = [];

            for ($date = $start->copy(); $date->lt($end); $date->addDay()) {
                $dateStr = $date->toDateString();

                if (isset($overrides[$dateStr])) {
                    $price = (float) $overrides[$dateStr]->price; // date override beats season/base
                } else {
                    $price = $base;
                    foreach ($seasons as $season) { // already ordered by priority desc
                        if ($date->betweenIncluded($season->start_date, $season->end_date)) {
                            // First (highest-priority) covering season wins; no rate → base price.
                            $rate = $ratesBySeason->get($season->id)?->get($type->id)?->price;
                            $price = $rate !== null ? (float) $rate : $base;
                            break;
                        }
                    }
                }

                $total += $price;
                $breakdown[] = ['date' => $dateStr, 'price' => round($price, 2)];
            }

            $quotes[$type->id] = [
                'nights' => (int) $start->diffInDays($end),
                'total' => round($total, 2),
                'base' => $base,
                'breakdown' => $breakdown,
            ];
        }

        return $quotes;
    }

    public static function total(RoomType $roomType, string|Carbon $checkIn, string|Carbon $checkOut): float
    {
        return self::quote($roomType, $checkIn, $checkOut)['total'];
    }

    /**
     * The seasonal-or-base price for ONE date, IGNORING any per-date override — the reference
     * the Smart Pricing engine applies its occupancy adjustment to. Mirrors quote()'s rule:
     * the highest-priority season covering the date governs (no rate cell → base price).
     */
    public static function seasonPrice(RoomType $roomType, string|Carbon $date): float
    {
        $d = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);
        $base = (float) $roomType->base_price;

        $season = Season::query()
            ->whereDate('start_date', '<=', $d->toDateString())
            ->whereDate('end_date', '>=', $d->toDateString())
            ->orderByDesc('priority')
            ->orderBy('id')
            ->with(['rates' => fn ($q) => $q->where('room_type_id', $roomType->id)])
            ->first();

        if ($season) {
            $rate = optional($season->rates->first())->price;

            return $rate !== null ? (float) $rate : $base;
        }

        return $base;
    }
}
