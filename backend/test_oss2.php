<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$oss = app(App\Services\OssService::class);
echo "Host: " . (new ReflectionClass($oss))->getMethod('getHost')->invoke($oss) . "\n";
echo "Testing upload...\n";
$r = $oss->putObject('test.txt', 'OK ' . date('Y-m-d H:i:s'), 'text/plain');
echo "Result: {$r}\n";
$oss->deleteObject('test.txt');
echo "Cleanup done\n";
