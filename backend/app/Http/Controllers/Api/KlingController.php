<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class KlingController extends Controller
{
    public function recommend(Request $request)
    {
        $content = $request->content ?? '';
        $autoConfigured = $request->auto_configured ?? false;
        $charCount = mb_strlen($content);
        $sceneCount = max(1, substr_count($content, "\n") + 1);

        $recommended = $autoConfigured ? null : [
            'video_model' => 'kling-v3-turbo',
            'video_duration' => $charCount > 500 ? '10' : ($charCount > 200 ? '8' : '5'),
            'video_aspect_ratio' => '9:16',
            'image_resolution' => $charCount > 1000 ? '2k' : '1k',
        ];

        $warnings = [];
        if ($charCount < 50) $warnings[] = '故事内容较短，建议补充更多场景细节';
        if ($autoConfigured) $warnings[] = '当前配置已根据参考视频自动设置，AI不再调整，仅提供参考建议';

        return response()->json([
            'recommended' => $recommended,
            'analysis' => ['char_count' => $charCount, 'scene_count' => $sceneCount],
            'warnings' => $warnings,
        ]);
    }
}
