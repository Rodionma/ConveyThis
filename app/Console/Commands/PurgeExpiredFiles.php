<?php

namespace App\Console\Commands;

use App\Services\FileStorageService;
use Illuminate\Console\Command;

class PurgeExpiredFiles extends Command
{
    protected $signature = 'files:purge-expired';

    protected $description = 'Delete uploaded files whose retention period has expired and notify via RabbitMQ';

    public function handle(FileStorageService $service): int
    {
        $count = $service->purgeExpired();

        $this->info("Deleted {$count} expired file(s).");

        return self::SUCCESS;
    }
}
