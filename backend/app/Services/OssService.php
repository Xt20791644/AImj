<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OssService
{
    private string $accessKeyId;
    private string $accessKeySecret;
    private string $bucket;
    private string $endpoint;
    private string $cdnDomain;

    public function __construct()
    {
        $this->accessKeyId = config('services.oss.access_key_id');
        $this->accessKeySecret = config('services.oss.access_key_secret');
        $this->bucket = config('services.oss.bucket', 'aiduanju');
        $this->endpoint = config('services.oss.endpoint', 'oss-cn-hangzhou.aliyuncs.com');
        $this->cdnDomain = config('services.oss.cdn_domain', '');
    }

    public function isConfigured(): bool
    {
        return !empty($this->accessKeyId) && !empty($this->accessKeySecret);
    }

    /**
     * 从 URL 下载文件并上传到 OSS
     * 
     * @param string $sourceUrl Kling CDN 视频/图片 URL
     * @param string $ossPath OSS 存储路径 (如 works/12/output.mp4)
     * @return string|null OSS 访问 URL，失败返回 null
     */
    public function uploadFromUrl(string $sourceUrl, string $ossPath): ?string
    {
        if (!$this->isConfigured()) {
            Log::info('OSS not configured, skipping upload');
            return null;
        }

        try {
            // 1. 下载源文件
            $response = Http::timeout(120)->get($sourceUrl);
            if (!$response->successful()) {
                Log::error("OSS download failed: {$sourceUrl}");
                return null;
            }

            $content = $response->body();
            $contentType = $response->header('Content-Type') ?? 'application/octet-stream';

            // 2. 上传到 OSS
            return $this->putObject($ossPath, $content, $contentType);
        } catch (\Exception $e) {
            Log::error("OSS upload failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * PUT Object 到 OSS (REST API)
     */
    public function putObject(string $objectPath, string $content, string $contentType): string
    {
        $date = gmdate('D, d M Y H:i:s \G\M\T');
        // endpoint 已包含 bucket 则直接使用，否则前置 bucket
        $host = str_starts_with($this->endpoint, $this->bucket) ? $this->endpoint : "{$this->getHost()}";
        $resource = "/{$this->bucket}/{$objectPath}";

        // 签名
        $stringToSign = "PUT\n\n{$contentType}\n{$date}\n{$resource}";
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $this->accessKeySecret, true));
        $authorization = "OSS {$this->accessKeyId}:{$signature}";

        $response = Http::withHeaders([
            'Date' => $date,
            'Content-Type' => $contentType,
            'Authorization' => $authorization,
            'x-oss-object-acl' => 'public-read',
        ])->withBody($content, $contentType)
          ->put("https://{$host}/{$objectPath}");

        if ($response->successful()) {
            $url = $this->cdnDomain
                ? "https://{$this->cdnDomain}/{$objectPath}"
                : "https://{$host}/{$objectPath}";
            Log::info("OSS upload success: {$url}");
            return $url;
        }

        Log::error("OSS PUT failed", ['status' => $response->status(), 'body' => $response->body()]);
        throw new \Exception("OSS上传失败 [{$response->status()}]: " . substr($response->body(), 0, 200));
    }

    /**
     * 获取公开访问 URL
     */
    public function getUrl(string $objectPath): string
    {
        if ($this->cdnDomain) {
            return "https://{$this->cdnDomain}/{$objectPath}";
        }
        return "https://{$this->getHost()}/{$objectPath}";
    }

    /**
     * 删除 OSS 文件
     */
    public function deleteObject(string $objectPath): bool
    {
        if (!$this->isConfigured()) return false;

        $date = gmdate('D, d M Y H:i:s \G\M\T');
        $host = "{$this->getHost()}";
        $resource = "/{$this->bucket}/{$objectPath}";
        $stringToSign = "DELETE\n\n\n{$date}\n{$resource}";
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $this->accessKeySecret, true));

        $response = Http::withHeaders([
            'Date' => $date,
            'Authorization' => "OSS {$this->accessKeyId}:{$signature}",
        ])->delete("https://{$host}/{$objectPath}");

        return $response->successful();
    }

    private function getHost(): string
    {
        return str_starts_with($this->endpoint, $this->bucket)
            ? $this->endpoint
            : "{$this->getHost()}";
    }
}
