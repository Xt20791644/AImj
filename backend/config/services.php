<?php

return [
    // 可灵AI
    'kling' => [
        'api_key' => env('KLING_API_KEY'),
        'api_secret' => env('KLING_API_SECRET'),
        'api_base' => env('KLING_API_BASE', 'https://api.klingai.com'),
    ],

    // Azure TTS
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
