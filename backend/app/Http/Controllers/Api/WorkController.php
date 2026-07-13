<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWorkJob;
use App\Models\Work;
use App\Models\WorkTimeline;
use App\Services\KlingService;
use Illuminate\Http\Request;

class WorkController extends Controller
{
    public function index(Request $request)
    {
        $query = Work::with('user:id,name,avatar')->latest();
        
        // ?my=1 只返回当前用户的作品
        if ($request->has('my')) {
            $query->where('user_id', $request->user()->id);
        } else {
            $query->where('status', 'completed');
        }
        
        $works = $query->paginate(12);
        return response()->json($works);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'style' => 'required|in:realistic,anime,3d,cyberpunk',
        ]);

        $cost = config('services.credits.cost_per_generation', 50);
        $user = $request->user();
        if ($user->credits < $cost) {
            return response()->json(['message' => '积分不足，请先充值'], 402);
        }

        $mode = $request->mode ?? 'fast';
        $klingConfig = $request->kling_config ?? [];

        $work = Work::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'content' => $request->content,
            'style' => $request->style,
            'status' => $mode === 'fine' ? 'draft' : 'pending',
            'progress' => 0,
            'meta' => [
                'mode' => $mode,
                'script' => $request->script ?? null,
                'kling_config' => $klingConfig,
                'selected_images' => $request->selected_images ?? [],
            ],
        ]);

        $user->consumeCredits($cost, "创作短剧《{$work->title}》", $work);

        $steps = ['script', 'characters', 'storyboard', 'images', 'video', 'audio', 'compose'];
        foreach ($steps as $step) {
            WorkTimeline::create(['work_id' => $work->id, 'step' => $step, 'status' => 'pending']);
        }

        // 精细模式不自动触发Job，快速模式直接调度
        if ($mode !== 'fine') {
            $this->initTimelineSteps($work);
            ProcessWorkJob::dispatch($work->id);
        } else {
            // 精细模式：标记剧本已完成（用户在前端已确认）
            $this->updateTimeline($work, 'script', 'completed', '剧本已确认');
            $work->update(['progress' => 20, 'status_text' => '剧本已确认']);
        }

        return response()->json($work, 201);
    }

    // ============================================
    // 精细模式分步API
    // ============================================

    /** Step 2: 确认剧本，进入图片配置 */
    public function confirmScript(Request $request, $id)
    {
        $work = $this->findOwnWork($request, $id);
        $work->update([
            'meta' => array_merge($work->meta ?? [], ['script' => $request->script]),
            'progress' => 20,
            'status_text' => '剧本已确认',
        ]);
        $this->updateTimeline($work, 'script', 'completed', '剧本已确认');

        return response()->json($work);
    }

    /** Step 2: 发起图片生成 */
    public function generateImages(Request $request, $id)
    {
        $work = $this->findOwnWork($request, $id);
        $config = $request->config ?? $work->meta['kling_config'] ?? [];

        $n = max(3, min(5, (int)($config['image_n'] ?? 3)));

        $this->updateTimeline($work, 'images', 'processing', "正在生成 {$n} 张图片...");
        $work->update(['progress' => 40, 'status_text' => '正在生成图片...']);

        try {
            $kling = app(KlingService::class);
            $images = [];
            $script = $work->meta['script'] ?? $work->content;
            $promptBase = mb_substr($script, 0, 500);

            for ($i = 0; $i < $n; $i++) {
                $result = $kling->generateImage("短剧场景图: {$promptBase} - 场景" . ($i + 1), $config);
                $taskId = $result['task_id'] ?? null;
                while ($taskId) {
                    sleep(3);
                    $status = $kling->getImageResult($taskId);
                    if (($status['task_status'] ?? '') === 'succeed') {
                        $images[] = ['id' => $i + 1, 'task_id' => $taskId, 'url' => $status['task_result']['images'][0]['url'] ?? '', 'label' => "场景 " . ($i + 1)];
                        break;
                    }
                    if (($status['task_status'] ?? '') === 'failed') break;
                }
            }

            $meta = $work->meta ?? [];
            $meta['generated_images'] = $images;
            $work->update(['meta' => $meta, 'progress' => 50, 'status_text' => '图片生成完成']);

            $this->updateTimeline($work, 'images', 'completed', "已生成 " . count($images) . " 张图片", ['images' => $images]);

            return response()->json(['images' => $images, 'work' => $work]);
        } catch (\Exception $e) {
            $this->updateTimeline($work, 'images', 'failed', $e->getMessage());
            return response()->json(['message' => '生图失败: ' . $e->getMessage()], 500);
        }
    }

    /** Step 3: 选择图片后，发起视频+配音合成 */
    public function finalizeWork(Request $request, $id)
    {
        $work = $this->findOwnWork($request, $id);

        $meta = $work->meta ?? [];
        $meta['selected_images'] = $request->selected_images ?? [];
        $meta['kling_config'] = array_merge($meta['kling_config'] ?? [], $request->config ?? []);
        $work->update(['meta' => $meta, 'status' => 'pending', 'progress' => 60, 'status_text' => '开始视频合成']);

        $this->updateTimeline($work, 'characters', 'completed', '角色提取完成');
        $this->updateTimeline($work, 'storyboard', 'completed', '分镜生成完成');

        ProcessWorkJob::dispatch($work->id, 'video');

        return response()->json($work);
    }

    // ============================================
    // 公共方法
    // ============================================

    public function show($id)
    {
        $work = Work::with('timelines', 'user:id,name,avatar')->findOrFail($id);
        return response()->json($work);
    }

    public function destroy(Request $request, $id)
    {
        $work = $this->findOwnWork($request, $id);
        $work->delete();
        return response()->json(['message' => '已删除']);
    }

    public function timeline($id)
    {
        $work = Work::findOrFail($id);
        return response()->json($work->timelines);
    }

    // ============================================
    // 私有辅助
    // ============================================

    private function findOwnWork(Request $request, $id): Work
    {
        return Work::where('user_id', $request->user()->id)->findOrFail($id);
    }

    private function updateTimeline(Work $work, string $step, string $status, string $message, array $output = null): void
    {
        WorkTimeline::where('work_id', $work->id)->where('step', $step)->update([
            'status' => $status, 'message' => $message, 'output' => $output,
        ]);
    }

    private function initTimelineSteps(Work $work): void
    {
        $steps = ['script' => 'processing', 'characters' => 'pending', 'storyboard' => 'pending',
                   'images' => 'pending', 'video' => 'pending', 'audio' => 'pending', 'compose' => 'pending'];
        foreach ($steps as $step => $status) {
            WorkTimeline::where('work_id', $work->id)->where('step', $step)
                ->update(['status' => $status, 'message' => $status === 'processing' ? '正在分析故事...' : null]);
        }
    }
}
