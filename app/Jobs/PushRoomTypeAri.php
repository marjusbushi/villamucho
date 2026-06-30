<?php

namespace App\Jobs;

use App\Models\ChannelMapping;
use App\Models\RoomType;
use App\Services\ChannelSync;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Push one room type's availability + rates to Channex, off the request cycle.
 * Dispatched in real time when a reservation or a price changes. Idempotent —
 * it re-sends the current PMS truth, so duplicate dispatches are harmless.
 */
class PushRoomTypeAri implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public int $roomTypeId) {}

    public function handle(ChannelSync $sync): void
    {
        $roomType = RoomType::find($this->roomTypeId);
        if ($roomType) {
            // pushRoomType throws on a rejected push -> the queue retries (tries=3);
            // a no-op skip (unconfigured/unmapped) returns false without throwing.
            $sync->pushRoomType($roomType);
        }
    }

    /** All retries exhausted — surface it (don't let a failed sync stay buried in a log row). */
    public function failed(\Throwable $e): void
    {
        report($e);
    }

    /**
     * Queue a push for every Channex-mapped room type (no-op if Channex is not
     * configured). Returns how many were queued. Shared by the pricing save, the
     * Sync-now button, and the channex:push-ari command.
     */
    public static function dispatchAllMapped(): int
    {
        if (! config('services.channex.api_key')) {
            return 0;
        }

        $ids = ChannelMapping::where('channel', 'channex')->pluck('room_type_id');
        foreach ($ids as $id) {
            self::dispatch($id);
        }

        return $ids->count();
    }
}

