<?php
namespace App\Services\Integrations\YouTube;

final class YouTubeCacheService
{
    private string $dir;

    public function __construct(?string $dir = null)
    {
        $this->dir = rtrim((string)($dir ?? base_path('storage/cache/youtube')), '/\\');
    }

    /** @return array{updated_at:string,data:array<string,mixed>}|null */
    public function readFresh(string $key): ?array
    {
        $payload = $this->readRaw($key);
        if ($payload === null) return null;
        if ((int)($payload['expires_at'] ?? 0) <= time()) return null;
        return $payload;
    }

    /** @return array{updated_at:string,data:array<string,mixed>}|null */
    public function readAny(string $key): ?array
    {
        return $this->readRaw($key);
    }

    /** @param array<string,mixed> $data */
    public function write(string $key, array $data, int $ttl): void
    {
        $ttl = max(60, $ttl);
        $payload = [
            'expires_at' => time() + $ttl,
            'updated_at' => gmdate('c'),
            'data' => $data,
        ];

        $path = $this->pathForKey($key);
        @mkdir(dirname($path), 0775, true);
        @file_put_contents(
            $path,
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    /** @return array{expires_at:int,updated_at:string,data:array<string,mixed>}|null */
    private function readRaw(string $key): ?array
    {
        $path = $this->pathForKey($key);
        if (!is_file($path)) return null;

        try {
            $raw = @file_get_contents($path);
            $data = is_string($raw) ? json_decode($raw, true) : null;
            if (!is_array($data)) return null;
            if (!is_array($data['data'] ?? null)) return null;
            return [
                'expires_at' => (int)($data['expires_at'] ?? 0),
                'updated_at' => (string)($data['updated_at'] ?? ''),
                'data' => (array)$data['data'],
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function pathForKey(string $key): string
    {
        return $this->dir . '/' . sha1('yt:' . $key) . '.json';
    }
}
