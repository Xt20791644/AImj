<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== 监控模式已启动 ===\n";
echo "等待用户提交新作品...\n\n";

$processedIds = [];
$kling = app(App\Services\KlingService::class);
$tts = app(App\Services\CosyVoiceService::class);
$oss = app(App\Services\OssService::class);

while (true) {
    $work = App\Models\Work::where('status', 'pending')
        ->whereNotIn('id', $processedIds)
        ->latest()
        ->first();

    if ($work) {
        echo "══════════════════════════════════\n";
        echo "📹 检测到新作品 ID: {$work->id} — 《{$work->title}》\n";
        echo "开始处理...\n\n";

        try {
            $job = new App\Jobs\ProcessWorkJob($work->id);
            $job->handle($kling, $tts, $oss);
            
            $work->refresh();
            echo "\n✅ 处理完成！\n";
            echo "视频 CDN 链接:\n";
            echo $work->output_video . "\n\n";

            if ($work->output_video) {
                echo "=== 生成成功！视频已保存 ===\n";
            } else {
                echo "⚠️ 视频链接为空，检查上方日志\n";
            }
        } catch (\Throwable $e) {
            echo "❌ 失败: " . $e->getMessage() . "\n";
            // 退款
            $cost = config('services.credits.cost_per_generation', 50);
            $work->user->rechargeCredits($cost, "退款");
            echo "已退还 {$cost} 积分\n";
        }
        
        $processedIds[] = $work->id;
        echo "\n等待下一个作品...\n\n";
    }

    sleep(2);
}
