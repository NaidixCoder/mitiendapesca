<?php
namespace App\Services\Integrations\YouTube;

final class YouTubeApiClient
{
    private string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = trim((string)($apiKey ?? env('YOUTUBE_KEY', '')));
        if ($this->apiKey === '') {
            throw new \RuntimeException('YOUTUBE_KEY no configurado.');
        }
    }

    /** @return array<string,mixed> */
    public function getChannelById(string $channelId): array
    {
        $data = $this->request('channels', [
            'part' => 'snippet,statistics,contentDetails',
            'id' => trim($channelId),
            'maxResults' => 1,
        ]);
        $items = (array)($data['items'] ?? []);
        return is_array($items[0] ?? null) ? $items[0] : [];
    }

    /** @return array<int,array<string,mixed>> */
    public function getPlaylistItems(string $playlistId, int $maxResults = 5): array
    {
        $data = $this->request('playlistItems', [
            'part' => 'snippet,contentDetails',
            'playlistId' => trim($playlistId),
            'maxResults' => max(1, min(10, $maxResults)),
        ]);
        $items = (array)($data['items'] ?? []);
        return array_values(array_filter($items, 'is_array'));
    }

    /** @return array<int,array<string,mixed>> */
    public function getVideosByIds(array $videoIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map(
            static fn($v): string => trim((string)$v),
            $videoIds
        ), static fn(string $v): bool => $v !== '')));
        if ($ids === []) return [];

        $data = $this->request('videos', [
            'part' => 'snippet,statistics,contentDetails',
            'id' => implode(',', $ids),
            'maxResults' => count($ids),
        ]);
        $items = (array)($data['items'] ?? []);
        return array_values(array_filter($items, 'is_array'));
    }

    /** @return array<string,mixed> */
    private function request(string $resource, array $query): array
    {
        $endpoint = 'https://www.googleapis.com/youtube/v3/' . ltrim($resource, '/');
        $query['key'] = $this->apiKey;
        $url = $endpoint . '?' . http_build_query($query);

        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('No se pudo inicializar cURL para YouTube API.');
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

        $raw = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = (string)curl_error($ch);
        curl_close($ch);

        if ($raw === false || $raw === null) {
            throw new \RuntimeException('Error de red YouTube API: ' . ($err !== '' ? $err : 'sin detalle'));
        }

        $data = json_decode((string)$raw, true);
        if (!is_array($data)) {
            throw new \RuntimeException('Respuesta invalida de YouTube API (JSON).');
        }

        if ($code < 200 || $code >= 300) {
            $msg = '';
            if (isset($data['error']['message'])) {
                $msg = (string)$data['error']['message'];
            } elseif (isset($data['message'])) {
                $msg = (string)$data['message'];
            }
            if ($msg === '') $msg = 'HTTP ' . $code;
            throw new \RuntimeException('YouTube API: ' . $msg);
        }

        if (isset($data['error']) && is_array($data['error'])) {
            $msg = (string)($data['error']['message'] ?? 'Error desconocido');
            throw new \RuntimeException('YouTube API: ' . $msg);
        }

        return $data;
    }
}
