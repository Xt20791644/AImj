<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$oss = app(App\Services\OssService::class);

echo "OSS Configured: " . ($oss->isConfigured() ? "YES" : "NO") . "\n";

if ($oss->isConfigured()) {
    // Test upload a small text file
    $result = $oss->putObject('test.txt', 'Hello OSS from AI短剧', 'text/plain');
    echo "Test upload result: {$result}\n";
    $oss->deleteObject('test.txt');
    echo "Test file cleaned up\n";
}
