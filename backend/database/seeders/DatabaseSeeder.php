<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Work;
use App\Models\WorkTimeline;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 创建管理员账号
        $admin = User::create([
            'name' => '管理员',
            'email' => 'admin@aiduanju.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'credits' => 99999,
        ]);

        $admin->creditTransactions()->create([
            'amount' => 99999,
            'balance_after' => 99999,
            'type' => 'recharge',
            'description' => '系统初始化',
        ]);

        // 创建测试用户
        $testUser = User::create([
            'name' => '测试用户',
            'email' => 'test@aiduanju.com',
            'password' => Hash::make('test123'),
            'role' => 'user',
            'credits' => 500,
        ]);

        $testUser->creditTransactions()->create([
            'amount' => 500,
            'balance_after' => 500,
            'type' => 'recharge',
            'description' => '注册赠送积分',
        ]);

        // 创建示例作品
        $demoWork = Work::create([
            'user_id' => $testUser->id,
            'title' => '都市逆袭 - 第一集',
            'content' => "都市白领林晨在30岁生日那天被公司裁员，心灰意冷的他在天桥下捡到一块旧怀表。当他拨动表针的那一刻，时间竟倒流回了三年前。\n\n林晨睁开眼，发现自己坐在三年前的办公室里，手中的项目方案正是当初被否决的那一份。这一次，他要改写命运。",
            'style' => 'realistic',
            'status' => 'completed',
            'progress' => 100,
            'status_text' => '创作完成',
            'duration' => 45,
            'views' => 1280,
        ]);

        $steps = [
            ['step' => 'script', 'status' => 'completed', 'message' => '剧本分析完成'],
            ['step' => 'characters', 'status' => 'completed', 'message' => '提取角色：林晨(男主)、张总(反派)、小美(女主)'],
            ['step' => 'storyboard', 'status' => 'completed', 'message' => '生成12个分镜镜头'],
            ['step' => 'images', 'status' => 'completed', 'message' => '生成3个角色参考图、5个场景底图'],
            ['step' => 'video', 'status' => 'completed', 'message' => '生成12个视频片段，总计45秒'],
            ['step' => 'audio', 'status' => 'completed', 'message' => '配音完成：林晨(云希)、张总(云扬)、小美(晓晓)'],
            ['step' => 'compose', 'status' => 'completed', 'message' => '视频合成完成'],
        ];

        foreach ($steps as $step) {
            WorkTimeline::create([
                'work_id' => $demoWork->id,
                ...$step,
            ]);
        }

        // 创建第二个示例作品（处理中状态）
        $inProgressWork = Work::create([
            'user_id' => $testUser->id,
            'title' => '仙侠情缘 - 第一集',
            'content' => "修仙界第一天才楚云，渡劫失败后重生回到少年时代。带着前世记忆的他，决心这一世不再重蹈覆辙。他提前找到了还未成长起来的未来魔尊，在对方最弱小的时候伸出了援手……",
            'style' => 'realistic',
            'status' => 'processing',
            'progress' => 60,
            'status_text' => '正在生成画面素材...',
        ]);

        $inProgressSteps = [
            ['step' => 'script', 'status' => 'completed', 'message' => '剧本分析完成'],
            ['step' => 'characters', 'status' => 'completed', 'message' => '提取角色：楚云(男主)、墨渊(魔尊)、清雪(女主)'],
            ['step' => 'storyboard', 'status' => 'completed', 'message' => '生成15个分镜镜头'],
            ['step' => 'images', 'status' => 'processing', 'message' => '正在生成画面素材...'],
            ['step' => 'video', 'status' => 'pending', 'message' => null],
            ['step' => 'audio', 'status' => 'pending', 'message' => null],
            ['step' => 'compose', 'status' => 'pending', 'message' => null],
        ];

        foreach ($inProgressSteps as $step) {
            WorkTimeline::create([
                'work_id' => $inProgressWork->id,
                ...$step,
            ]);
        }

        echo "Seeder completed:\n";
        echo "  Admin: admin@aiduanju.com / admin123\n";
        echo "  Test:  test@aiduanju.com / test123\n";
    }
}
