<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$oss = app(App\Services\OssService::class);
echo "OSS: " . ($oss->isConfigured() ? 'YES' : 'NO') . "\n";

// List of test files to delete
$files = ['ref/6a55f4739fb77.mp4', 'ref/6a55fb92dfe92.mp4', 'ref/6a55fb9d3e2ab.mp4', 'test.txt', 'test/upload-test-20260713-190338.txt'];
foreach ($files as $f) {
    try {
        $oss->deleteObject($f);
        echo "Deleted: {$f}\n";
    } catch (Exception $e) {
        echo "Skip {$f}: " . $e->getMessage() . "\n";
    }
}
echo "Cleanup done\n";
