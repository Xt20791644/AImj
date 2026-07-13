<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('works', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('content'); // 原始故事内容
            $table->string('style')->default('realistic'); // realistic | anime | 3d | cyberpunk
            $table->string('status')->default('pending'); // pending | processing | completed | failed
            $table->integer('progress')->default(0); // 0-100
            $table->string('status_text')->nullable(); // 当前步骤描述
            $table->string('output_video')->nullable(); // 最终视频路径
            $table->string('output_cover')->nullable(); // 封面图
            $table->integer('duration')->default(0); // 视频时长(秒)
            $table->integer('views')->default(0);
            $table->json('meta')->nullable(); // 角色/场景/分镜等中间产物JSON
            $table->timestamps();
        });

        Schema::create('work_timelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_id')->constrained()->cascadeOnDelete();
            $table->string('step'); // script | characters | storyboard | images | video | audio | compose
            $table->string('status'); // pending | processing | completed | failed
            $table->text('message')->nullable();
            $table->json('output')->nullable(); // 本步骤产出的数据
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_timelines');
        Schema::dropIfExists('works');
    }
};
