<?php

namespace App\Services;

/**
 * 可灵AI 完整模型和参数配置
 * 基于 2026年7月 最新文档: https://klingai.com/document-api/
 */
class KlingConfig
{
    // ============================================
    // 图片生成模型
    // ============================================
    const IMAGE_MODELS = [
        'kling-v1' => [
            'name' => 'Kling V1',
            'description' => '基础版本，速度快',
            'resolutions' => ['1k'],
            'aspect_ratios' => ['16:9', '9:16', '1:1', '4:3', '3:4', '3:2', '2:3'],
            'supports' => ['image_reference', 'image_fidelity'],
            'max_n' => 9,
        ],
        'kling-v1-5' => [
            'name' => 'Kling V1.5',
            'description' => '支持人脸参考和主体参考',
            'resolutions' => ['1k', '2k'],
            'aspect_ratios' => ['16:9', '9:16', '1:1', '4:3', '3:4', '3:2', '2:3'],
            'supports' => ['image_reference', 'image_fidelity', 'human_fidelity'],
            'max_n' => 9,
        ],
        'kling-v2' => [
            'name' => 'Kling V2',
            'description' => '画质提升，细节更丰富',
            'resolutions' => ['1k', '2k'],
            'aspect_ratios' => ['16:9', '9:16', '1:1', '4:3', '3:4', '3:2', '2:3'],
            'supports' => [],
            'max_n' => 9,
        ],
        'kling-v2-new' => [
            'name' => 'Kling V2 New',
            'description' => 'V2 升级版',
            'resolutions' => ['1k', '2k'],
            'aspect_ratios' => ['16:9', '9:16', '1:1', '4:3', '3:4', '3:2', '2:3', '2:1', '1:2', '1:9'],
            'supports' => [],
            'max_n' => 9,
        ],
        'kling-v2-1' => [
            'name' => 'Kling V2.1',
            'description' => 'V2 最新稳定版，推荐使用',
            'resolutions' => ['1k', '2k'],
            'aspect_ratios' => ['16:9', '9:16', '1:1', '4:3', '3:4', '3:2', '2:3', '2:1', '1:2', '1:9'],
            'supports' => [],
            'max_n' => 9,
        ],
        'kling-v3' => [
            'name' => 'Kling V3',
            'description' => '最新旗舰，真人写实质感最强',
            'resolutions' => ['1k', '2k', '4k'],
            'aspect_ratios' => ['16:9', '9:16', '1:1', '4:3', '3:4', '3:2', '2:3', '2:1', '1:2', '1:9'],
            'supports' => [],
            'max_n' => 9,
        ],
        'kling-v3-omni' => [
            'name' => 'Kling V3 Omni',
            'description' => '全能版，支持元素/系列/多参考图',
            'resolutions' => ['1k', '2k'],
            'aspect_ratios' => ['auto', '16:9', '9:16', '1:1', '4:3', '3:4', '3:2', '2:3'],
            'supports' => ['element_list', 'image_list', 'series'],
            'max_n' => 9,
        ],
        'kling-image-o1' => [
            'name' => 'Kling Image O1',
            'description' => 'O1 专业图片模型，支持4K',
            'resolutions' => ['1k', '2k', '4k'],
            'aspect_ratios' => ['auto', '16:9', '9:16', '1:1', '4:3', '3:4', '3:2', '2:3'],
            'supports' => ['element_list', 'image_list', 'series'],
            'max_n' => 9,
        ],
    ];

    // ============================================
    // 视频生成模型 (图生视频/文生视频通用)
    // ============================================
    const VIDEO_MODELS = [
        'kling-v1' => [
            'name' => 'Kling V1',
            'description' => '基础版本',
            'modes' => ['std', 'pro'],
            'durations' => ['5', '10'],
            'supports' => ['image2video'],
        ],
        'kling-v1-5' => [
            'name' => 'Kling V1.5',
            'description' => 'V1 增强版',
            'modes' => ['std', 'pro'],
            'durations' => ['5', '10'],
            'supports' => ['image2video'],
        ],
        'kling-v1-6' => [
            'name' => 'Kling V1.6',
            'description' => 'V1 最新版',
            'modes' => ['std', 'pro'],
            'durations' => ['5', '10'],
            'supports' => ['image2video'],
        ],
        'kling-v2-master' => [
            'name' => 'Kling V2 Master',
            'description' => 'V2 旗舰版',
            'modes' => ['std', 'pro'],
            'durations' => ['5', '10'],
            'supports' => ['image2video', 'text2video'],
        ],
        'kling-v2-1' => [
            'name' => 'Kling V2.1',
            'description' => 'V2 稳定版',
            'modes' => ['std', 'pro'],
            'durations' => ['5', '10'],
            'supports' => ['image2video', 'text2video'],
        ],
        'kling-v2-1-master' => [
            'name' => 'Kling V2.1 Master',
            'description' => 'V2.1 旗舰版',
            'modes' => ['std', 'pro'],
            'durations' => ['5', '10'],
            'supports' => ['image2video', 'text2video'],
        ],
        'kling-v2-5-turbo' => [
            'name' => 'Kling V2.5 Turbo',
            'description' => '快速版，性价比高',
            'modes' => ['std', 'pro'],
            'durations' => ['5', '10'],
            'supports' => ['image2video', 'text2video'],
        ],
        'kling-v2-6' => [
            'name' => 'Kling V2.6',
            'description' => 'V2 终极版，支持运镜控制',
            'modes' => ['std', 'pro'],
            'durations' => ['3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15'],
            'supports' => ['image2video', 'camera_control', 'image_tail', 'watermark'],
        ],
        'kling-v3' => [
            'name' => 'Kling V3',
            'description' => '最新旗舰，支持文生视频+多镜头',
            'modes' => ['std', 'pro', '4k'],
            'durations' => ['5', '10', '15'],
            'aspect_ratios' => ['16:9', '9:16', '1:1', '4:3', '3:4'],
            'supports' => ['image2video', 'text2video', 'multi_shot', 'sound', 'aspect_ratio'],
        ],
        'kling-v3-omni' => [
            'name' => 'Kling V3 Omni',
            'description' => '全能旗舰，图/文/音/多镜头全支持',
            'modes' => ['std', 'pro', '4k'],
            'durations' => ['5', '10', '15'],
            'aspect_ratios' => ['16:9', '9:16', '1:1', '4:3', '3:4'],
            'supports' => ['image2video', 'text2video', 'multi_shot', 'sound', 'aspect_ratio', 'element_list', 'voice_list'],
        ],
        'kling-video-o1' => [
            'name' => 'Kling Video O1',
            'description' => 'O1 专业视频模型，支持图片列表+多镜头',
            'modes' => ['pro', '4k'],
            'durations' => ['5', '10', '15'],
            'aspect_ratios' => ['16:9', '9:16', '1:1', '4:3', '3:4'],
            'supports' => ['image2video', 'multi_shot', 'image_list', 'aspect_ratio'],
        ],
    ];

    // ============================================
    // 图片参数选项
    // ============================================
    const IMAGE_RESOLUTIONS = [
        '1k' => '1K 标清',
        '2k' => '2K 高清',
        '4k' => '4K 超清',
    ];

    const IMAGE_ASPECT_RATIOS = [
        '16:9' => '16:9 横屏',
        '9:16' => '9:16 竖屏(抖音)',
        '1:1' => '1:1 正方形',
        '4:3' => '4:3 标准',
        '3:4' => '3:4 竖屏',
        '3:2' => '3:2 宽屏',
        '2:3' => '2:3 竖长',
        '2:1' => '2:1 超宽',
        '1:2' => '1:2 超长',
        '1:9' => '1:9 长条',
        'auto' => '自动',
    ];

    // ============================================
    // 视频参数选项
    // ============================================
    const VIDEO_MODES = [
        'std' => '标准 (720P)',
        'pro' => '专业 (1080P)',
        '4k' => '4K 超清',
    ];

    const VIDEO_DURATIONS = [
        '3' => '3秒',
        '4' => '4秒',
        '5' => '5秒 (推荐)',
        '6' => '6秒',
        '7' => '7秒',
        '8' => '8秒',
        '9' => '9秒',
        '10' => '10秒',
        '11' => '11秒',
        '12' => '12秒',
        '13' => '13秒',
        '14' => '14秒',
        '15' => '15秒',
    ];

    const CAMERA_TYPES = [
        'simple' => '自定义运镜',
        'down_back' => '下后拉远',
        'forward_up' => '前上推进',
        'right_turn_forward' => '右转前进',
        'left_turn_forward' => '左转前进',
    ];

    const CAMERA_CONFIGS = [
        'horizontal' => ['label' => '水平移动', 'min' => -10, 'max' => 10, 'step' => 0.5],
        'vertical' => ['label' => '垂直移动', 'min' => -10, 'max' => 10, 'step' => 0.5],
        'pan' => ['label' => '水平摇镜', 'min' => -10, 'max' => 10, 'step' => 0.5],
        'tilt' => ['label' => '垂直倾斜', 'min' => -10, 'max' => 10, 'step' => 0.5],
        'roll' => ['label' => '旋转', 'min' => -10, 'max' => 10, 'step' => 0.5],
        'zoom' => ['label' => '缩放', 'min' => -10, 'max' => 10, 'step' => 0.5],
    ];

    // ============================================
    // 快捷预设 (一键配置)
    // ============================================
    const PRESETS = [
        'short_drama' => [
            'name' => '真人短剧 (推荐)',
            'image_model' => 'kling-v3',
            'image_resolution' => '2k',
            'image_aspect_ratio' => '9:16',
            'video_model' => 'kling-v2-6',
            'video_mode' => 'pro',
            'video_duration' => '5',
            'video_sound' => 'off',
        ],
        'cinematic' => [
            'name' => '电影质感',
            'image_model' => 'kling-v3',
            'image_resolution' => '4k',
            'image_aspect_ratio' => '16:9',
            'video_model' => 'kling-v3',
            'video_mode' => '4k',
            'video_duration' => '10',
            'video_sound' => 'off',
        ],
        'fast_preview' => [
            'name' => '快速预览',
            'image_model' => 'kling-v2-1',
            'image_resolution' => '1k',
            'image_aspect_ratio' => '9:16',
            'video_model' => 'kling-v2-5-turbo',
            'video_mode' => 'std',
            'video_duration' => '5',
            'video_sound' => 'off',
        ],
    ];

    /**
     * 获取所有选项配置 (给前端 API)
     */
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

    /**
     * 默认配置
     */
    public static function defaults(): array
    {
        $preset = self::PRESETS['short_drama'];

        return [
            'image_model' => $preset['image_model'],
            'image_resolution' => $preset['image_resolution'],
            'image_aspect_ratio' => $preset['image_aspect_ratio'],
            'image_n' => 1,
            'image_negative_prompt' => '',
            'video_model' => $preset['video_model'],
            'video_mode' => $preset['video_mode'],
            'video_duration' => $preset['video_duration'],
            'video_sound' => $preset['video_sound'],
            'video_negative_prompt' => '',
            'video_aspect_ratio' => '9:16',
            'camera_type' => null,
            'camera_config' => null,
            'cfg_scale' => 0.5,
        ];
    }
}
