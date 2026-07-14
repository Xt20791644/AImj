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
            // 从粘贴文本中提取URL（处理用户复制整个分享文案的情况）
            preg_match('/https?:\/\/[^\s]+/', $url, $matches);
            if (!empty($matches[0])) {
                $url = rtrim($matches[0], '.,;:!?）)】」');
            }

            // 验证URL
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                return response()->json(['message' => '未识别到有效视频链接，请检查后重试'], 422);
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

            // 下载（跟随重定向）
            $response = Http::withOptions(['allow_redirects' => ['max' => 5]])->timeout(120)->get($url);
            if (!$response->successful()) {
                return response()->json(['message' => '无法获取视频，请检查链接'], 422);
            }

            $contentType = $response->header('Content-Type');
            $content = $response->body();

            // 如果下载到的是网页而不是视频，尝试从页面中提取视频源
            if (str_contains($contentType, 'text/html') || str_contains($contentType, 'application/json')) {
                // 尝试从HTML中提取视频URL
                preg_match('/"url":"(https?:\\/\\/[^"]+\\.mp4[^"]*)"/i', $content, $v);
                if (empty($v)) preg_match('/src="(https?:\\/\\/[^"]+\\.mp4[^"]*)"/i', $content, $v);
                if (empty($v)) preg_match('/https?:\\/\\/[^"\'\\s]+\\.mp4[^"\'\\s]*/i', $content, $v);

                if (!empty($v[1])) {
                    $response = Http::withOptions(['allow_redirects' => ['max' => 5]])->timeout(120)->get($v[1]);
                    if ($response->successful()) {
                        $contentType = $response->header('Content-Type');
                        $content = $response->body();
                    }
                }

                // 仍然不是视频
                if (!str_contains($contentType, 'video/') && !str_contains($contentType, 'application/octet-stream')) {
                    return response()->json([
                        'message' => '分享链接无法直接提取视频源。请通过"本地上传"方式上传已下载的视频文件'
                    ], 422);
                }
            }

            // 检查是否为视频
            if (!str_contains($contentType, 'video/') && !str_contains($contentType, 'octet-stream')) {
                return response()->json(['message' => '链接内容不是视频文件，请使用本地上传'], 422);
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
            try {
                $ossUrl = $oss->putObject($filename, $content, 'video/mp4');
                return response()->json(['url' => $ossUrl, 'path' => $filename, 'message' => '参考视频已上传']);
            } catch (\Exception $e) {
                Log::error('OSS upload error: ' . $e->getMessage());
                return response()->json(['message' => '视频存储失败，请稍后重试'], 500);
            }
        }
        return response()->json(['message' => '云存储服务未配置，请联系管理员'], 500);
    }

    /** 删除OSS上的参考视频 */
    public function deleteReference(Request $request, OssService $oss)
    {
        $path = $request->input('path');
        if ($path && $oss->isConfigured()) {
            $oss->deleteObject($path);
            return response()->json(['message' => '已删除']);
        }
        return response()->json(['message' => '无需删除']);
    }
}
