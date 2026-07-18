<?php

namespace App\Services\Reporting;

use App\Models\MaintenanceIssue;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;

final class RoomTypePerformanceService
{
    public function __construct(
        private readonly StayRevenueAllocator $revenueAllocator,
        private readonly SellableInventoryCalculator $inventoryCalculator,
        private readonly KpiCalculator $kpiCalculator,
    ) {}

    /** @return array{period:array{from:string,to:string},kpis:array,rows:array,daily:array} */
    public function summary(ReportingPeriod $period): array
    {
        $types = RoomType::query()
            ->with(['rooms:id,room_type_id,status'])
            ->orderBy('name')
            ->get(['id', 'name']);
        $roomTypeByRoom = $types->flatMap(fn (RoomType $type) => $type->rooms
            ->mapWithKeys(fn (Room $room) => [(string) $room->id => $type->id]));
        $roomIds = $roomTypeByRoom->keys()->map(fn ($id) => (int) $id)->all();

        $reservations = Reservation::query()
            ->whereIn('room_id', $roomIds)
            ->where('status', '!=', 'cancelled')
            ->whereNull('no_show_at')
            ->whereDate('check_in_date', '<=', $period->to->toDateString())
            ->whereDate('check_out_date', '>', $period->from->toDateString())
            ->get(['id', 'room_id', 'check_in_date', 'check_out_date', 'total_amount']);

        $blocks = MaintenanceIssue::query()
            ->whereIn('room_id', $roomIds)
            ->where('room_blocked', true)
            ->where('created_at', '<=', $period->to->endOfDay())
            ->where(function ($query) use ($period) {
                $query->whereNull('resolved_at')
                    ->orWhere('resolved_at', '>', $period->from->startOfDay());
            })
            ->get(['room_id', 'created_at', 'resolved_at'])
            ->map(fn (MaintenanceIssue $issue) => [
                'room_id' => $issue->room_id,
                'starts_at' => $issue->created_at->toDateTimeString(),
                'ends_at' => $issue->resolved_at?->toDateTimeString(),
            ]);
        $blockedRoomIds = $blocks->pluck('room_id')->map(fn ($id) => (string) $id)->all();

        $types->flatMap->rooms
            ->where('status', 'maintenance')
            ->whereNotIn('id', $blockedRoomIds)
            ->each(function (Room $room) use ($blocks, $period) {
                $blocks->push([
                    'room_id' => $room->id,
                    'starts_at' => $period->from->toDateTimeString(),
                    'ends_at' => null,
                ]);
            });

        $rows = $types->map(function (RoomType $type) use ($reservations, $blocks, $period) {
            $typeRoomIds = $type->rooms->pluck('id')->map(fn ($id) => (int) $id);
            $typeReservations = $reservations->whereIn('room_id', $typeRoomIds);
            $revenueByDate = [];
            $occupiedByDate = [];

            foreach ($typeReservations as $reservation) {
                foreach ($this->revenueAllocator->allocate(
                    $reservation->check_in_date,
                    $reservation->check_out_date,
                    $reservation->total_amount,
                    $period,
                ) as $date => $amount) {
                    $revenueByDate[$date] = ($revenueByDate[$date] ?? 0.0) + $amount;
                    $occupiedByDate[$date][(string) $reservation->room_id] = true;
                }
            }

            $inventory = $this->inventoryCalculator->calculate(
                $typeRoomIds->count(),
                $blocks->whereIn('room_id', $typeRoomIds)->all(),
                $period,
            );
            $revenue = round((float) array_sum($revenueByDate), 2);
            $occupied = collect($occupiedByDate)->sum(fn (array $rooms) => count($rooms));
            $sellable = $inventory['sellable_room_nights'];
            $kpis = $this->kpiCalculator->calculate($revenue, $revenue, $occupied, $sellable);

            return [
                'type_id' => $type->id,
                'type' => $type->name,
                'rooms_count' => $typeRoomIds->count(),
                'occupied_room_nights' => $occupied,
                'sellable_room_nights' => $sellable,
                'blocked_room_nights' => ($typeRoomIds->count() * $period->days()) - $sellable,
                'room_revenue' => $kpis['room_revenue'],
                'occupancy' => $kpis['occupancy'],
                'adr' => $kpis['adr'],
                'revpar' => $kpis['revpar'],
                'daily_revenue' => $revenueByDate,
                'daily_occupied' => collect($occupiedByDate)->map(fn (array $rooms) => count($rooms))->all(),
                'daily_inventory' => $inventory['by_date'],
            ];
        })->values();

        $daily = [];
        for ($date = $period->from; $date->lessThanOrEqualTo($period->to); $date = $date->addDay()) {
            $key = $date->toDateString();
            $revenue = (float) $rows->sum(fn (array $row) => $row['daily_revenue'][$key] ?? 0);
            $occupied = (int) $rows->sum(fn (array $row) => $row['daily_occupied'][$key] ?? 0);
            $sellable = (int) $rows->sum(fn (array $row) => $row['daily_inventory'][$key]['sellable'] ?? 0);
            $dayKpis = $this->kpiCalculator->calculate($revenue, $revenue, $occupied, $sellable);
            $daily[$key] = [
                'room_revenue' => $dayKpis['room_revenue'],
                'occupied_room_nights' => $occupied,
                'sellable_room_nights' => $sellable,
                'occupancy' => $dayKpis['occupancy'],
                'adr' => $dayKpis['adr'],
                'revpar' => $dayKpis['revpar'],
            ];
        }

        $roomRevenue = (float) $rows->sum('room_revenue');
        $occupied = (int) $rows->sum('occupied_room_nights');
        $sellable = (int) $rows->sum('sellable_room_nights');

        return [
            'period' => $period->toArray(),
            'kpis' => $this->kpiCalculator->calculate($roomRevenue, $roomRevenue, $occupied, $sellable),
            'rows' => $rows->map(fn (array $row) => collect($row)->except(['daily_revenue', 'daily_occupied', 'daily_inventory'])->all())->all(),
            'daily' => $daily,
        ];
    }

    /** @return array{current:array,previous_period:array,previous_year:array,changes:array} */
    public function withComparisons(ReportingPeriod $period): array
    {
        $current = $this->summary($period);
        $previousPeriod = $this->summary($period->previousPeriod());
        $previousYear = $this->summary($period->previousYear());
        $changes = [];

        foreach (['room_revenue', 'occupancy', 'adr', 'revpar'] as $key) {
            $changes[$key] = $this->kpiCalculator->change(
                (float) $current['kpis'][$key],
                (float) $previousPeriod['kpis'][$key],
            );
        }

        return [
            'current' => $current,
            'previous_period' => $previousPeriod,
            'previous_year' => $previousYear,
            'changes' => $changes,
        ];
    }
}
