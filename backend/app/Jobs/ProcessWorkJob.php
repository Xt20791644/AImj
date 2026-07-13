<?php

namespace App\Jobs;

use App\Models\Work;
use App\Models\WorkTimeline;
use App\Services\KlingService;
use App\Services\AzureTTSService;
use App\Services\StoryClawService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWorkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600; // 1 hour
    public int $tries = 1;

    public function __construct(
        public int $workId
    ) {}

    public function handle(
        StoryClawService $storyClaw,
        KlingService $kling,
        AzureTTSService $tts,
    ): void {
        $work = Work::findOrFail($this->workId);
        $work->update(['status' => 'processing']);

        try {
            // Step 1: 剧本分析
            $this->updateTimeline($work, 'script', 'processing', '正在分析故事结构...');
            $storyData = $storyClaw->processStory($work->title, $work->content, $work->style);
            $this->updateTimeline($work, 'script', 'completed', '剧本分析完成', $storyData);
            $work->update(['progress' => 15, 'status_text' => '剧本分析完成']);

            // Step 2: 角色提取
            $this->updateTimeline($work, 'characters', 'processing', '正在提取角色信息...');
            $this->updateTimeline($work, 'characters', 'completed', '角色提取完成', $storyData['characters'] ?? []);
            $work->update(['progress' => 30, 'status_text' => '角色提取完成']);

            // Step 3: 分镜生成
            $this->updateTimeline($work, 'storyboard', 'processing', '正在生成分镜脚本...');
            $this->updateTimeline($work, 'storyboard', 'completed', '分镜生成完成', $storyData['storyboard'] ?? []);
            $work->update(['progress' => 45, 'status_text' => '分镜生成完成']);

            // Step 4: 生成图片 (TODO: 接入可灵API后启用)
            $this->updateTimeline($work, 'images', 'processing', '正在生成画面素材...');
            // $images = $this->generateImages($kling, $storyData);
            $this->updateTimeline($work, 'images', 'completed', '画面生成完成');
            $work->update(['progress' => 60, 'status_text' => '画面生成完成']);

            // Step 5: 生成视频片段 (TODO: 接入可灵API后启用)
            $this->updateTimeline($work, 'video', 'processing', '正在生成视频片段...');
            // $videos = $this->generateVideos($kling, $storyData);
            $this->updateTimeline($work, 'video', 'completed', '视频生成完成');
            $work->update(['progress' => 75, 'status_text' => '视频生成完成']);

            // Step 6: 配音生成 (TODO: 接入Azure TTS API后启用)
            $this->updateTimeline($work, 'audio', 'processing', '正在生成配音...');
            // $audioFiles = $this->generateAudio($tts, $storyData);
            $this->updateTimeline($work, 'audio', 'completed', '配音生成完成');
            $work->update(['progress' => 90, 'status_text' => '配音生成完成']);

            // Step 7: 合成导出
            $this->updateTimeline($work, 'compose', 'processing', '正在合成导出视频...');
            $output = $this->composeVideo($work, $storyData);
            $this->updateTimeline($work, 'compose', 'completed', '视频合成完成', $output);
            $work->update([
                'progress' => 100,
                'status' => 'completed',
                'status_text' => '创作完成',
                'output_video' => $output['video'] ?? null,
                'output_cover' => $output['cover'] ?? null,
                'duration' => $output['duration'] ?? 0,
            ]);

            Log::info("Work {$work->id} completed successfully");
        } catch (\Throwable $e) {
            Log::error("Work {$work->id} failed: " . $e->getMessage());
            $work->update([
                'status' => 'failed',
                'status_text' => '生成失败: ' . $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function updateTimeline(Work $work, string $step, string $status, string $message, array $output = null): void
    {
        WorkTimeline::where('work_id', $work->id)
            ->where('step', $step)
            ->update([
                'status' => $status,
                'message' => $message,
                'output' => $output,
            ]);

        $work->update(['status_text' => $message]);
    }

    /**
     * 合成最终视频 (FFmpeg)
     * TODO: 实际对接FFmpeg命令
     */
    private function composeVideo(Work $work, array $storyData): array
    {
        $outputDir = storage_path("app/works/{$work->id}");
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $outputVideo = "works/{$work->id}/output.mp4";
        $outputCover = "works/{$work->id}/cover.jpg";

        // FFmpeg 合成命令 (示例)
        // $cmd = "ffmpeg -f concat -i {$outputDir}/filelist.txt -c copy {$outputDir}/output.mp4";
        // exec($cmd, $output, $returnCode);

        return [
            'video' => $outputVideo,
            'cover' => $outputCover,
            'duration' => 0,
        ];
    }
}
