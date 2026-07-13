<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Running job synchronously...\n";
try {
    $work = \App\Models\Work::latest()->first();
$id = $work->id;
echo "Running job for work ID: {$id}\n";
$job = new App\Jobs\ProcessWorkJob($id);
    $job->handle(app(App\Services\KlingService::class), app(App\Services\CosyVoiceService::class));
    echo "Job completed!\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
