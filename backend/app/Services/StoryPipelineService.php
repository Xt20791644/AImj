<?php

namespace App\Services;

/**
 * AI短剧文本创作管线
 * 用户输入简短故事 → AI扩展为完整剧本 → 逐镜头生成专业提示词 → 再发给可灵
 */
class StoryPipelineService
{
    /**
     * 完整文本管线
     * @return array {script, scenes, characters, prompts}
     */
    public function process(string $title, string $content, string $style = 'realistic'): array
    {
        // Step 1: 扩展故事为完整剧本
        $script = $this->expandScript($title, $content, $style);

        // Step 2: 拆分为分镜场景
        $scenes = $this->splitScenes($script, $content);

        // Step 3: 提取角色信息
        $characters = $this->extractCharacters($content);

        // Step 4: 生成每镜头的专业可灵提示词
        $prompts = $this->buildPrompts($title, $scenes, $characters, $style);

        return [
            'script' => $script,
            'scenes' => $scenes,
            'characters' => $characters,
            'prompts' => $prompts,
        ];
    }

    /**
     * 将用户简短输入扩展为完整剧本
     */
    private function expandScript(string $title, string $content, string $style): string
    {
        $lines = array_filter(explode("\n", $content), fn($l) => trim($l) !== '');
        $sceneCount = count($lines);

        $opening = "【短剧】《{$title}》\n";
        $opening .= "风格：{$style}\n\n";

        $script = $opening;
        $script .= "━━━━━ 序幕 ━━━━━\n";
        $script .= "【全景·推镜】" . trim($lines[0] ?? $content) . "\n\n";

        for ($i = 1; $i < $sceneCount; $i++) {
            $sceneNum = $i;
            $line = trim($lines[$i]);
            if (empty($line)) continue;

            $cameraTypes = ['中景·平视', '近景·特写', '全景·广角', '中景·跟拍', '近景·侧拍', '特写·表情'];
            $camera = $cameraTypes[$i % count($cameraTypes)];

            $script .= "━━━━━ 第{$sceneNum}场 ━━━━━\n";
            $script .= "【{$camera}】{$line}\n\n";
        }

        $script .= "━━━━━ 尾声 ━━━━━\n";
        $script .= "【全景·拉远】画面渐暗，故事落幕。\n";

        return $script;
    }

    /**
     * 拆分为独立分镜场景
     */
    private function splitScenes(string $script, string $content): array
    {
        $lines = array_values(array_filter(explode("\n", $content), fn($l) => trim($l) !== ''));
        $scenes = [];

        foreach ($lines as $i => $line) {
            $cameraTypes = ['中景平视', '近景特写', '全景广角', '中景跟拍', '近景侧拍'];
            $moodTypes = ['平静', '紧张', '温馨', '悲伤', '激动', '悬疑'];

            $scenes[] = [
                'id' => $i + 1,
                'description' => trim($line),
                'camera' => $cameraTypes[$i % count($cameraTypes)],
                'mood' => $moodTypes[$i % count($moodTypes)],
                'duration' => $this->estimateDuration($line),
            ];
        }

        return $scenes;
    }

    /**
     * 提取角色
     */
    private function extractCharacters(string $content): array
    {
        $characters = [];
        // 提取中文名字（姓+1-2字名）
        preg_match_all('/[\x{4e00}-\x{9fa5}]{2,3}(?=[，。、；：！？\s]|$)/u', $content, $matches);
        $names = array_unique($matches[0] ?? []);

        // 过滤掉非人名的常见词
        $notNames = ['一个', '一次', '一天', '一种', '这个', '那个', '什么', '怎么', '为什么', '因为', '所以',
            '可以', '没有', '已经', '还是', '但是', '如果', '虽然', '而且', '然后', '最后', '忽然', '突然',
            '发现', '看到', '听到', '感到', '觉得', '知道', '开始', '结束', '出来', '进去', '过来', '过去',
            '起来', '下来', '上来', '下去', '走进', '走出', '坐在', '站在', '来到', '回到', '转身', '抬头',
            '低头', '微笑', '看着', '说着', '走着', '想着', '停下', '继续', '离开', '过来', '走去',
        ];

        $names = array_filter($names, fn($n) => !in_array($n, $notNames) && mb_strlen($n) >= 2);
        $names = array_slice($names, 0, 5);

        foreach ($names as $i => $name) {
            $genders = ['男', '女'];
            $ages = [25, 30, 22, 35, 28, 40, 18, 45];
            $traits = ['沉稳内敛', '活泼开朗', '温柔善良', '冷酷果断', '神秘莫测', '阳光自信'];

            $characters[] = [
                'name' => $name,
                'gender' => $genders[$i % 2],
                'age' => $ages[$i % count($ages)],
                'trait' => $traits[$i % count($traits)],
                'role' => $i === 0 ? '主角' : '配角',
            ];
        }

        if (empty($characters)) {
            $characters[] = ['name' => '主角', 'gender' => '男', 'age' => 28, 'trait' => '沉稳内敛', 'role' => '主角'];
        }

        return $characters;
    }

    /**
     * 生成每场景的专业可灵提示词
     */
    public function buildPrompts(string $title, array $scenes, array $characters, string $style): array
    {
        $prompts = [];
        $characterDesc = '';
        foreach ($characters as $c) {
            $characterDesc .= "{$c['name']}（{$c['gender']}，{$c['age']}岁，{$c['trait']}），";
        }

        $styleDesc = match($style) {
            'realistic' => '真人写实电影质感，专业电影级别摄影灯光，超清4K画质，真实人物表情自然细腻，肌肤纹理清晰可见，电影级色彩调色，浅景深虚化背景，奥斯卡级别光影效果',
            'anime' => '日系动画电影风格，新海诚级别细腻手绘质感，柔和自然光渲染，明亮温暖色调，精品场景细节刻画，吉卜力电影质感',
            '3d' => '3D动画电影质感，Pixar/迪士尼级别渲染品质，精细材质贴图和纹理，真实光线追踪渲染，流畅自然的角色动画表现',
            default => '真人写实电影质感，专业摄影灯光，超清4K画质，自然真实'
        };

        foreach ($scenes as $scene) {
            $cameraDesc = match($scene['camera']) {
                '中景平视' => '中景镜头，水平视角，稳定构图，人物居中',
                '近景特写' => '近景特写镜头，浅景深虚化背景，聚焦人物表情和眼神',
                '全景广角' => '全景广角镜头，展现完整场景和环境氛围，电影级宽银幕构图',
                '中景跟拍' => '中景跟随镜头，轻微手持晃动感，纪录片真实风格',
                '近景侧拍' => '近景侧面拍摄，45度角，突出人物轮廓和光影层次',
                default => '中景镜头，平稳构图'
            };

            $moodDesc = match($scene['mood']) {
                '平静' => '宁静氛围，柔和自然光，温暖色调',
                '紧张' => '紧张氛围，冷色调，高对比度光影',
                '温馨' => '温馨氛围，暖金色光线，柔焦效果',
                '悲伤' => '悲伤氛围，灰冷色调，柔和散射光',
                '激动' => '激烈氛围，动态模糊，高饱和度色彩',
                '悬疑' => '悬疑氛围，暗调光影，局部打光',
                default => '自然氛围，均匀光线'
            };

            $prompt = "短剧《{$title}》第{$scene['id']}幕。{$scene['description']}。角色：{$characterDesc}。{$styleDesc}。{$cameraDesc}。{$moodDesc}。避免模糊、变形、低画质、AI塑料感、手指畸形。";

            $prompts[] = [
                'scene_id' => $scene['id'],
                'prompt' => $prompt,
                'camera' => $scene['camera'],
                'duration' => $scene['duration'],
            ];
        }

        return $prompts;
    }

    /**
     * 估算每个场景的时长
     */
    private function estimateDuration(string $text): int
    {
        $len = mb_strlen($text);
        if ($len < 30) return 3;
        if ($len < 80) return 5;
        if ($len < 200) return 8;
        return 10;
    }
}
