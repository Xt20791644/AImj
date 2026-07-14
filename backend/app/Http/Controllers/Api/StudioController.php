<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Character;
use App\Models\Episode;
use App\Models\Storyboard;
use App\Models\Work;
use App\Services\FFmpegService;
use App\Services\OssService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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

    // ========== Long Video Composition ==========
    public function composeWork(Request $request, $workId)
    {
        $episodes = Episode::where('work_id', $workId)->orderBy('episode_number')->get();
        $allVideos = [];

        foreach ($episodes as $ep) {
            $shots = Storyboard::where('episode_id', $ep->id)
                ->whereNotNull('output_video')
                ->orderBy('sort')->get();
            foreach ($shots as $s) {
                $allVideos[] = $s->output_video;
            }
        }

        if (count($allVideos) < 2) {
            return response()->json(['message' => '至少需要2个视频片段才能拼接'], 400);
        }

        $ffmpeg = app(FFmpegService::class);
        $oss = app(OssService::class);
        $workDir = storage_path("app/works/{$workId}");
        if (!is_dir($workDir)) mkdir($workDir, 0755, true);

        // Download all clips
        $localFiles = [];
        foreach ($allVideos as $i => $url) {
            $localPath = "{$workDir}/clip_{$i}.mp4";
            $response = Http::timeout(120)->get($url);
            if ($response->successful()) {
                file_put_contents($localPath, $response->body());
                $localFiles[] = $localPath;
            }
        }

        if (count($localFiles) < 2) {
            return response()->json(['message' => '下载视频片段失败'], 500);
        }

        // FFmpeg concat
        $outputPath = "{$workDir}/stitched.mp4";
        $finalPath = $ffmpeg->concatVideos($localFiles, $outputPath);

        // Upload to OSS
        $ossUrl = null;
        if ($oss->isConfigured()) {
            $ossUrl = $oss->uploadFromUrl('file://' . $finalPath, "works/{$workId}/stitched.mp4");
        }

        // Cleanup
        foreach ($localFiles as $f) @unlink($f);

        return response()->json([
            'output_url' => $ossUrl ?: $finalPath,
            'clip_count' => count($localFiles),
            'message' => "已拼接 " . count($localFiles) . " 个片段",
        ]);
    }
}
