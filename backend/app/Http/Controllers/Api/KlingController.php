<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class KlingController extends Controller
{
    public function recommend(Request $request)
    {
        $content = $request->content ?? '';
        $charCount = mb_strlen($content);
        $sceneCount = max(1, substr_count($content, "\n") + 1);

        return response()->json([
            'recommended' => [
                'video_model' => 'kling-v3-turbo',
                'video_duration' => $charCount > 500 ? '10' : ($charCount > 200 ? '8' : '5'),
                'video_aspect_ratio' => '9:16',
                'image_resolution' => $charCount > 1000 ? '2k' : '1k',
            ],
            'analysis' => [
                'char_count' => $charCount,
                'scene_count' => $sceneCount,
            ],
            'warnings' => $charCount < 50 ? ['故事内容较短，建议补充更多场景细节'] : [],
        ]);
    }
}
