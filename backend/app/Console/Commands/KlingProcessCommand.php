<?php

namespace App\Console\Commands;

use App\Jobs\ProcessWorkJob;
use Illuminate\Console\Command;

class KlingProcessCommand extends Command
{
    protected $signature = 'kling:process {workId}';
    protected $description = 'Process a Kling work job synchronously (background)';

    public function handle()
    {
        $workId = (int) $this->argument('workId');
        $this->info("Processing work ID: {$workId}");

        try {
            $job = new ProcessWorkJob($workId);
            $job->handle(
                app(\App\Services\KlingService::class),
                app(\App\Services\CosyVoiceService::class),
                app(\App\Services\OssService::class),
                app(\App\Services\StoryPipelineService::class)
            );
            $this->info("Work {$workId} completed successfully");
        } catch (\Throwable $e) {
            $this->error("Work {$workId} failed: " . $e->getMessage());
        }
    }
}
