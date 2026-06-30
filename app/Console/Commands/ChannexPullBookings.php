<?php

namespace App\Console\Commands;

use App\Services\ChannexBookingImporter;
use App\Services\ChannexClient;
use Illuminate\Console\Command;

/**
 * Pull every unacknowledged Channex booking revision from the feed, import it
 * into the PMS, and acknowledge it. The catch-up / manual counterpart to the
 * real-time webhook (run it to backfill anything a missed webhook left behind).
 */
class ChannexPullBookings extends Command
{
    protected $signature = 'channex:pull-bookings';

    protected $description = 'Pull + import unacknowledged Channex booking revisions (OTA -> PMS)';

    public function handle(ChannexClient $channex, ChannexBookingImporter $importer): int
    {
        if (! $channex->configured()) {
            $this->error('CHANNEX_API_KEY is not set (.env).');

            return self::FAILURE;
        }

        $feed = $channex->getBookingFeed();
        if ($feed === []) {
            $this->info('No unacknowledged bookings.');

            return self::SUCCESS;
        }

        $created = 0;
        $updated = 0;
        $cancelled = 0;
        foreach ($feed as $item) {
            try {
                $s = $importer->importRevision($item);
                $channex->ackBookingRevision($item['id']);
                $created += $s['created'];
                $updated += $s['updated'];
                $cancelled += $s['cancelled'];
                $flag = $s['flagged'] ? '  [FLAG: '.implode('; ', $s['flagged']).']' : '';
                $this->line(sprintf('  %-12s %-18s +%d ~%d x%d%s', $s['channel'], $s['ref'], $s['created'], $s['updated'], $s['cancelled'], $flag));
            } catch (\Throwable $e) {
                $this->error('  failed '.($item['id'] ?? '?').' — '.$e->getMessage());
            }
        }
        $this->info("Done: {$created} new, {$updated} updated, {$cancelled} cancelled.");

        return self::SUCCESS;
    }
}
