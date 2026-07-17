<?php

namespace App\Services;

use App\Models\RoomType;
use App\Models\Setting;

class AiPriceGuardrails
{
    private const DEFAULT_MAX_DEVIATION_PCT = 15.0;

    public static function maxDeviationPct(): float
    {
        return round(min(25, max(1, (float) Setting::get(
            'ai_mcp.price_recommendation_max_deviation_pct',
            self::DEFAULT_MAX_DEVIATION_PCT,
        ))), 1);
    }

    /** @return array{anchor:float,min:float,max:float,max_deviation_pct:float} */
    public static function limits(RoomType $type, array $day): array
    {
        $anchor = (float) ($day['suggested_price'] ?? $day['current_price'] ?? $type->base_price);
        if ($anchor <= 0) {
            $anchor = max(0.01, (float) $type->base_price);
        }

        [$ownerMin, $ownerMax] = $type->priceBounds();
        $base = (float) $type->base_price;
        $hardMin = $ownerMin ?? ($base > 0 ? $base * 0.25 : 0.01);
        $hardMax = $ownerMax ?? ($base > 0 ? $base * 4.0 : $anchor * 4.0);
        $deviation = self::maxDeviationPct();
        $min = max($hardMin, $anchor * (1 - $deviation / 100));
        $max = min($hardMax, $anchor * (1 + $deviation / 100));

        return [
            'anchor' => round($anchor, 2),
            'min' => round($min, 2),
            'max' => round($max, 2),
            'max_deviation_pct' => $deviation,
        ];
    }

    public static function accepts(RoomType $type, array $day, float $price): bool
    {
        $limits = self::limits($type, $day);

        return $price > 0 && $price + 0.005 >= $limits['min'] && $price - 0.005 <= $limits['max'];
    }

    public static function fingerprint(array $days, array $market, string $rulesVersion): string
    {
        $snapshot = collect($days)->map(fn (array $day) => [
            'date' => $day['date'],
            'current_price' => round((float) $day['current_price'], 2),
            'suggested_price' => round((float) $day['suggested_price'], 2),
            'occupancy_pct' => (int) $day['occupancy_pct'],
            'booked' => (int) $day['booked'],
            'total' => (int) $day['total'],
            'factors' => $day['factors'] ?? [],
            'events' => $day['events'] ?? [],
        ])->values()->all();
        ksort($market);

        return hash('sha256', json_encode([
            'rules_version' => $rulesVersion,
            'days' => $snapshot,
            'market' => $market,
        ], JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_UNICODE));
    }
}
