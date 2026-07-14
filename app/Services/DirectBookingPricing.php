<?php

namespace App\Services;

use App\Models\RoomType;
use App\Models\Setting;
use Carbon\Carbon;

class DirectBookingPricing
{
    public function enabled(): bool
    {
        return (bool) Setting::get('pricing_programs.direct_discount_enabled', false);
    }

    public function discountPercent(): float
    {
        if (! $this->enabled()) {
            return 0.0;
        }

        return round(min(50.0, max(0.0, (float) Setting::get('pricing_programs.direct_discount_pct', 10))), 2);
    }

    /**
     * Price a public website stay from the canonical Smart Pricing rate and
     * apply the owner-controlled direct-booking benefit on the server.
     *
     * @return array{nights:int,original_total:float,discount_pct:float,discount_amount:float,total:float,original_per_night:float,price_per_night:float}
     */
    public function quote(RoomType $roomType, string|Carbon $checkIn, string|Carbon $checkOut): array
    {
        $canonical = RoomPricing::quote($roomType, $checkIn, $checkOut);
        $original = round((float) $canonical['total'], 2);
        $pct = $this->discountPercent();
        $discount = round($original * $pct / 100, 2);
        $total = round(max(0, $original - $discount), 2);
        $nights = (int) $canonical['nights'];

        return [
            'nights' => $nights,
            'original_total' => $original,
            'discount_pct' => $pct,
            'discount_amount' => $discount,
            'total' => $total,
            'original_per_night' => $nights > 0 ? round($original / $nights, 2) : 0.0,
            'price_per_night' => $nights > 0 ? round($total / $nights, 2) : 0.0,
        ];
    }

    /** @return array{original:float|null,direct:float|null,discount_pct:float} */
    public function fromPrice(?float $canonicalPrice): array
    {
        $pct = $this->discountPercent();
        if ($canonicalPrice === null || $canonicalPrice <= 0) {
            return ['original' => null, 'direct' => null, 'discount_pct' => $pct];
        }

        $original = round($canonicalPrice, 2);

        return [
            'original' => $original,
            'direct' => round($original * (1 - $pct / 100), 2),
            'discount_pct' => $pct,
        ];
    }
}
