<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$oss = app(App\Services\OssService::class);

echo "Bucket: " . config('services.oss.bucket') . "\n";
echo "Endpoint: " . config('services.oss.endpoint') . "\n";
echo "Configured: " . ($oss->isConfigured() ? "YES" : "NO") . "\n\n";

// Upload test file
$content = "OSS Test - " . date('Y-m-d H:i:s');
$path = 'test/upload-test-' . date('Ymd-His') . '.txt';

echo "Uploading to: {$path}\n";
$result = $oss->putObject($path, $content, 'text/plain');
echo "Result URL: {$result}\n\n";

if ($result && str_contains($result, 'oss-cn-beijing')) {
    echo "✅ OSS 上传成功！\n";
    echo "文件路径: {$path}\n";
    echo "访问URL: {$result}\n";
} else {
    echo "❌ 上传失败\n";
}
