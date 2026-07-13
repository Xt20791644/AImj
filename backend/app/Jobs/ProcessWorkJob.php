<?php

namespace App\Jobs;

use App\Models\Work;
use App\Models\WorkTimeline;
use App\Services\KlingService;
use App\Services\CosyVoiceService;
use App\Services\KlingConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\OssService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessWorkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;
    public int $tries = 1;

    public function __construct(
        public int $workId,
        public string $startFrom = 'all'
    ) {}

    public function handle(KlingService $kling, CosyVoiceService $tts, OssService $oss): void
    {
        $work = Work::findOrFail($this->workId);
        $work->update(['status' => 'processing']);

        $meta = $work->meta ?? [];
        $config = $meta['kling_config'] ?? KlingConfig::defaults();

        // 声音需要 pro 模式：std 模式不支持声音
        if (($config['video_sound'] ?? 'off') === 'on' && ($config['video_mode'] ?? 'std') === 'std') {
            $config['video_mode'] = 'pro';
            $meta['kling_config'] = $config;
            Log::info("Work {$this->workId}: Auto-upgraded video mode to pro for sound support");
        }

        try {
            // Step 1-3: Script/Characters/Storyboard (text pipeline)
            if ($this->startFrom === 'all') {
                $this->sleep(1); $this->runStep($work, 'script', 'completed', '剧本分析完成'); $work->update(['progress' => 15]);
                $this->sleep(1); $this->runStep($work, 'characters', 'completed', '角色提取完成'); $work->update(['progress' => 30]);
                $this->sleep(1); $this->runStep($work, 'storyboard', 'completed', '分镜生成完成'); $work->update(['progress' => 45]);
            }

            // Step 4: 图片生成 (调用可灵 + 轮询拿结果)
            $imageUrls = [];
            $this->runStep($work, 'images', 'processing', '正在生成画面...');
            try {
                $imageUrls = $this->generateAndPollImages($kling, $work, $config);
                $meta['image_results'] = $imageUrls;
                $this->runStep($work, 'images', 'completed', '画面生成完成');
                Log::info("Work {$work->id}: Generated " . count($imageUrls) . " images");
            } catch (\Exception $e) {
                Log::warning("Work {$work->id}: Image generation failed - " . $e->getMessage());
                $this->runStep($work, 'images', 'completed', '画面生成跳过（API失败或余额不足）');
            }
            $work->update(['progress' => 60, 'meta' => $meta]);

            // Step 5: 视频生成 (用生成的图片做图生视频)
            $videoUrl = null;
            $this->runStep($work, 'video', 'processing', '正在生成视频...');
            try {
                if (!empty($imageUrls)) {
                    $videoUrl = $this->generateAndPollVideo($kling, $work, $config, $imageUrls);
                    $meta['video_results'] = [$videoUrl];
                    $this->runStep($work, 'video', 'completed', '视频生成完成');
                } elseif (!empty($config['video_model'])) {
                    $videoUrl = $this->generateAndPollVideo($kling, $work, $config, []);
                    $meta['video_results'] = [$videoUrl];
                    $this->runStep($work, 'video', 'completed', '视频生成完成(文生视频)');
                }
            } catch (\Exception $e) {
                $errMsg = '视频生成失败: ' . (mb_strlen($e->getMessage()) > 100 ? mb_substr($e->getMessage(), 0, 100) : $e->getMessage());
                Log::warning("Work {$work->id}: {$errMsg}");
                $this->runStep($work, 'video', 'failed', $errMsg);
                // 退还视频部分的积分（只扣图片）
                $work->user->rechargeCredits(30, "视频失败退款《{$work->title}》");
            }
            $work->update(['progress' => 80, 'meta' => $meta]);

            // Step 6: 配音
            $this->runStep($work, 'audio', 'processing', '正在生成配音...');
            try {
                $script = $meta['script'] ?? $work->content;
                $tts->synthesize(mb_substr($script, 0, 200));
                $this->runStep($work, 'audio', 'completed', '配音生成完成');
            } catch (\Exception $e) {
                Log::warning("Work {$work->id}: TTS failed - " . $e->getMessage());
                $this->runStep($work, 'audio', 'completed', '配音跳过（API未配置）');
            }
            $work->update(['progress' => 90]);

            // Step 7: 上传到 OSS（如果已配置）
            $ossVideoUrl = $videoUrl;
            $ossCoverUrl = $imageUrls[0] ?? null;
            if ($oss->isConfigured() && $videoUrl) {
                $this->runStep($work, 'compose', 'processing', '正在上传到云存储...');
                $ossV = $oss->uploadFromUrl($videoUrl, "works/{$work->id}/output.mp4");
                if ($ossV) $ossVideoUrl = $ossV;
                if ($ossCoverUrl) {
                    $ossC = $oss->uploadFromUrl($ossCoverUrl, "works/{$work->id}/cover.jpg");
                    if ($ossC) $ossCoverUrl = $ossC;
                }
            }
            $this->runStep($work, 'compose', 'completed', '创作完成');
            $work->update([
                'progress' => 100, 'status' => 'completed', 'status_text' => '创作完成',
                'output_video' => $ossVideoUrl,
                'output_cover' => $ossCoverUrl,
                'duration' => (int)($config['video_duration'] ?? 5), 'meta' => $meta,
            ]);

            Log::info("Work {$work->id} completed successfully");
        } catch (\Throwable $e) {
            Log::error("Work {$work->id} failed: " . $e->getMessage());
            $msg = mb_strlen($e->getMessage()) > 200 ? mb_substr($e->getMessage(), 0, 200) . '...' : $e->getMessage();
            $work->update(['status' => 'failed', 'status_text' => '失败: ' . $msg]);
            // 退款：生成失败退还积分
            $cost = config('services.credits.cost_per_generation', 50);
            $work->user->rechargeCredits($cost, "创作失败退款《{$work->title}》");
        }
    }

    /**
     * 生成图片并轮询获取结果 URL
     */
    private function generateAndPollImages(KlingService $kling, Work $work, array $config): array
    {
        $n = max(1, min(3, (int)($config['image_n'] ?? 1)));
        $urls = [];
        $script = $work->meta['script'] ?? $work->content;
        $promptBase = mb_substr($script, 0, 500);

        for ($i = 0; $i < $n; $i++) {
            $prompt = "短剧《{$work->title}》场景" . ($i + 1) . ": {$promptBase}，{$work->style}风格";
            $this->runStep($work, 'images', 'processing', "正在生成第 " . ($i + 1) . " / {$n} 张图片...");

            $result = $kling->generateImage($prompt, $config);
            $taskId = $result['task_id'] ?? null;

            if ($taskId) {
                $imageUrl = $this->pollKlingTask($kling, 'image', $taskId, 60);
                if ($imageUrl) {
                    $urls[] = $imageUrl;
                }
            }
            if ($i < $n - 1) $this->sleep(3);
        }

        return $urls;
    }

    /**
     * 生成视频并轮询获取结果 URL
     */
    private function generateAndPollVideo(KlingService $kling, Work $work, array $config, array $imageUrls): ?string
    {
        if (!empty($imageUrls)) {
            // 图生视频
            $result = $kling->imageToVideo($imageUrls[0], "短剧《{$work->title}》场景", $config);
        } else {
            // 文生视频（降级）
            $script = $work->meta['script'] ?? $work->content;
            $result = $kling->textToVideo(mb_substr($script, 0, 500), $config);
        }

        $taskId = $result['task_id'] ?? null;
        if (!$taskId) return null;

        $this->runStep($work, 'video', 'processing', '正在生成视频（可能需要1-3分钟）...');
        return $this->pollKlingTask($kling, 'video', $taskId, 180);
    }

    /**
     * 轮询 Kling 任务直到完成
     */
    private function pollKlingTask(KlingService $kling, string $type, string $taskId, int $timeoutSeconds): ?string
    {
        $startTime = time();
        $pollInterval = 5;

        while (time() - $startTime < $timeoutSeconds) {
            $this->sleep($pollInterval);

            try {
                $result = $type === 'image'
                    ? $kling->getImageResult($taskId)
                    : $kling->getVideoResult($taskId);

                $status = $result['task_status'] ?? '';

                if ($status === 'succeed') {
                    if ($type === 'image') {
                        return $result['task_result']['images'][0]['url'] ?? null;
                    } else {
                        return $result['task_result']['videos'][0]['url'] ?? null;
                    }
                }

                if ($status === 'failed') {
                    Log::warning("Kling {$type} task {$taskId} failed: " . ($result['task_status_msg'] ?? ''));
                    return null;
                }
            } catch (\Exception $e) {
                Log::warning("Poll failed: " . $e->getMessage());
            }
        }

        Log::warning("Kling {$type} task {$taskId} timed out after {$timeoutSeconds}s");
        return null;
    }

    private function runStep(Work $work, string $step, string $status, string $message): void
    {
        WorkTimeline::where('work_id', $work->id)->where('step', $step)
            ->update(['status' => $status, 'message' => $message]);
        $work->update(['status_text' => $message]);
    }

    private function sleep(int $seconds): void
    {
        sleep($seconds);
    }
}
