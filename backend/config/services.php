<?php

return [
    // 可灵AI
    'kling' => [
        'api_key' => env('KLING_API_KEY'),
        'api_secret' => env('KLING_API_SECRET'),
        'api_base' => env('KLING_API_BASE', 'https://api.klingai.com'),
    ],

    // CosyVoice TTS (阿里云百炼)
    'cosyvoice' => [
        'api_key' => env('COSYVOICE_API_KEY'),
        'api_base' => env('COSYVOICE_API_BASE', 'https://dashscope.aliyuncs.com/api/v1'),
    ],

    // Azure TTS (备选，已替换为CosyVoice)
    'azure_speech' => [
        'key' => env('AZURE_SPEECH_KEY'),
        'region' => env('AZURE_SPEECH_REGION', 'eastasia'),
    ],

    // 阿里云 OSS
    'oss' => [
        'access_key_id' => env('OSS_ACCESS_KEY_ID'),
        'access_key_secret' => env('OSS_ACCESS_KEY_SECRET'),
        'bucket' => env('OSS_BUCKET'),
        'endpoint' => env('OSS_ENDPOINT'),
    ],

    // 积分系统
    'credits' => [
        'default' => (int) env('DEFAULT_CREDITS', 100),
        'cost_per_generation' => (int) env('COST_PER_GENERATION', 50),
    ],
];
