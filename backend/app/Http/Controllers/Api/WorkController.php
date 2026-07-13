<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWorkJob;
use App\Models\Work;
use App\Models\WorkTimeline;
use Illuminate\Http\Request;

class WorkController extends Controller
{
    public function index(Request $request)
    {
        $works = Work::with('user:id,name,avatar')
            ->where('status', 'completed')
            ->latest()
            ->paginate(12);

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

        // 检查积分
        if ($user->credits < $cost) {
            return response()->json(['message' => '积分不足，请先充值'], 402);
        }

        // 扣除积分
        $work = Work::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'content' => $request->content,
            'style' => $request->style,
            'status' => 'pending',
            'progress' => 0,
        ]);

        $user->consumeCredits($cost, "创作短剧《{$work->title}》", $work);

        // 创建时间线记录
        $steps = ['script', 'characters', 'storyboard', 'images', 'video', 'audio', 'compose'];
        foreach ($steps as $step) {
            WorkTimeline::create([
                'work_id' => $work->id,
                'step' => $step,
                'status' => 'pending',
            ]);
        }

        ProcessWorkJob::dispatch($work->id);

        return response()->json($work, 201);
    }

    public function show($id)
    {
        $work = Work::with('timelines', 'user:id,name,avatar')->findOrFail($id);
        return response()->json($work);
    }

    public function destroy(Request $request, $id)
    {
        $work = Work::where('user_id', $request->user()->id)->findOrFail($id);
        $work->delete();
        return response()->json(['message' => '已删除']);
    }

    public function timeline($id)
    {
        $work = Work::findOrFail($id);
        return response()->json($work->timelines);
    }
}
