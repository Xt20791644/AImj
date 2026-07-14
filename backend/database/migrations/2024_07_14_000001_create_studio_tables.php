<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 角色资产库
        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('role_type')->default('supporting'); // protagonist/antagonist/supporting/cameo
            $table->string('gender')->nullable();
            $table->integer('age')->nullable();
            $table->text('description')->nullable(); // 人设描述
            $table->text('appearance')->nullable(); // 外貌特征
            $table->text('personality')->nullable(); // 性格
            $table->text('voice_tone')->nullable(); // 音色
            $table->string('image_url')->nullable(); // 角色形象图
            $table->string('ref_image_url')->nullable(); // 参考图
            $table->json('tags')->nullable(); // 标签
            $table->integer('sort')->default(0);
            $table->timestamps();
        });

        // 剧集
        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_id')->constrained()->cascadeOnDelete();
            $table->integer('episode_number');
            $table->string('title');
            $table->text('script')->nullable(); // 本集剧本
            $table->text('summary')->nullable(); // 剧情摘要
            $table->string('status')->default('draft'); // draft/storyboarding/rendering/completed
            $table->integer('duration')->default(0);
            $table->string('output_video')->nullable();
            $table->string('output_cover')->nullable();
            $table->integer('sort')->default(0);
            $table->timestamps();
        });

        // 分镜 (每个镜头)
        Schema::create('storyboards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('episode_id')->constrained()->cascadeOnDelete();
            $table->integer('scene_number');
            $table->integer('shot_number');
            $table->string('shot_type')->default('medium'); // wide/medium/close-up/extreme-close-up
            $table->string('camera_movement')->nullable(); // 运镜
            $table->text('prompt'); // 生成提示词
            $table->text('description')->nullable(); // 场景描述
            $table->text('dialogue')->nullable(); // 台词
            $table->string('character_ids')->nullable(); // 出镜角色ID列表
            $table->integer('duration')->default(5); // 时长(秒)
            $table->string('status')->default('pending'); // pending/generating/completed/failed
            $table->string('output_video')->nullable();
            $table->string('output_image')->nullable();
            $table->json('config')->nullable(); // 生成配置
            $table->integer('sort')->default(0);
            $table->timestamps();
        });

        // 给 works 表添加项目级字段
        Schema::table('works', function (Blueprint $table) {
            $table->string('project_type')->default('single')->after('style'); // single/series
            $table->integer('total_episodes')->default(1)->after('project_type');
            $table->string('art_style')->nullable()->after('total_episodes'); // 画风: realistic/anime/3d等
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storyboards');
        Schema::dropIfExists('episodes');
        Schema::dropIfExists('characters');
        Schema::table('works', function (Blueprint $table) {
            $table->dropColumn(['project_type', 'total_episodes', 'art_style']);
        });
    }
};
