<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AzureTTSService
{
    private string $key;
    private string $region;
    private string $endpoint;

    public function __construct()
    {
        $this->key = config('services.azure_speech.key');
        $this->region = config('services.azure_speech.region');
        $this->endpoint = "https://{$this->region}.tts.speech.microsoft.com/cognitiveservices/v1";
    }

    /**
     * 生成配音
     * @param string $text 文本内容
     * @param string $voiceName 音色名称
     * @param string $outputPath 输出文件路径
     * @return string 文件路径
     */
    public function synthesize(string $text, string $voiceName = 'zh-CN-XiaoxiaoNeural', string $outputPath = null): string
    {
        $outputPath = $outputPath ?? storage_path('app/tts/' . uniqid() . '.mp3');

        // SSML 格式
        $ssml = <<<XML
<speak version="1.0" xmlns="http://www.w3.org/2001/10/synthesis" xml:lang="zh-CN">
    <voice name="{$voiceName}">
        {$text}
    </voice>
</speak>
XML;

        $response = Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => $this->key,
            'Content-Type' => 'application/ssml+xml',
            'X-Microsoft-OutputFormat' => 'audio-48khz-192kbitrate-mono-mp3',
        ])->withBody($ssml, 'application/ssml+xml')
          ->post($this->endpoint);

        if (!$response->successful()) {
            Log::error('Azure TTS failed', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \Exception('Azure TTS 配音失败');
        }

        file_put_contents($outputPath, $response->body());

        return $outputPath;
    }

    /**
     * Azure TTS 常用中文音色
     */
    public static function voices(): array
    {
        return [
            'female_young' => 'zh-CN-XiaoxiaoNeural',     // 晓晓 - 年轻女声
            'female_gentle' => 'zh-CN-XiaohanNeural',     // 晓涵 - 温柔女声
            'female_sweet' => 'zh-CN-XiaoruiNeural',      // 晓睿 - 甜美女声
            'male_young' => 'zh-CN-YunxiNeural',          // 云希 - 年轻男声
            'male_deep' => 'zh-CN-YunyangNeural',         // 云扬 - 深沉男声
            'male_warm' => 'zh-CN-YunjianNeural',         // 云健 - 温暖男声
            'child' => 'zh-CN-XiaochenNeural',            // 晓辰 - 童声
            'elder' => 'zh-CN-YunyeNeural',               // 云野 - 老年男声
        ];
    }
}
