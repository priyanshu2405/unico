<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PointsService;

class ProcessRewardPoints extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'points:process';

    /**
     * The console command description.
     */
    protected $description = 'Process scheduled points release, expiry, and 30d/7d expiry notifications';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting reward points processing...');

        try {
            PointsService::processScheduledPoints();
            $this->info('Reward points release, expiry, and reminders processed successfully.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to process reward points: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
