<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Work;
use App\Services\KlingService;
use App\Services\OssService;
use App\Services\PromptOptimizerService;

// 确保 works 表存在
try { \Illuminate\Support\Facades\Schema::create('works', function($t) {
    $t->id(); $t->foreignId('user_id')->constrained('users'); $t->string('title'); $t->text('content');
    $t->string('style')->default('realistic'); $t->string('status')->default('pending');
    $t->integer('progress')->default(0); $t->text('status_text')->nullable();
    $t->text('output_video')->nullable(); $t->text('output_cover')->nullable();
    $t->integer('duration')->default(0); $t->boolean('visible')->default(true); $t->json('meta')->nullable(); $t->timestamps();
}); } catch(Exception $e) {}

$kling = app(KlingService::class); $oss = app(OssService::class); $optimizer = app(PromptOptimizerService::class);

echo "=== 监控模式 ===\n等待新作品...\n\n";
$seen = [];
while(true) {
    $work = Work::whereNotIn('id', $seen ?: [0])->where('status', 'pending')->latest()->first();
    if ($work && !in_array($work->id, $seen)) {
        $seen[] = $work->id;
        echo "══════════════════════════════════\n📹 ID:{$work->id} 《{$work->title}》\n";
        $meta = $work->meta ?? []; $config = $meta['kling_config'] ?? [];
        $prompt = $optimizer->optimize($work->content, ['title'=>$work->title,'style'=>$work->style]);
        echo "优化提示词: ".mb_substr($prompt,0,120)."...\n";

        try {
            $refVideo = $config['ref_video'] ?? '';
            $vc = array_merge($config,['video_sound'=>'on','video_mode'=>'pro']);
            $videoPrompt = $optimizer->optimizeVideoPrompt($work->content, $config);

            if ($refVideo) {
                // ========== 爆款复刻：跳过生图，直接用参考视频 ==========
                $work->update(['progress' => 10, 'status_text' => '正在生成视频…']);
                echo "🎥 生视频中...\n  📹 omni-video（参考视频）\n";
                $vr = $kling->omniVideo($refVideo, $videoPrompt, $vc);
                $videoType = 'omni'; $imgUrl = null;
                $vtid = $vr['task_id']??''; echo "  任务:{$vtid}\n"; $videoUrl = null;
                if($vtid) for($i=0;$i<60;$i++){sleep(5);
                    $s = $kling->getOmniVideoResult($vtid);
                    $work->update(['progress'=>10+intval($i/60*80),'status_text'=>"视频生成中（{$s['task_status']}）"]);
                    echo"  轮询{$i}:{$s['task_status']}\n";
                    if($s['task_status']==='succeed'){$videoUrl=$s['task_result']['videos'][0]['url']??null;break;}
                    if($s['task_status']==='failed')break;
                }
            } else {
                // ========== 普通流程：先生图再生视频 ==========
                echo "🎨 生图中...\n";
                $work->update(['progress' => 10, 'status_text' => 'AI正在绘制首帧画面…']);
                $r = $kling->generateImage($prompt, $config); $tid = $r['task_id']??'';
                echo "  任务:{$tid}\n"; $imgUrl = null;
                if($tid) for($i=0;$i<20;$i++){sleep(5);$s=$kling->getImageResult($tid);$work->update(['progress'=>10+intval($i/20*25),'status_text'=>"绘画中（{$s['task_status']}）"]);echo"  轮询{$i}:{$s['task_status']}\n";if($s['task_status']==='succeed'){$imgUrl=$s['task_result']['images'][0]['url']??($s['task_result']['images'][0]['url_1']??null);break;}if($s['task_status']==='failed')break;}
                if($imgUrl) echo "  ✅ 图片: ".mb_substr($imgUrl,0,80)."\n";

                echo "🎥 生视频中...\n";
                $work->update(['progress' => 35, 'status_text' => '正在生成视频…']);
                $videoType = 'text2video';
                if ($imgUrl) {
                    $vr = $kling->imageToVideo($imgUrl, $videoPrompt, $vc);
                    $videoType = 'image2video';
                } else {
                    $vr = $kling->textToVideo($videoPrompt, $vc);
                }
                $vtid = $vr['task_id']??''; echo "  任务:{$vtid}\n"; $videoUrl = null;
                if($vtid) for($i=0;$i<60;$i++){sleep(5);
                    $s = match($videoType) {
                        'text2video' => $kling->getTextVideoResult($vtid),
                        default => $kling->getVideoResult($vtid),
                    };
                    $work->update(['progress'=>35+intval($i/60*55),'status_text'=>"视频生成中（{$s['task_status']}）"]);
                    echo"  轮询{$i}:{$s['task_status']}\n";
                    if($s['task_status']==='succeed'){$videoUrl=$s['task_result']['videos'][0]['url']??null;break;}
                    if($s['task_status']==='failed')break;
                }
            }

            $ossUrl = $videoUrl; $ossCover = $imgUrl;
            if ($videoUrl) {
                if ($oss->isConfigured()) { echo "☁️ OSS...\n"; $ossUrl = $oss->uploadFromUrl($videoUrl, "works/{$work->id}/output.mp4") ?: $videoUrl; }
                if ($oss->isConfigured() && $imgUrl) { $ossCover = $oss->uploadFromUrl($imgUrl, "works/{$work->id}/cover.jpg") ?: $imgUrl; }
                $dt = (int)($config['duration'] ?? $config['video_duration'] ?? 10);
                $work->update(['status' => 'completed', 'progress' => 100, 'output_video' => $ossUrl, 'output_cover' => $ossCover, 'status_text' => '创作完成', 'duration' => $dt]);
                echo "\n✅ 完成！\n视频: {$ossUrl}\n\n";
            } else {
                $work->update(['status' => 'failed', 'status_text' => '视频生成失败']);
                echo "\n❌ 视频生成失败\n\n";
            }
        } catch(\Exception $e) {
            echo "❌ ".$e->getMessage()."\n";
            $work->update(['status'=>'failed','status_text'=>'生成失败']);
        }
    }
    sleep(2);
}
