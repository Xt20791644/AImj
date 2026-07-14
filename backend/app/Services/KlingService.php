<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
    // 图片生成
    // ============================================

    public function generateImage(string $prompt, array $config = []): array
    {
        $defaults = KlingConfig::defaults();
        $body = [
            'model_name' => $this->mapModel($config['image_model'] ?? 'kling-v3-omni'),
            'prompt' => $prompt,
            'negative_prompt' => $config['image_negative_prompt'] ?? '',
            'n' => (int)($config['image_n'] ?? 1),
            'aspect_ratio' => $config['image_aspect_ratio'] ?? '9:16',
            'resolution' => $config['image_resolution'] ?? '1k',
        ];
        if (!empty($config['reference_image'])) $body['image'] = $config['reference_image'];

        return $this->post('/v1/images/generations', $body);
    }

    public function getImageResult(string $taskId): array
    {
        return $this->get("/v1/images/generations/{$taskId}");
    }

    // ============================================
    // 视频生成
    // ============================================

    public function imageToVideo(string $imageUrl, string $prompt, array $config = []): array
    {
        $defaults = KlingConfig::defaults();
        $body = [
            'model_name' => $config['video_model'] ?? $defaults['video_model'],
            'image' => $imageUrl,
            'prompt' => $prompt,
            'negative_prompt' => $config['video_negative_prompt'] ?? '',
            'duration' => (string)($config['video_duration'] ?? '5'),
            'mode' => $config['video_mode'] ?? 'pro',
        ];
        if (!empty($config['image_tail'])) $body['image_tail'] = $config['image_tail'];
        if (($config['video_sound'] ?? '') === 'on') $body['sound'] = 'on';
        if (!empty($config['video_aspect_ratio'])) $body['aspect_ratio'] = $config['video_aspect_ratio'];
        $this->addCameraControl($body, $config);
        if (isset($config['cfg_scale']) && !str_starts_with($body['model_name'], 'kling-v2')) {
            $body['cfg_scale'] = (float)$config['cfg_scale'];
        }

        return $this->post('/v1/videos/image2video', $body);
    }

    public function textToVideo(string $prompt, array $config = []): array
    {
        $defaults = KlingConfig::defaults();
        $body = [
            'model_name' => $config['video_model'] ?? $defaults['video_model'],
            'prompt' => $prompt,
            'negative_prompt' => $config['video_negative_prompt'] ?? '',
            'duration' => (string)($config['video_duration'] ?? '5'),
            'mode' => $config['video_mode'] ?? 'pro',
        ];
        if (($config['video_sound'] ?? '') === 'on') $body['sound'] = 'on';
        if (!empty($config['video_aspect_ratio'])) $body['aspect_ratio'] = $config['video_aspect_ratio'];
        return $this->post('/v1/videos/text2video', $body);
    }

    public function getVideoResult(string $taskId): array
    {
        return $this->get("/v1/videos/image2video/{$taskId}");
    }

    // ============================================
    // 内部方法
    // ============================================

    private function addCameraControl(array &$body, array $config): void
    {
        if (empty($config['camera_type'])) return;
        $model = $body['model_name'] ?? '';
        // kling-v2-6 和更早版本不支持 camera_control
        if (str_contains($model, 'v2-') || str_contains($model, 'v1')) return;
        $body['camera_control'] = ['type' => $config['camera_type']];
        if ($config['camera_type'] === 'simple' && !empty($config['camera_config'])) {
            $body['camera_control']['config'] = $config['camera_config'];
        }
    }

    private function post(string $path, array $body): array
    {
        $response = Http::withHeaders($this->authHeaders())->post("{$this->apiBase}{$path}", $body);
        return $this->parseResponse($response);
    }

    private function get(string $path): array
    {
        $response = Http::withHeaders($this->authHeaders())->get("{$this->apiBase}{$path}");
        return $this->parseResponse($response);
    }

    private function authHeaders(): array
    {
        return ['Authorization' => "Bearer {$this->apiKey}", 'Content-Type' => 'application/json'];
    }

    // Kling API 识别 kling-v1 等原生名称，需要映射
    private static array $modelMap = [
        'kling-v3' => 'kling-v1', 'kling-v3-omni' => 'kling-v1',
        'kling-v2-1' => 'kling-v1', 'kling-v2-new' => 'kling-v1',
        'kling-v2' => 'kling-v1', 'kling-v1-5' => 'kling-v1', 'kling-v1' => 'kling-v1',
        'kling-image-o1' => 'kling-v1',
        'kling-v3-turbo' => 'kling-v1', 'kling-o1' => 'kling-v1',
        'kling-v2-6' => 'kling-v1',
    ];

    private function mapModel(string $model): string
    {
        return self::$modelMap[$model] ?? $model;
    }

    private function parseResponse($response): array
    {
        if (!$response->successful()) {
            $body = $response->json();
            throw new \Exception('可灵API失败: ' . ($body['message'] ?? '未知错误'));
        }
        $body = $response->json();
        if (($body['code'] ?? -1) !== 0) {
            throw new \Exception('可灵API错误: ' . ($body['message'] ?? '未知错误'));
        }
        return $body['data'] ?? $body;
    }
}
