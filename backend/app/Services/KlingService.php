<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 可灵AI API 服务 — 严格按官方文档 https://klingai.com/document-api/
 */
class KlingService
{
    private string $apiKey;
    private string $apiBase;

    public function __construct()
    {
        $this->apiKey = config('services.kling.api_key');
        $this->apiBase = config('services.kling.api_base', 'https://api-beijing.klingai.com');
    }

    // ============================================
    // 图片生成 — POST /v1/images/generations
    // 文档: https://klingai.com/document-api/api/image/3-0-omni
    // ============================================

    public function generateImage(string $prompt, array $config = []): array
    {
        $body = [
            'model_name' => $this->toKlingImageModel($config['image_model'] ?? 'kling-v3'),
            'prompt' => $prompt,
            'negative_prompt' => $config['image_negative_prompt'] ?? '',
            'n' => (int)($config['image_n'] ?? 1),
            'aspect_ratio' => $config['image_aspect_ratio'] ?? '9:16',
        ];

        // 参考图（Base64 或 URL）
        if (!empty($config['image'])) {
            $body['image'] = $config['image'];
        }

        return $this->post('/v1/images/generations', $body);
    }

    public function getImageResult(string $taskId): array
    {
        return $this->get("/v1/images/generations/{$taskId}");
    }

    // ============================================
    // Omni视频生成 — POST /v1/videos/omni-video
    // 文档: https://klingai.com/document-api/api/video/3-0-omni/video-omni
    // 支持视频参考输入
    // ============================================

    public function omniVideo(string $videoUrl, string $prompt, array $config = [], ?string $imageUrl = null): array
    {
        $body = [
            'model_name' => $this->toKlingVideoModel($config['video_model'] ?? 'kling-v3-omni'),
            'prompt' => $prompt,
            'duration' => (string)($config['video_duration'] ?? '10'),
            'mode' => $config['video_mode'] ?? 'pro',
            'aspect_ratio' => $config['video_aspect_ratio'] ?? '9:16',
            'video_url' => $videoUrl,
            'image_list' => $imageUrl ? [['image_url' => $imageUrl]] : [],
        ];

        if (($config['video_sound'] ?? 'off') === 'on') {
            $body['sound'] = 'on';
        }

        return $this->post('/v1/videos/omni-video', $body);
    }

    // ============================================
    // 图生视频 — POST /v1/videos/image2video
    // ============================================

    public function imageToVideo(string $imageUrl, string $prompt, array $config = []): array
    {
        $body = [
            'model_name' => $this->toKlingVideoModel($config['video_model'] ?? 'kling-v2-6'),
            'image' => $imageUrl,
            'prompt' => $prompt,
            'duration' => (string)($config['video_duration'] ?? '5'),
            'mode' => $config['video_mode'] ?? 'pro',
        ];

        if (!empty($config['video_negative_prompt'])) {
            $body['negative_prompt'] = $config['video_negative_prompt'];
        }
        if (($config['video_sound'] ?? 'off') === 'on') {
            $body['sound'] = 'on';
        }

        return $this->post('/v1/videos/image2video', $body);
    }

    // ============================================
    // 文生视频 — POST /v1/videos/text2video
    // ============================================

    public function textToVideo(string $prompt, array $config = []): array
    {
        $body = [
            'model_name' => $this->toKlingVideoModel($config['video_model'] ?? 'kling-v2-6'),
            'prompt' => $prompt,
            'duration' => (string)($config['video_duration'] ?? '5'),
            'mode' => $config['video_mode'] ?? 'pro',
            'aspect_ratio' => $config['video_aspect_ratio'] ?? '9:16',
        ];

        if (($config['video_sound'] ?? 'off') === 'on') {
            $body['sound'] = 'on';
        }

        return $this->post('/v1/videos/text2video', $body);
    }

    public function getVideoResult(string $taskId): array
    {
        return $this->get("/v1/videos/image2video/{$taskId}");
    }

    // ============================================
    // 内部方法
    // ============================================

    private function post(string $path, array $body): array
    {
        $response = Http::withHeaders($this->authHeaders())
            ->post("{$this->apiBase}{$path}", $body);
        return $this->parseResponse($response);
    }

    private function get(string $path): array
    {
        $response = Http::withHeaders($this->authHeaders())
            ->get("{$this->apiBase}{$path}");
        return $this->parseResponse($response);
    }

    private function authHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * 图片模型名映射
     */
    private static array $imageModelMap = [
        'kling-v3-omni' => 'kling-v3',
        'kling-image-o1' => 'kling-v3',
        'kling-v2-1' => 'kling-v2-1',
        'kling-v2-new' => 'kling-v2-new',
        'kling-v2' => 'kling-v2',
        'kling-v1-5' => 'kling-v1-5',
        'kling-v1' => 'kling-v1',
    ];

    /**
     * 视频模型名映射
     */
    private static array $videoModelMap = [
        'kling-v3-turbo' => 'kling-v2-5-turbo',
        'kling-v3-omni' => 'kling-v3-omni',
        'kling-o1' => 'kling-video-o1',
        'kling-v2-6' => 'kling-v2-6',
        'kling-v2-5-turbo' => 'kling-v2-5-turbo',
        'kling-v2-1-master' => 'kling-v2-1',
        'kling-v2-master' => 'kling-v2-master',
        'kling-v1-6' => 'kling-v1-6',
        'kling-v1-5' => 'kling-v1-5',
        'kling-v1' => 'kling-v1',
    ];

    private function toKlingImageModel(string $model): string
    {
        return self::$imageModelMap[$model] ?? $model;
    }

    private function toKlingVideoModel(string $model): string
    {
        return self::$videoModelMap[$model] ?? $model;
    }

    private function parseResponse($response): array
    {
        $body = $response->json();
        $code = $body['code'] ?? -1;

        if (!$response->successful() || $code !== 0) {
            $msg = $body['message'] ?? '未知错误';
            Log::error('Kling API error', ['status' => $response->status(), 'code' => $code, 'message' => $msg]);
            throw new \Exception($msg);
        }

        return $body['data'] ?? $body;
    }
}
