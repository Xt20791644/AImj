<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$works = App\Models\Work::orderBy('id')->get();
$kling = app(App\Services\KlingService::class);
$oss = app(App\Services\OssService::class);

echo "=== 所有作品视频地址 ===\n\n";
$total = 0;
$success = 0;

foreach ($works as $w) {
    $total++;
    echo "────────────────────────────────\n";
    echo "ID: {$w->id} | 《{$w->title}》 | 状态: {$w->status}\n";
    
    $meta = $w->meta ?? [];
    $videoResults = $meta['video_results'] ?? [];
    
    if ($w->output_video) {
        $success++;
        echo "✅ OSS: {$w->output_video}\n";
    } elseif (!empty($videoResults)) {
        echo "🔗 可灵CDN: {$videoResults[0]}\n";
        $success++;
    } else {
        // Try to find task ID from meta
        $taskIds = [];
        if (!empty($meta['image_results'])) {
            foreach ((array)$meta['image_results'] as $ir) {
                if (is_array($ir) && !empty($ir['task_id'])) $taskIds[] = $ir['task_id'];
            }
        }
        
        // Check timelines for video task messages
        $videoTimeline = $w->timelines->firstWhere('step', 'video');
        if ($videoTimeline && str_contains($videoTimeline->message ?? '', '905')) {
            preg_match('/\d{15,}/', $videoTimeline->message, $matches);
            if (!empty($matches[0])) $taskIds[] = $matches[0];
        }

        if (!empty($taskIds)) {
            foreach (array_unique($taskIds) as $tid) {
                echo "🔍 尝试恢复任务: {$tid} ... ";
                try {
                    $r = $kling->getVideoResult($tid);
                    if (($r['task_status'] ?? '') === 'succeed') {
                        $url = $r['task_result']['videos'][0]['url'] ?? null;
                        if ($url) {
                            $ossUrl = $oss->isConfigured() ? $oss->uploadFromUrl($url, "works/{$w->id}/output.mp4") : $url;
                            $w->update(['output_video' => $ossUrl ?: $url, 'status_text' => '创作完成']);
                            echo "✅ 已恢复 → OSS: " . ($ossUrl ?: $url) . "\n";
                            $success++;
                            continue 2;
                        }
                    }
                    echo "状态: {$r['task_status']}\n";
                } catch (Exception $e) {
                    echo "失败: {$e->getMessage()}\n";
                }
            }
        }
        echo "❌ 无视频\n";
    }

    // Show timelines summary
    $videoTL = $w->timelines->firstWhere('step', 'video');
    if ($videoTL) {
        echo "   视频步骤: {$videoTL->status} - {$videoTL->message}\n";
    }
}

echo "\n=== 总计: {$total} 个作品, {$success} 个有视频 ===\n";
