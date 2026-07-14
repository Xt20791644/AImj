<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OssService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class VideoController extends Controller
{
    /**
     * 处理参考视频：URL下载 → OSS上传 → 返回OSS链接
     */
    public function uploadReference(Request $request, OssService $oss)
    {
        $url = $request->input('url');
        $file = $request->file('video');

        // 方式1: URL下载
        if ($url) {
            // 验证URL
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                return response()->json(['message' => '视频链接格式不正确'], 422);
            }

            // 检查是否为支持的平台
            $supported = ['douyin.com','tiktok.com','kuaishou.com','xiaohongshu.com','xhslink.com',
                'bilibili.com','weibo.com','youtube.com','v.douyin.com','v.kuaishou.com'];
            $host = parse_url($url, PHP_URL_HOST);
            $isSupported = false;
            foreach ($supported as $s) {
                if (str_contains($host, $s)) { $isSupported = true; break; }
            }
            if (!$isSupported) {
                return response()->json(['message' => '不支持的视频平台，支持：抖音/快手/小红书/B站/微博'], 422);
            }

            // 下载视频
            $response = Http::timeout(120)->get($url);
            if (!$response->successful()) {
                return response()->json(['message' => '无法获取视频，请检查链接'], 422);
            }

            $content = $response->body();
            $filename = 'ref/' . uniqid() . '.mp4';

        // 方式2: 本地上传
        } elseif ($file) {
            if (!$file->isValid()) {
                return response()->json(['message' => '视频上传失败'], 422);
            }
            $content = file_get_contents($file->getRealPath());
            $filename = 'ref/' . uniqid() . '.' . $file->getClientOriginalExtension();
        } else {
            return response()->json(['message' => '请提供视频链接或上传视频文件'], 422);
        }

        // 上传到OSS
        if ($oss->isConfigured()) {
            $ossUrl = $oss->putObject($filename, $content, 'video/mp4');
            return response()->json(['url' => $ossUrl, 'message' => '参考视频已上传']);
        }

        return response()->json(['message' => 'OSS未配置'], 500);
    }
}
