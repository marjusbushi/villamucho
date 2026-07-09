<?php

namespace App\Console\Commands;

use App\Models\Room;
use App\Models\RoomType;
use App\Services\ChannexClient;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Create a Channex room type + per_room rate plan for each PMS room type that
 * does not have one yet, so a FRESH property (e.g. the new production account)
 * has the structure that channex:link-rooms needs to map against.
 *
 * IDEMPOTENT + re-runnable: it matches existing Channex room types by
 * (case-insensitive) title and skips creating them, and skips a rate plan when
 * the room type already has one — so a re-run after a partial failure is a safe
 * no-op for what already exists. It does NOT write channel_mappings (that stays
 * channex:link-rooms' job) and does NOT push ARI.
 *
 *   php artisan channex:bootstrap-rooms --dry   # preview, no writes
 *   php artisan channex:bootstrap-rooms         # create what's missing
 */
class ChannexBootstrapRooms extends Command
{
    protected $signature = 'channex:bootstrap-rooms {--dry : Show what would be created without creating}';

    protected $description = 'Create a Channex room type + per_room rate plan for each PMS room type that lacks one (idempotent)';

    public function handle(ChannexClient $channex): int
    {
        if (! $channex->configured()) {
            $this->error('CHANNEX_API_KEY is not set (.env).');

            return self::FAILURE;
        }

        try {
            $existingRoomTypes = collect($channex->getRoomTypes());
            $ratePlans = collect($channex->getRatePlans());
        } catch (\Throwable $e) {
            // A bad key / lost access surfaces (getList throws) instead of looking
            // like an empty account — never create blindly against the wrong place.
            report($e);
            $this->error('Could not read from Channex — check the API key / connection (see logs).');

            return self::FAILURE;
        }

        // Existing Channex room types keyed by normalized title, and the set of
        // room-type ids that already own at least one rate plan.
        $existingByTitle = $existingRoomTypes->keyBy(fn ($rt) => Str::lower(trim($rt['attributes']['title'] ?? '')));
        $roomTypeIdsWithRatePlan = $ratePlans
            ->pluck('relationships.room_type.data.id')
            ->filter()
            ->flip();

        $dry = (bool) $this->option('dry');
        $rtCreated = 0;
        $rpCreated = 0;
        $rtExisting = 0;

        foreach (RoomType::orderBy('id')->get() as $roomType) {
            $title = trim($roomType->name);
            $count = Room::where('room_type_id', $roomType->id)->count();
            $occ = max(1, (int) $roomType->max_occupancy);

            $match = $existingByTitle->get(Str::lower($title));
            $channexRoomTypeId = $match['id'] ?? null;

            if ($channexRoomTypeId) {
                $this->line(sprintf('  = exists      %-30s %s', $title, $channexRoomTypeId));
                $rtExisting++;
            } else {
                $this->line(sprintf('  %s %-30s (rooms=%d, occ=%d)', $dry ? '+ would create' : '+ creating   ', $title, $count, $occ));
                if (! $dry) {
                    $channexRoomTypeId = $channex->createRoomType($title, $count, $occ);
                    if (! $channexRoomTypeId) {
                        $this->error("    FAILED to create room type \"{$title}\" (see channel_sync_logs)");

                        continue;
                    }
                    $rtCreated++;
                }
            }

            // Ensure a per_room rate plan exists for this room type. A room type we
            // just created (or that has none) needs one; an existing one is skipped.
            $hasRatePlan = $channexRoomTypeId && $roomTypeIdsWithRatePlan->has($channexRoomTypeId);
            if ($hasRatePlan) {
                continue;
            }
            if ($dry) {
                $this->line(sprintf('      + would create rate plan (occ=%d)', $occ));

                continue;
            }
            if ($channexRoomTypeId) {
                $rpId = $channex->createRatePlan($channexRoomTypeId, $occ);
                if ($rpId) {
                    $rpCreated++;
                } else {
                    $this->error("    FAILED to create rate plan for \"{$title}\" (see channel_sync_logs)");
                }
            }
        }

        $this->info(($dry ? '[dry] ' : '')."Room types created: {$rtCreated}, rate plans created: {$rpCreated}, already existed: {$rtExisting}.");
        $this->line('Next: php artisan channex:link-rooms');

        return self::SUCCESS;
    }
}
