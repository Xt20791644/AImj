<?php

namespace App\Services;

/**
 * 提示词优化器 — 硬性规则：所有发给可灵的内容必须经过此优化
 * 目标：电影级真实感、高质量输出、绝不直接使用用户原始输入
 */
class PromptOptimizerService
{
    /**
     * 核心入口：优化提示词
     * 所有可灵 API 调用前必须调用此方法
     */
    public function optimize(string $userInput, array $config = [], string $sceneContext = ''): string
    {
        $style = $config['style'] ?? 'realistic';
        $title = $config['title'] ?? '';
        $negativePrompt = $config['negative_prompt'] ?? '';

        // 1. 风格基础
        $styleBase = $this->getStyleBase($style);

        // 2. 画质增强
        $quality = $this->getQualityEnhancement();

        // 3. 场景上下文
        $scenePrompt = $sceneContext ? "场景描述：{$sceneContext}。" : '';

        // 4. 角色描述
        $characterPrompt = '';
        if (!empty($config['characters'])) {
            $characterPrompt = '角色：' . implode('，', array_map(function($c) {
                return "{$c['name']}（{$c['gender']}，{$c['age']}岁，{$c['trait']}）";
            }, $config['characters'])) . '。';
        }

        // 5. 构建完整提示词
        $prompt = "{$title}。{$userInput}。{$scenePrompt}{$characterPrompt}{$styleBase}。{$quality}。";

        // 6. 添加电影级运镜和光线描述
        $prompt .= $this->getCinematography();

        // 7. 负面提示词
        $defaultNegative = '模糊，变形，低画质，AI感，塑料质感，手指畸形，多手指，画面抖动，噪点，过曝，曝光不足，卡通风格，动漫风格，水印，文字，logo';
        $finalNegative = $negativePrompt ? "{$defaultNegative}，{$negativePrompt}" : $defaultNegative;
        $prompt .= " 避免：{$finalNegative}。";

        return $prompt;
    }

    /**
     * 优化图片生成的参考图提示词
     */
    public function optimizeImagePrompt(string $userInput, array $config = []): string
    {
        $style = $config['style'] ?? 'realistic';
        $styleBase = $this->getStyleBase($style);

        $prompt = "{$userInput}。{$styleBase}。" . $this->getQualityEnhancement() . '。';
        $prompt .= "精细面部特征，自然表情，真实皮肤纹理，柔和自然光线，电影级构图，专业摄影灯光。";
        $prompt .= " 避免：模糊，变形，多手指，低画质，卡通风格，动漫风格，AI塑料感。";

        return $prompt;
    }

    /**
     * 优化视频生成的提示词（含运镜和动态描述）
     * 爆款复刻模式：检测到 ref_video 时，构造场景描述而非操作指令
     */
    public function optimizeVideoPrompt(string $userInput, array $config = []): string
    {
        $duration = $config['duration'] ?? 10;

        // 爆款复刻模式：用参考视频风格生成新内容，不依赖用户的空洞指令
        if (!empty($config['ref_video'])) {
            $styleBase = $this->getStyleBase($config['style'] ?? 'realistic');
            $motion = match(true) {
                $duration <= 5 => '轻微自然动作，呼吸感微动',
                $duration <= 10 => '流畅自然的表演，人物调度自然，舒缓的运镜节奏',
                default => '丰富的场景调度，多层次运镜变化，戏剧化的光影运动'
            };
            return "基于 <<<video_1>>> 的视觉风格、运镜手法、色彩调性和叙事节奏，"
                . "生成一段全新的中国短剧视频，保持完全一致的制作水准和画面质感。"
                . "内容为一段精彩的短视频剧情片段。"
                . "{$styleBase}。{$motion}。"
                . "避免：模糊，变形，低画质，卡通，动漫，AI塑料感，画面扭曲，水印，文字，logo。";
        }

        $prompt = $this->optimize($userInput, $config);

        // 视频特定的动态描述
        $motionHints = match(true) {
            $duration <= 5 => '轻微自然动作，呼吸感微动，眼神自然流转',
            $duration <= 10 => '流畅自然的表演，合理的人物调度，舒缓的运镜节奏',
            default => '丰富的场景调度，多层次运镜变化，戏剧化的光影运动'
        };

        $prompt .= " 动态描述：{$motionHints}。视频流畅无卡顿，人物动作自然无违和感。";

        return $prompt;
    }

    // ============================================
    // 私有方法
    // ============================================

    private function getStyleBase(string $style): string
    {
        return match($style) {
            'realistic' => '真人写实电影质感，专业电影级别摄影灯光，超清4K画质，真实人物表情自然细腻，肌肤纹理清晰可见，电影级色彩调色，浅景深虚化背景，奥斯卡级别光影效果',
            'anime' => '日系动画电影风格，新海诚级别细腻手绘质感，柔和自然光渲染，明亮温暖色调，精品场景细节刻画，吉卜力电影质感',
            '3d' => '3D动画电影质感，Pixar/迪士尼级别渲染品质，精细材质贴图和纹理，真实光线追踪渲染，流畅自然的角色动画表现',
            'cyberpunk' => '赛博朋克风格，霓虹灯光渲染，未来都市质感，高对比度光影，电影级科幻氛围',
            default => '真人写实电影质感，专业摄影灯光，超清4K画质，自然真实'
        };
    }

    private function getQualityEnhancement(): string
    {
        return '8K超精细画质，HDR高动态范围，电影级后期调色，专业摄影棚灯光，真实物理材质渲染，微距细节清晰可见';
    }

    private function getCinematography(): string
    {
        $angles = ['中景平稳构图', '近景浅景深特写', '全景广角电影构图', '45度角侧面拍摄', '低角度仰拍'];
        return $angles[array_rand($angles)] . '，自然光线柔和过渡，背景虚化自然不突兀';
    }
}
