<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$work = App\Models\Work::latest()->first();
if ($work) {
    echo "ID: {$work->id}\nTitle: {$work->title}\nStatus: {$work->status}\nOutput: {$work->output_video}\n";
    echo "Meta: " . json_encode($work->meta, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) . "\n";
    // Check timelines
    foreach ($work->timelines as $t) {
        echo "  {$t->step}: {$t->status} - {$t->message}\n";
    }
} else {
    echo "No works found\n";
}
