<?php

namespace App\Console\Commands;

use App\Support\HabboImaging\HabboImagingSyncService;
use Illuminate\Console\Command;

class HabboImagingSyncCommand extends Command
{
    protected $signature = 'habbo-imaging:sync 
                            {--force : Force re-download all assets}
                            {--batch=25 : Number of assets to process per batch}';
    
    protected $description = 'Sync Habbo figure assets from remote sources';

    public function handle(): int
    {
        $startTime = microtime(true);

        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('Habbo Figure Asset Sync');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $service = app(HabboImagingSyncService::class);

        try {
            $result = $service->sync(
                force: (bool) $this->option('force'),
                batchSize: (int) $this->option('batch')
            );

            $elapsed = round(microtime(true) - $startTime, 2);

            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info('Sync Complete');
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->line("Status: {$result['status']}");
            $this->line("Time: {$elapsed}s");

            if (isset($result['asset_counts'])) {
                $this->line("Extracted: {$result['asset_counts']['extracted']}");
                $this->line("Pending: {$result['asset_counts']['pending']}");
                $this->line("Failed: {$result['asset_counts']['failed']}");
            }

            return $result['status'] === 'failed' ? 1 : 0;
        } catch (\Throwable $e) {
            $this->error('Sync failed: ' . $e->getMessage());

            return 1;
        }
    }
}
