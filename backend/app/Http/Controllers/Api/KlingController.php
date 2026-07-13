<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\KlingConfig;
use Illuminate\Http\Request;

class KlingController extends Controller
{
    public function options()
    {
        return response()->json(KlingConfig::getOptions());
    }

    public function defaults()
    {
        return response()->json(KlingConfig::defaults());
    }

    /**
     * AI 分析故事内容，推荐最优可灵配置
     */
    public function recommend(Request $request)
    {
        $content = $request->input('content', '');
        $style = $request->input('style', 'realistic');
        $durationHint = $request->input('duration_hint'); // 用户期望的时长(秒)

        $charCount = mb_strlen($content);
        $sceneCount = max(1, substr_count($content, "\n") + 1);
        $hasAction = (bool)preg_match('/打|战斗|奔跑|追逐|枪|刀|爆炸|飞/i', $content);
        $hasDialogue = (bool)preg_match('/说|道|问|答|："|："|」/i', $content);

        // 根据故事长度推荐视频时长
        if ($charCount < 100) {
            $recDuration = 5;
            $durationReason = '故事较短，建议 5 秒短视频';
        } elseif ($charCount < 500) {
            $recDuration = 10;
            $durationReason = '故事适中，建议 10 秒';
        } elseif ($charCount < 1500) {
            $recDuration = 15;
            $durationReason = '故事内容丰富，可生成 15 秒';
        } else {
            $recDuration = 15;
            $durationReason = '长故事，建议拆分为多集，单集 15 秒';
        }

        // 根据风格推荐模型
        if ($style === 'realistic') {
            $recImageModel = 'kling-v3';
            $recVideoModel = 'kling-v2-6';
        } elseif ($style === 'anime') {
            $recImageModel = 'kling-v2-1';
            $recVideoModel = 'kling-v2-5-turbo';
        } else {
            $recImageModel = 'kling-v3';
            $recVideoModel = 'kling-v2-6';
        }

        // 推荐图片数量
        $recImageN = $sceneCount < 3 ? 3 : ($sceneCount > 5 ? 5 : $sceneCount);

        // 推荐模式
        $recVideoMode = $charCount > 500 ? 'pro' : 'std';

        // 动作场景推荐运镜
        $recCamera = $hasAction ? 'simple' : null;

        // 合理性校验
        $warnings = [];
        if ($durationHint && (int)$durationHint > $recDuration * 3) {
            $warnings[] = "⚠️ 你的故事长度仅 {$charCount} 字，但期望生成 {$durationHint} 秒视频。建议将时长设为 {$recDuration} 秒，或补充更详细的故事大纲。";
        }
        if ($sceneCount > 8) {
            $warnings[] = "📋 检测到 {$sceneCount} 个场景，建议拆分为多集处理，每集 3-5 个场景效果最佳。";
        }
        if ($charCount < 50) {
            $warnings[] = "💡 故事大纲较短（{$charCount} 字），AI 可发挥空间有限。建议补充更多场景描述和人物细节，获得更好的生成效果。";
        }
        if (!$hasDialogue && $charCount > 200) {
            $warnings[] = "🔇 未检测到对话内容，如需配音建议在故事中加入人物对话。";
        }

        return response()->json([
            'recommended' => [
                'image_model' => $recImageModel,
                'image_resolution' => $charCount > 500 ? '2k' : '1k',
                'image_aspect_ratio' => '9:16',
                'image_n' => $recImageN,
                'video_model' => $recVideoModel,
                'video_mode' => $recVideoMode,
                'video_duration' => (string)$recDuration,
                'video_aspect_ratio' => '9:16',
                'video_sound' => $hasDialogue ? 'on' : 'off',
                'camera_type' => $recCamera,
                'video_negative_prompt' => '',
            ],
            'analysis' => [
                'char_count' => $charCount,
                'scene_count' => $sceneCount,
                'has_action' => $hasAction,
                'has_dialogue' => $hasDialogue,
                'duration_reason' => $durationReason,
            ],
            'warnings' => $warnings,
        ]);
    }

    /**
     * 校验用户配置是否合理
     */
    public function validateConfig(Request $request)
    {
        $content = $request->input('content', '');
        $config = $request->input('config', []);
        $charCount = mb_strlen($content);
        $duration = (int)($config['video_duration'] ?? 5);

        $warnings = [];
        if ($charCount < 100 && $duration > 10) {
            $warnings[] = ['field' => 'video_duration', 'message' => "故事只有 {$charCount} 字，建议将时长从 {$duration} 秒减少到 5-10 秒"];
        }
        if ($charCount > 2000 && $duration < 10) {
            $warnings[] = ['field' => 'video_duration', 'message' => "长故事建议增加时长到 15 秒，或拆分为多集"];
        }
        if (($config['video_mode'] ?? '') === '4k' && $charCount < 300) {
            $warnings[] = ['field' => 'video_mode', 'message' => '4K 模式成本较高，短故事建议使用专业(1080P)模式'];
        }
        if (($config['image_n'] ?? 3) > 5) {
            $warnings[] = ['field' => 'image_n', 'message' => '建议图片数量不超过 5 张'];
        }
        if (($config['video_sound'] ?? 'off') === 'on' && !preg_match('/说|道|问|答|："|」/i', $content)) {
            $warnings[] = ['field' => 'video_sound', 'message' => '故事中未检测到对话，建议关闭声音或添加对话内容'];
        }

        return response()->json(['warnings' => $warnings, 'valid' => empty($warnings)]);
    }
}
