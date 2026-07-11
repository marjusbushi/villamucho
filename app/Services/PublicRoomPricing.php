<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class PublicRoomPricing
{
    public const HORIZON_DAYS = 60;

    /**
     * Lowest positive effective OTA/direct rate on a night that still has at
     * least one sellable room. Pricing is resolved by RoomPricing, the same
     * source used by ChannelSync; availability is loaded in two batch queries.
     *
     * @param  Collection<int, RoomType>  $roomTypes
     * @return array<int, float|null>
     */
    public function fromPrices(
        Collection $roomTypes,
        ?CarbonInterface $from = null,
        int $days = self::HORIZON_DAYS,
    ): array {
        $types = $roomTypes->filter(fn ($type) => $type instanceof RoomType)->keyBy('id');
        if ($types->isEmpty()) {
            return [];
        }

        $days = max(1, min($days, 366));
        $start = CarbonImmutable::parse($from ?? today())->startOfDay();
        $end = $start->addDays($days); // half-open; last advertised night is end - 1 day
        $lastNight = $end->subDay();

        $rooms = Room::query()
            ->whereIn('room_type_id', $types->keys())
            ->where('status', '!=', 'maintenance')
            ->get(['id', 'room_type_id']);
        $roomsByType = $rooms->groupBy('room_type_id');
        $typeByRoom = $rooms->pluck('room_type_id', 'id');

        $reservations = $rooms->isEmpty()
            ? collect()
            : Reservation::query()
                ->whereIn('room_id', $rooms->pluck('id'))
                ->whereNotIn('status', ['cancelled', 'checked_out'])
                ->whereDate('check_in_date', '<=', $lastNight->toDateString())
                ->whereDate('check_out_date', '>', $start->toDateString())
                ->get(['room_id', 'check_in_date', 'check_out_date'])
                ->map(fn (Reservation $reservation) => [
                    'room_id' => $reservation->room_id,
                    'room_type_id' => $typeByRoom->get($reservation->room_id),
                    'check_in' => $reservation->check_in_date->toDateString(),
                    'check_out' => $reservation->check_out_date->toDateString(),
                ]);
        $staysByType = $reservations->groupBy('room_type_id');
        $quotes = RoomPricing::quoteMany($types->values(), $start->toDateString(), $end->toDateString());

        $result = [];
        foreach ($types as $type) {
            $sellableRooms = $roomsByType->get($type->id, collect());
            if ($sellableRooms->isEmpty()) {
                $result[$type->id] = null;

                continue;
            }

            $stays = $staysByType->get($type->id, collect());
            $best = null;
            foreach ($quotes[$type->id]['breakdown'] ?? [] as $night) {
                $date = $night['date'];
                $occupiedRooms = $stays
                    ->filter(fn (array $stay) => $date >= $stay['check_in'] && $date < $stay['check_out'])
                    ->pluck('room_id')
                    ->unique()
                    ->count();

                if ($occupiedRooms >= $sellableRooms->count()) {
                    continue;
                }

                $price = (float) $night['price'];
                if ($price > 0 && ($best === null || $price < $best)) {
                    $best = $price;
                }
            }

            $result[$type->id] = $best !== null ? round($best, 2) : null;
        }

        return $result;
    }
}
