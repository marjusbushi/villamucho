<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\ChannexClient;
use App\Services\ChannexConfiguration;
use App\Services\ChannexMessageImporter;
use App\Tenancy\TenantContext;
use Illuminate\Console\Command;

/**
 * Backfill existing guest-message threads from Channex into the inbox. The
 * 'message' webhook only delivers NEW messages, so conversations that already
 * existed on the OTA never arrive on their own — run this once after enabling
 * messaging (and re-run any time to heal missed webhooks; it is idempotent).
 *
 * Iterates active tenants itself (like TenantCommandRunner) so a manual run
 * on production is tenant-safe without extra flags. Historical messages are
 * imported as READ by default; pass --mark-unread to badge them.
 */
class ChannexPullMessages extends Command
{
    protected $signature = 'channex:pull-messages {--pages=10 : Max thread pages per tenant (100 threads each)} {--mark-unread : Count backfilled guest messages as unread}';

    protected $description = 'Backfill existing Channex guest-message threads into the PMS inbox';

    public function handle(TenantContext $context): int
    {
        $pages = max(1, (int) $this->option('pages'));
        $markUnread = (bool) $this->option('mark-unread');

        Tenant::query()->active()->orderBy('id')->each(function (Tenant $tenant) use ($context, $pages, $markUnread) {
            $context->run($tenant, function () use ($tenant, $pages, $markUnread) {
                if (! app(ChannexConfiguration::class)->configured()) {
                    $this->line("  {$tenant->name}: Channex not configured — skipped.");

                    return;
                }

                $channex = app(ChannexClient::class);
                $importer = app(ChannexMessageImporter::class);

                $threads = $channex->listMessageThreads(maxPages: $pages);
                $threadCount = 0;
                $messageCount = 0;
                foreach ($threads as $threadObject) {
                    try {
                        $summary = $importer->importThreadFromApi($threadObject, $markUnread);
                        if ($summary['status'] === 'ok') {
                            $threadCount++;
                            $messageCount += $summary['imported'] ?? 0;
                        }
                    } catch (\Throwable $e) {
                        report($e);
                        $this->error('  failed thread '.($threadObject['id'] ?? '?').' — '.$e->getMessage());
                    }
                }

                $this->info("  {$tenant->name}: {$threadCount} threads, {$messageCount} new messages imported.");
            });
        });

        return self::SUCCESS;
    }
}
