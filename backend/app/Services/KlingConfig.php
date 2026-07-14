<?php

namespace App\Services;

class KlingConfig
{
    // ============================================
    // 图片模型 — 精确功能/定价/分辨率 (可灵官方定价表)
    // ============================================
    const IMAGE_MODELS = [
        'kling-v3' => [
            'name' => 'Kling Image 3.0',
            'desc' => '文生图 / 图生图',
            'resolutions' => ['1k', '2k'],
            'supports_4k' => false,
            'pricing' => ['t2i' => 8, 'i2i' => 8],
            'max_ref_images' => 1, 'max_n' => 9,
        ],
        'kling-v3-omni' => [
            'name' => 'Kling Image 3.0 Omni',
            'desc' => '文生图 / 图生图',
            'resolutions' => ['1k', '2k', '4k'],
            'supports_4k' => true,
            'pricing' => ['t2i' => 8, 'i2i' => 8, 't2i_4k' => 16, 'i2i_4k' => 16],
            'max_ref_images' => 1, 'max_n' => 9,
        ],
        'kling-image-o1' => [
            'name' => 'Kling Image O1',
            'desc' => '文生图 / 图生图',
            'resolutions' => ['1k', '2k'],
            'supports_4k' => false,
            'pricing' => ['t2i' => 8, 'i2i' => 8],
            'max_ref_images' => 1, 'max_n' => 9,
        ],
        'kling-v2-1' => [
            'name' => 'Kling Image 2.1',
            'desc' => '文生图 / 图生图 / 多图参考',
            'resolutions' => ['1k', '2k'],
            'supports_4k' => false,
            'pricing' => ['t2i' => 4, 'i2i' => 8, 'multi' => 16],
            'max_ref_images' => 3, 'max_n' => 9,
        ],
        'kling-v2-new' => [
            'name' => 'Kling Image 2.1 New',
            'desc' => '图生图',
            'resolutions' => ['1k'],
            'supports_4k' => false,
            'pricing' => ['i2i' => 8],
            'max_ref_images' => 1, 'max_n' => 9,
        ],
        'kling-v2' => [
            'name' => 'Kling Image 2.0',
            'desc' => '文生图 / 图生图 / 多图参考',
            'resolutions' => ['1k', '2k'],
            'supports_4k' => false,
            'pricing' => ['t2i' => 4, 'i2i' => 8, 'multi' => 16],
            'max_ref_images' => 3, 'max_n' => 9,
            'ref_resolution_limit' => '1k',
        ],
        'kling-v1-5' => [
            'name' => 'Kling Image 1.5',
            'desc' => '文生图 / 图生图',
            'resolutions' => ['1k'],
            'supports_4k' => false,
            'pricing' => ['t2i' => 4, 'i2i' => 8],
            'max_ref_images' => 1, 'max_n' => 9,
        ],
        'kling-v1' => [
            'name' => 'Kling Image 1.0',
            'desc' => '文生图 / 图生图',
            'resolutions' => ['1k'],
            'supports_4k' => false,
            'pricing' => ['t2i' => 2, 'i2i' => 2],
            'max_ref_images' => 1, 'max_n' => 9,
        ],
    ];

    // ============================================
    // 视频模型 (保持不变)
    // ============================================
    const VIDEO_MODELS = [
        'kling-v3-turbo' => [
            'name' => 'Kling 3.0 Turbo',
            'modes' => ['std', 'pro'],
            'durations' => ['5','6','7','8','9','10','11','12','13','14','15'],
            'supports' => ['image2video', 'text2video', 'sound'],
            'pricing' => ['std' => 1, 'pro' => 2],
            'default_duration' => '10',
        ],
        'kling-v3' => [
            'name' => 'Kling 3.0',
            'modes' => ['std', 'pro', '4k'],
            'durations' => ['5','6','7','8','9','10','11','12','13','14','15'],
            'supports' => ['image2video', 'text2video', 'sound', 'motion_control'],
            'pricing' => ['std' => 1, 'pro' => 2, '4k' => 6],
            'motion_pricing' => ['std' => 1, 'pro' => 2],
            'motion_requires_image' => true,
            'motion_no_4k' => true,
            'default_duration' => '10',
        ],
    ];

    const IMAGE_RESOLUTIONS = ['1k' => '1K 标清', '2k' => '2K 高清', '4k' => '4K 超清'];
    const IMAGE_ASPECT_RATIOS = ['16:9' => '16:9 横屏', '9:16' => '9:16 竖屏(抖音)', '1:1' => '1:1 方形', '4:3' => '4:3 标准', '3:4' => '3:4 竖屏', '3:2' => '3:2 宽屏', '2:3' => '2:3 竖长', '2:1' => '2:1 超宽', '1:2' => '1:2 超长', '1:9' => '1:9 长条', 'auto' => '自动'];
    const VIDEO_MODES = ['std' => '标准 720P', 'pro' => '专业 1080P', '4k' => '4K 超清'];
    const VIDEO_DURATIONS = ['3' => '3秒', '4' => '4秒', '5' => '5秒', '6' => '6秒', '7' => '7秒', '8' => '8秒', '9' => '9秒', '10' => '10秒', '11' => '11秒', '12' => '12秒', '13' => '13秒', '14' => '14秒', '15' => '15秒'];
    const CAMERA_TYPES = ['simple' => '自定义运镜', 'down_back' => '下后拉远', 'forward_up' => '前上推进', 'right_turn_forward' => '右转前进', 'left_turn_forward' => '左转前进'];
    const CAMERA_CONFIGS = ['horizontal' => ['label' => '水平移动', 'min' => -10, 'max' => 10, 'step' => 0.5], 'vertical' => ['label' => '垂直移动', 'min' => -10, 'max' => 10, 'step' => 0.5], 'pan' => ['label' => '水平摇镜', 'min' => -10, 'max' => 10, 'step' => 0.5], 'tilt' => ['label' => '垂直倾斜', 'min' => -10, 'max' => 10, 'step' => 0.5], 'roll' => ['label' => '旋转', 'min' => -10, 'max' => 10, 'step' => 0.5], 'zoom' => ['label' => '缩放', 'min' => -10, 'max' => 10, 'step' => 0.5]];

    const PRESETS = [
        'short_drama' => ['name' => '真人短剧 (推荐)', 'image_model' => 'kling-v3', 'image_resolution' => '2k', 'image_aspect_ratio' => '9:16', 'video_model' => 'kling-v3-turbo', 'video_mode' => 'pro', 'video_duration' => '10', 'video_sound' => 'on'],
        'cinematic' => ['name' => '电影质感', 'image_model' => 'kling-v3-omni', 'image_resolution' => '2k', 'image_aspect_ratio' => '16:9', 'video_model' => 'kling-v3-turbo', 'video_mode' => 'pro', 'video_duration' => '10', 'video_sound' => 'on'],
        'fast_preview' => ['name' => '快速预览', 'image_model' => 'kling-v2-1', 'image_resolution' => '1k', 'image_aspect_ratio' => '9:16', 'video_model' => 'kling-v3-turbo', 'video_mode' => 'std', 'video_duration' => '10', 'video_sound' => 'on'],
    ];

    /** 计算图片生成积分 */
    public static function calcImageCost(string $model, string $resolution, int $imageCount, int $refImageCount = 0): int
    {
        $m = self::IMAGE_MODELS[$model] ?? null;
        if (!$m) return $imageCount * 8;

        $p = $m['pricing'];
        if ($refImageCount >= 2 && isset($p['multi'])) {
            $unit = $p['multi'];
        } elseif ($refImageCount >= 1 && isset($p['i2i'])) {
            $unit = $p['i2i'];
        } elseif (isset($p['t2i'])) {
            $unit = $p['t2i'];
        } else {
            $unit = reset($p);
        }

        if ($resolution === '4k' && $m['supports_4k']) {
            $unit = $refImageCount >= 1 ? ($p['i2i_4k'] ?? $unit * 2) : ($p['t2i_4k'] ?? $unit * 2);
        }

        return $unit * $imageCount;
    }

    /** 计算视频生成积分 */
    public static function calcVideoCost(string $model, string $mode, int $duration): int
    {
        $m = self::VIDEO_MODELS[$model] ?? null;
        if (!$m || !isset($m['pricing'])) return $duration * 2;
        $rate = $m['pricing'][$mode] ?? 2;
        return $rate * $duration;
    }

    /** 获取模型支持的分辨率列表 */
    public static function getResolutionsForModel(string $model): array
    {
        $m = self::IMAGE_MODELS[$model] ?? null;
        if (!$m) return ['1k' => '1K 标清', '2k' => '2K 高清', '4k' => '4K 超清'];
        $list = [];
        foreach ($m['resolutions'] as $r) $list[$r] = self::IMAGE_RESOLUTIONS[$r] ?? $r;
        if ($m['supports_4k']) $list['4k'] = '4K 超清';
        return $list;
    }

    public static function getOptions(): array
    {
        return [
            'image_models' => self::IMAGE_MODELS,
            'video_models' => self::VIDEO_MODELS,
            'image_resolutions' => self::IMAGE_RESOLUTIONS,
            'image_aspect_ratios' => self::IMAGE_ASPECT_RATIOS,
            'video_modes' => self::VIDEO_MODES,
            'video_durations' => self::VIDEO_DURATIONS,
            'camera_types' => self::CAMERA_TYPES,
            'camera_configs' => self::CAMERA_CONFIGS,
            'presets' => self::PRESETS,
        ];
    }

    public static function defaults(): array
    {
        $p = self::PRESETS['short_drama'];
        return [
            'image_model' => $p['image_model'], 'image_resolution' => $p['image_resolution'],
            'image_aspect_ratio' => $p['image_aspect_ratio'], 'image_n' => 1,
            'image_negative_prompt' => '',
            'video_model' => $p['video_model'], 'video_mode' => $p['video_mode'],
            'video_duration' => $p['video_duration'], 'video_sound' => $p['video_sound'],
            'video_negative_prompt' => '', 'video_aspect_ratio' => '9:16',
            'camera_type' => null, 'camera_config' => null, 'cfg_scale' => 0.5,
        ];
    }
}
