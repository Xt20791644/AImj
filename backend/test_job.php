<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Running job synchronously...\n";
try {
    $job = new App\Jobs\ProcessWorkJob(8);
    $job->handle(app(App\Services\KlingService::class), app(App\Services\CosyVoiceService::class));
    echo "Job completed!\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
