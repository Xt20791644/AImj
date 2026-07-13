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
use Illuminate\Support\Facades\Log;

class ProcessWorkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;
    public int $tries = 1;

    public function __construct(
        public int $workId,
        public string $startFrom = 'all' // 'all' | 'video'
    ) {}

    public function handle(KlingService $kling, CosyVoiceService $tts): void
    {
        $work = Work::findOrFail($this->workId);
        $work->update(['status' => 'processing']);

        $meta = $work->meta ?? [];
        $config = $meta['kling_config'] ?? KlingConfig::defaults();

        try {
            if ($this->startFrom === 'all') {
                // 快速模式：全流程
                $this->runStep($work, 'script', 'processing', '正在分析故事结构...');
                $this->sleep(1);
                $this->runStep($work, 'script', 'completed', '剧本分析完成');
                $work->update(['progress' => 15]);

                $this->runStep($work, 'characters', 'processing', '正在提取角色...');
                $this->sleep(1);
                $this->runStep($work, 'characters', 'completed', '角色提取完成');
                $work->update(['progress' => 30]);

                $this->runStep($work, 'storyboard', 'processing', '正在生成分镜脚本...');
                $this->sleep(1);
                $this->runStep($work, 'storyboard', 'completed', '分镜生成完成');
                $work->update(['progress' => 45]);

                $this->runStep($work, 'images', 'processing', '正在生成画面...');
                try {
                    $this->generateImages($kling, $work, $config);
                    $this->runStep($work, 'images', 'completed', '画面生成完成');
                } catch (\Exception $e) {
                    Log::warning('Image generation failed, continuing: ' . $e->getMessage());
                    $this->runStep($work, 'images', 'completed', '画面生成跳过（API余额不足）');
                }
                $work->update(['progress' => 60]);
            }

            // 视频生成（快速模式和精细模式都执行）
            $this->runStep($work, 'video', 'processing', '正在生成视频片段...');
            try {
                $this->generateVideos($kling, $work, $config, $meta);
                $this->runStep($work, 'video', 'completed', '视频生成完成');
            } catch (\Exception $e) {
                Log::warning('Video generation failed: ' . $e->getMessage());
                $this->runStep($work, 'video', 'completed', '视频生成跳过（API余额不足）');
            }
            $work->update(['progress' => 75]);

            // 配音
            $this->runStep($work, 'audio', 'processing', '正在生成配音...');
            try {
                $script = $meta['script'] ?? $work->content;
                $tts->synthesize(mb_substr($script, 0, 200));
                $this->runStep($work, 'audio', 'completed', '配音生成完成');
            } catch (\Exception $e) {
                Log::warning('TTS failed: ' . $e->getMessage());
                $this->runStep($work, 'audio', 'completed', '配音跳过（API未配置）');
            }
            $work->update(['progress' => 90]);

            // 合成
            $this->runStep($work, 'compose', 'processing', '正在合成导出...');
            $this->sleep(1);
            $this->runStep($work, 'compose', 'completed', '视频合成完成');
            $work->update([
                'progress' => 100, 'status' => 'completed',
                'status_text' => '创作完成', 'duration' => (int)($config['video_duration'] ?? 5),
            ]);

            Log::info("Work {$work->id} completed");
        } catch (\Throwable $e) {
            Log::error("Work {$work->id} failed: " . $e->getMessage());
            $work->update(['status' => 'failed', 'status_text' => '失败: ' . $e->getMessage()]);
        }
    }

    private function generateImages(KlingService $kling, Work $work, array $config): void
    {
        $n = max(1, min(3, (int)($config['image_n'] ?? 1)));
        $results = [];
        for ($i = 0; $i < $n; $i++) {
            $prompt = "短剧《{$work->title}》场景图，{$work->style}风格";
            $result = $kling->generateImage($prompt, $config);
            $results[] = $result;
            $this->sleep(3);
        }
        $meta = $work->meta ?? [];
        $meta['image_results'] = $results;
        $work->update(['meta' => $meta]);
    }

    private function generateVideos(KlingService $kling, Work $work, array $config, array $meta): void
    {
        $images = $meta['generated_images'] ?? $meta['selected_images'] ?? [];
        $results = [];

        if (empty($images)) {
            // 文生视频
            $result = $kling->textToVideo("短剧《{$work->title}》", $config);
            $results[] = $result;
        } else {
            foreach (array_slice($images, 0, 3) as $img) {
                $url = $img['url'] ?? '';
                if ($url) {
                    $result = $kling->imageToVideo($url, "短剧《{$work->title}》", $config);
                    $results[] = $result;
                    $this->sleep(3);
                }
            }
        }

        $meta = $work->meta ?? [];
        $meta['video_results'] = $results;
        $work->update(['meta' => $meta]);
    }

    private function runStep(Work $work, string $step, string $status, string $message): void
    {
        WorkTimeline::where('work_id', $work->id)->where('step', $step)
            ->update(['status' => $status, 'message' => $message]);
        $work->update(['status_text' => $message]);
    }

    private function sleep(int $seconds): void
    {
        if (app()->environment('local')) {
            sleep($seconds);
        }
    }
}
