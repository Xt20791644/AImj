<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KlingService
{
    private string $apiKey;
    private string $apiSecret;
    private string $apiBase;

    public function __construct()
    {
        $this->apiKey = config('services.kling.api_key');
        $this->apiSecret = config('services.kling.api_secret');
        $this->apiBase = config('services.kling.api_base');
    }

    /**
     * 生成图片
     * @return array {task_id: string}
     */
    public function generateImage(string $prompt, array $options = []): array
    {
        $response = Http::withToken($this->getToken())
            ->post("{$this->apiBase}/v1/images/generations", [
                'model_name' => 'kling-v1',
                'prompt' => $prompt,
                'negative_prompt' => $options['negative_prompt'] ?? '',
                'n' => $options['n'] ?? 1,
                'size' => $options['size'] ?? '1024x1024',
                ...$options,
            ]);

        if (!$response->successful()) {
            Log::error('Kling image generation failed', $response->json());
            throw new \Exception('可灵生图失败: ' . ($response->json('message') ?? '未知错误'));
        }

        return $response->json();
    }

    /**
     * 查询图片生成结果
     */
    public function getImageResult(string $taskId): array
    {
        $response = Http::withToken($this->getToken())
            ->get("{$this->apiBase}/v1/images/generations/{$taskId}");

        return $response->json();
    }

    /**
     * 生成视频（图生视频）
     * @return array {task_id: string}
     */
    public function generateVideo(string $imageUrl, string $prompt = '', array $options = []): array
    {
        $response = Http::withToken($this->getToken())
            ->post("{$this->apiBase}/v1/videos/image2video", [
                'model_name' => 'kling-v1',
                'image' => $imageUrl,
                'prompt' => $prompt,
                'duration' => $options['duration'] ?? '5',
                'mode' => $options['mode'] ?? 'std',
                ...$options,
            ]);

        if (!$response->successful()) {
            Log::error('Kling video generation failed', $response->json());
            throw new \Exception('可灵生视频失败: ' . ($response->json('message') ?? '未知错误'));
        }

        return $response->json();
    }

    /**
     * 查询视频生成结果
     */
    public function getVideoResult(string $taskId): array
    {
        $response = Http::withToken($this->getToken())
            ->get("{$this->apiBase}/v1/videos/image2video/{$taskId}");

        return $response->json();
    }

    private function getToken(): string
    {
        // 可灵API使用AK/SK方式鉴权，需要自行实现签名
        // 简化版：直接使用API Key（具体鉴权方式以可灵官方文档为准）
        return $this->apiSecret ?: $this->apiKey;
    }
}
