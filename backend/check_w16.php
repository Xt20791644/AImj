<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$w = App\Models\Work::find(16);
if (!$w) { echo "Work 16 not found\n"; exit; }

echo "ID: {$w->id}\nTitle: {$w->title}\nStatus: {$w->status}\nProgress: {$w->progress}\n";
echo "Video: " . ($w->output_video ?: 'NULL') . "\n";
echo "Cover: " . ($w->output_cover ?: 'NULL') . "\n\n";

echo "Timelines:\n";
foreach ($w->timelines as $t) {
    echo "  {$t->step}: {$t->status} - {$t->message}\n";
}

echo "\nMeta video_results:\n";
$meta = $w->meta ?? [];
if (!empty($meta['video_results'])) {
    foreach ($meta['video_results'] as $vr) echo "  {$vr}\n";
} else echo "  (empty)\n";
