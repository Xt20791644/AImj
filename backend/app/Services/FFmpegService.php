<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class FFmpegService
{
    private string $ffmpegPath;

    public function __construct()
    {
        $this->ffmpegPath = config('services.ffmpeg.path', 'ffmpeg');
    }

    /**
     * 拼接多个视频片段
     * @param array $videoFiles 视频文件路径数组
     * @param string $outputPath 输出路径
     */
    public function concatVideos(array $videoFiles, string $outputPath): string
    {
        // 生成 filelist
        $filelist = dirname($outputPath) . '/filelist.txt';
        $content = '';
        foreach ($videoFiles as $file) {
            $content .= "file '" . str_replace("'", "'\\''", $file) . "'\n";
        }
        file_put_contents($filelist, $content);

        $cmd = sprintf(
            '%s -f concat -safe 0 -i %s -c copy -y %s 2>&1',
            escapeshellcmd($this->ffmpegPath),
            escapeshellarg($filelist),
            escapeshellarg($outputPath)
        );

        Log::info('FFmpeg concat', ['cmd' => $cmd]);
        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            Log::error('FFmpeg concat failed', ['output' => implode("\n", $output)]);
            throw new \Exception('视频拼接失败');
        }

        @unlink($filelist);
        return $outputPath;
    }

    /**
     * 视频 + 音频混合
     */
    public function mixAudio(string $videoPath, string $audioPath, string $outputPath, float $audioVolume = 1.0): string
    {
        $cmd = sprintf(
            '%s -i %s -i %s -filter_complex "[1:a]volume=%f[a]" -map 0:v -map "[a]" -c:v copy -shortest -y %s 2>&1',
            escapeshellcmd($this->ffmpegPath),
            escapeshellarg($videoPath),
            escapeshellarg($audioPath),
            $audioVolume,
            escapeshellarg($outputPath)
        );

        Log::info('FFmpeg mix audio', ['cmd' => $cmd]);
        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            Log::error('FFmpeg mix audio failed', ['output' => implode("\n", $output)]);
            throw new \Exception('音频混合失败');
        }

        return $outputPath;
    }

    /**
     * 多段音频拼接 (用于多角色配音拼接)
     */
    public function concatAudios(array $audioFiles, string $outputPath): string
    {
        $filelist = dirname($outputPath) . '/audiolist.txt';
        $content = '';
        foreach ($audioFiles as $file) {
            $content .= "file '" . str_replace("'", "'\\''", $file) . "'\n";
        }
        file_put_contents($filelist, $content);

        $cmd = sprintf(
            '%s -f concat -safe 0 -i %s -c copy -y %s 2>&1',
            escapeshellcmd($this->ffmpegPath),
            escapeshellarg($filelist),
            escapeshellarg($outputPath)
        );

        exec($cmd, $output, $returnCode);
        @unlink($filelist);

        if ($returnCode !== 0) {
            throw new \Exception('音频拼接失败');
        }

        return $outputPath;
    }

    /**
     * 添加字幕
     */
    public function addSubtitles(string $videoPath, string $srtPath, string $outputPath): string
    {
        $cmd = sprintf(
            '%s -i %s -vf "subtitles=%s:force_style=' .
            '\'FontSize=20,PrimaryColour=&HFFFFFF,OutlineColour=&H000000,BorderStyle=1\'" -y %s 2>&1',
            escapeshellcmd($this->ffmpegPath),
            escapeshellarg($videoPath),
            escapeshellarg(str_replace('\\', '/', $srtPath)),
            escapeshellarg($outputPath)
        );

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('字幕添加失败');
        }

        return $outputPath;
    }

    /**
     * 提取视频封面
     */
    public function extractCover(string $videoPath, string $outputPath, int $timeSeconds = 3): string
    {
        $cmd = sprintf(
            '%s -i %s -ss %d -vframes 1 -y %s 2>&1',
            escapeshellcmd($this->ffmpegPath),
            escapeshellarg($videoPath),
            $timeSeconds,
            escapeshellarg($outputPath)
        );

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('封面提取失败');
        }

        return $outputPath;
    }

    /**
     * 获取视频时长 (秒)
     */
    public function getDuration(string $videoPath): float
    {
        $cmd = sprintf(
            '%s -i %s 2>&1',
            escapeshellcmd($this->ffmpegPath),
            escapeshellarg($videoPath)
        );

        exec($cmd, $output);

        foreach ($output as $line) {
            if (preg_match('/Duration: (\d{2}):(\d{2}):(\d{2}\.\d+)/', $line, $matches)) {
                return $matches[1] * 3600 + $matches[2] * 60 + (float)$matches[3];
            }
        }

        return 0;
    }
}
