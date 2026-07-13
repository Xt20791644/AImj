<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$k = app(App\Services\KlingService::class);
try {
    $r = $k->getVideoResult('905596188433997844');
    echo json_encode($r, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) . "\n";
    
    // If succeeded, update the work
    if (($r['task_status'] ?? '') === 'succeed') {
        $videoUrl = $r['task_result']['videos'][0]['url'] ?? null;
        if ($videoUrl) {
            // Upload to OSS
            $oss = app(App\Services\OssService::class);
            $ossUrl = $oss->isConfigured() ? $oss->uploadFromUrl($videoUrl, 'works/16/output.mp4') : $videoUrl;
            
            App\Models\Work::where('id', 16)->update([
                'output_video' => $ossUrl ?: $videoUrl,
                'status_text' => '创作完成',
            ]);
            echo "\n✅ Updated work 16 with video URL: " . ($ossUrl ?: $videoUrl) . "\n";
        }
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
