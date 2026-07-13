<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CosyVoiceService
{
    private string $apiKey;
    private string $apiBase;

    public function __construct()
    {
        $this->apiKey = config('services.cosyvoice.api_key');
        $this->apiBase = config('services.cosyvoice.api_base', 'https://dashscope.aliyuncs.com/api/v1');
    }

    /**
     * 生成配音
     * @param string $text 文本内容
     * @param string $voice 音色名称
     * @param string|null $outputPath 输出文件路径
     * @return string 文件路径
     */
    public function synthesize(string $text, string $voice = 'longxiaochun', ?string $outputPath = null): string
    {
        $outputPath = $outputPath ?? storage_path('app/tts/' . uniqid() . '.mp3');

        // 确保目录存在
        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'X-DashScope-Async' => 'enable',
        ])->post("{$this->apiBase}/services/aigc/multimodal-generation/generation", [
            'model' => 'cosyvoice-v1',
            'input' => [
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            ['text' => $text],
                        ],
                    ],
                ],
            ],
            'parameters' => [
                'voice' => $voice,
                'format' => 'mp3',
                'sample_rate' => 48000,
                'volume' => 50,
                'rate' => 1.0,
                'pitch' => 1.0,
            ],
        ]);

        if (!$response->successful()) {
            Log::error('CosyVoice request failed', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            throw new \Exception('CosyVoice 配音请求失败: ' . ($response->json('message') ?? '未知错误'));
        }

        $data = $response->json();

        // 异步任务：轮询获取结果
        if (isset($data['output']['task_id'])) {
            return $this->pollTaskResult($data['output']['task_id'], $outputPath);
        }

        // 同步返回：直接获取音频URL
        if (isset($data['output']['audio_url'])) {
            $this->downloadAudio($data['output']['audio_url'], $outputPath);
            return $outputPath;
        }

        throw new \Exception('CosyVoice 返回格式异常');
    }

    /**
     * 轮询异步任务结果
     */
    private function pollTaskResult(string $taskId, string $outputPath): string
    {
        $maxRetries = 30;
        $retryCount = 0;

        while ($retryCount < $maxRetries) {
            sleep(2);
            $retryCount++;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get("{$this->apiBase}/tasks/{$taskId}");

            if (!$response->successful()) continue;

            $data = $response->json();

            if (($data['output']['task_status'] ?? '') === 'SUCCEEDED') {
                $audioUrl = $data['output']['results'][0]['url'] ?? null;
                if ($audioUrl) {
                    $this->downloadAudio($audioUrl, $outputPath);
                    return $outputPath;
                }
            }

            if (($data['output']['task_status'] ?? '') === 'FAILED') {
                throw new \Exception('CosyVoice 配音任务失败: ' . ($data['output']['message'] ?? ''));
            }
        }

        throw new \Exception('CosyVoice 配音任务超时');
    }

    /**
     * 下载音频文件
     */
    private function downloadAudio(string $url, string $outputPath): void
    {
        $response = Http::timeout(120)->get($url);
        if (!$response->successful()) {
            throw new \Exception('下载配音文件失败');
        }
        file_put_contents($outputPath, $response->body());
    }

    /**
     * CosyVoice 常用中文音色
     * 音色列表: https://help.aliyun.com/zh/model-studio/cosyvoice-voices
     */
    public static function voices(): array
    {
        return [
            // 基础音色（预置，无需克隆）
            'female_young' => 'longxiaochun',    // 龙小春 - 年轻女声
            'female_gentle' => 'longxiaoxia',    // 龙小夏 - 温柔女声
            'female_sweet' => 'longxiaomei',     // 龙小妹 - 活泼女声
            'male_young' => 'longyue',           // 龙跃 - 年轻男声
            'male_deep' => 'longchen',           // 龙辰 - 成熟男声
            'male_warm' => 'longye',             // 龙野 - 低沉男声
            
            // 情感音色
            'sad_female' => 'loongbell',         // 悲伤女声
            'storytelling' => 'loongbella',      // 故事叙述
            
            // 方言/多语言
            'cantonese_female' => 'loongjane',      // 粤语女声
            'sichuan_female' => 'loongfiona',       // 四川话女声
            'english_male' => 'loongterry',          // 英语男声
            'japanese_female' => 'loongyuri',        // 日语女声
        ];
    }

    /**
     * 音色克隆（需要传入参考音频URL）
     * 克隆后的音色可以用于后续所有配音
     */
    public function voiceClone(string $voiceName, string $referenceAudioUrl, string $referenceText = ''): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->apiBase}/services/aigc/multimodal-generation/generation", [
            'model' => 'cosyvoice-v1',
            'input' => [
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            ['text' => $referenceText ?: '参考语音'],
                            ['audio' => $referenceAudioUrl],
                        ],
                    ],
                ],
            ],
            'parameters' => [
                'voice' => $voiceName,
                'format' => 'mp3',
            ],
        ]);

        if (!$response->successful()) {
            throw new \Exception('CosyVoice 音色克隆失败: ' . ($response->json('message') ?? ''));
        }

        return $response->json();
    }
}
