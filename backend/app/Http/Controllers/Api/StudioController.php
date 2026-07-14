<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Character;
use App\Models\Episode;
use App\Models\Storyboard;
use App\Models\Work;
use Illuminate\Http\Request;

class StudioController extends Controller
{
    // ========== Characters ==========
    public function characters($workId)
    {
        return Character::where('work_id', $workId)->orderBy('sort')->get();
    }

    public function storeCharacter(Request $request, $workId)
    {
        $c = Character::create([...$request->all(), 'work_id' => $workId]);
        return response()->json($c, 201);
    }

    public function updateCharacter(Request $request, $workId, $id)
    {
        $c = Character::where('work_id', $workId)->findOrFail($id);
        $c->update($request->all());
        return response()->json($c);
    }

    public function deleteCharacter($workId, $id)
    {
        Character::where('work_id', $workId)->findOrFail($id)->delete();
        return response()->json(['message' => '已删除']);
    }

    // ========== Episodes ==========
    public function episodes($workId)
    {
        return Episode::where('work_id', $workId)->orderBy('episode_number')->get();
    }

    public function storeEpisode(Request $request, $workId)
    {
        $e = Episode::create([...$request->all(), 'work_id' => $workId]);
        return response()->json($e, 201);
    }

    public function updateEpisode(Request $request, $workId, $id)
    {
        $e = Episode::where('work_id', $workId)->findOrFail($id);
        $e->update($request->all());
        return response()->json($e);
    }

    public function showEpisode($workId, $id)
    {
        return Episode::where('work_id', $workId)->with('storyboards')->findOrFail($id);
    }

    // ========== Storyboards ==========
    public function storyboards($episodeId)
    {
        return Storyboard::where('episode_id', $episodeId)->orderBy('sort')->get();
    }

    public function storeStoryboard(Request $request, $episodeId)
    {
        $s = Storyboard::create([...$request->all(), 'episode_id' => $episodeId]);
        return response()->json($s, 201);
    }

    public function updateStoryboard(Request $request, $episodeId, $id)
    {
        $s = Storyboard::where('episode_id', $episodeId)->findOrFail($id);
        $s->update($request->all());
        return response()->json($s);
    }

    public function deleteStoryboard($episodeId, $id)
    {
        Storyboard::where('episode_id', $episodeId)->findOrFail($id)->delete();
        return response()->json(['message' => '已删除']);
    }

    // ========== Smart Model Selection ==========
    public function recommendModel(Request $request)
    {
        $script = $request->script ?? '';
        $style = $request->style ?? 'realistic';
        $hasAction = (bool)preg_match('/打|战斗|奔跑|追逐|飞|爆炸|枪|剑/i', $script);
        $hasDialogue = (bool)preg_match('/说|道|问|答|："|」/i', $script);
        $length = mb_strlen($script);

        $recommendations = [
            'image_model' => $style === 'realistic' ? 'kling-v3' : 'kling-v2-1',
            'video_model' => $hasDialogue ? 'kling-v3-turbo' : 'kling-v2-6',
            'video_mode' => $length > 1000 ? 'pro' : 'std',
            'image_resolution' => $length > 1000 ? '2k' : '1k',
            'duration' => $length > 500 ? '10' : '5',
        ];

        return response()->json($recommendations);
    }
}
