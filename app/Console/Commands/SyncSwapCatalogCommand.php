<?php

namespace App\Console\Commands;

use App\Services\Swap\CatalogSyncService;
use Illuminate\Console\Command;

class SyncSwapCatalogCommand extends Command
{
    protected $signature = 'swap:sync-catalog';

    protected $description = 'Synchronize supported blockchains and tokens from active cross-chain swap providers';

    public function handle(CatalogSyncService $catalogSyncService): int
    {
        $this->info('Synchronizing cross-chain swap catalog from active providers...');

        try {
            $stats = $catalogSyncService->syncAllProviders();
            $this->info(sprintf(
                'Catalog sync completed: %d provider(s), %d network(s), %d asset(s) upserted.',
                $stats['providers_synced'],
                $stats['networks_upserted'],
                $stats['assets_upserted']
            ));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Catalog synchronization failed: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}

