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
            'aspect_ratio' => $config['image_aspect_ratio'] ?? $config['aspect_ratio'] ?? '9:16',
            'resolution' => $config['image_resolution'] ?? '1k',
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
    // 文档: https://klingai.com/document-api/api/video/o1
    // 统一入口：文生视频 / 图生视频 / 视频参考
    // ============================================

    public function omniVideo(string $videoUrl, string $prompt, array $config = [], ?string $imageUrl = null): array
    {
        $body = [
            'model_name' => $this->toKlingVideoModel($config['video_model'] ?? 'kling-v3-omni'),
            'prompt' => $prompt,
            'duration' => (string)($config['video_duration'] ?? $config['duration'] ?? '10'),
            'mode' => $config['video_mode'] ?? $config['mode'] ?? 'pro',
            'aspect_ratio' => $config['video_aspect_ratio'] ?? $config['aspect_ratio'] ?? '9:16',
            'video_list' => [
                [
                    'video_url' => $videoUrl,
                    'refer_type' => 'base',
                    'keep_original_sound' => ($config['video_sound'] ?? 'off') === 'on' ? 'yes' : 'no',
                ],
            ],
        ];

        // 生成的首帧图作为参考
        if ($imageUrl) {
            $body['image_list'] = [['image_url' => $imageUrl]];
        }

        // 有参考视频时 sound 必须为 off，由 keep_original_sound 控制是否保留原声
        // 文档: "When a reference video is used, the sound parameter must be set to off"

        return $this->post('/v1/videos/omni-video', $body);
    }

    // ============================================
    // 图生视频 — POST /v1/videos/image2video
    // 文档: https://klingai.com/document-api/api/video/3-0-omni
    // ============================================

    public function imageToVideo(string $imageUrl, string $prompt, array $config = []): array
    {
        $body = [
            'model_name' => $this->toKlingVideoModel($config['video_model'] ?? 'kling-v2-6'),
            'image' => $imageUrl,
            'prompt' => $prompt,
            'duration' => (string)($config['video_duration'] ?? $config['duration'] ?? '5'),
            'mode' => $config['video_mode'] ?? $config['mode'] ?? 'pro',
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
            'duration' => (string)($config['video_duration'] ?? $config['duration'] ?? '5'),
            'mode' => $config['video_mode'] ?? $config['mode'] ?? 'pro',
            'aspect_ratio' => $config['video_aspect_ratio'] ?? $config['aspect_ratio'] ?? '9:16',
        ];

        if (($config['video_sound'] ?? 'off') === 'on') {
            $body['sound'] = 'on';
        }

        return $this->post('/v1/videos/text2video', $body);
    }

    // ============================================
    // 查询结果（按 endpoint 区分）
    // ============================================

    public function getVideoResult(string $taskId): array
    {
        return $this->get("/v1/videos/image2video/{$taskId}");
    }

    public function getTextVideoResult(string $taskId): array
    {
        return $this->get("/v1/videos/text2video/{$taskId}");
    }

    public function getOmniVideoResult(string $taskId): array
    {
        return $this->get("/v1/videos/omni-video/{$taskId}");
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
     * 图片模型名映射 — 按官方支持的模型 https://klingai.com/document-api/api/image/3-0-omni
     * kling-v1, kling-v1-5, kling-v2, kling-v2-new, kling-v2-1, kling-v3
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
     * 视频模型名映射 — 按官方支持的模型
     * image2video: kling-v1, kling-v1-5, kling-v1-6, kling-v2-master, kling-v2-1, kling-v2-1-master, kling-v2-5-turbo, kling-v2-6, kling-v3
     * omni-video: kling-video-o1, kling-v3-omni
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
