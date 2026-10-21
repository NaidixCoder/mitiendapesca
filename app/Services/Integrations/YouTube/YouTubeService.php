<?php
namespace App\Services\Integrations\YouTube;

final class YouTubeService
{
    private YouTubeCacheService $cache;

    public function __construct(?YouTubeCacheService $cache = null)
    {
        $this->cache = $cache ?? new YouTubeCacheService();
    }

    /** @return array<string,mixed> */
    public function forHome(): array
    {
        $channelId = trim((string)env('YOUTUBE_CHANNEL_ID', ''));
        $featuredVideoId = trim((string)env('YOUTUBE_FEATURED_VIDEO_ID', ''));
        $playlistId = trim((string)env('YOUTUBE_PLAYLIST_ID_PEJERREY', ''));
        $ttlChannel = max(120, (int)env('YOUTUBE_CACHE_TTL_CHANNEL', 3600));
        $ttlVideos = max(120, (int)env('YOUTUBE_CACHE_TTL_VIDEOS', 900));
        $shortsMaxSeconds = max(60, (int)env('YOUTUBE_SHORTS_MAX_SECONDS', 180));

        $fallback = $this->fallback('fallback', '', 'Configuracion de YouTube incompleta.');
        if ($channelId === '') return $fallback;

        $usedApi = false;
        $usedCache = false;
        $updatedAt = '';
        $error = '';

        try {
            $api = new YouTubeApiClient();
        } catch (\Throwable $e) {
            return $this->fallback('fallback', '', $e->getMessage());
        }

        $channelRaw = [];
        $channelCacheKey = 'channel_' . $channelId;
        try {
            $cached = $this->cache->readFresh($channelCacheKey);
            if ($cached !== null) {
                $usedCache = true;
                $channelRaw = (array)$cached['data'];
                $updatedAt = (string)($cached['updated_at'] ?? '');
            } else {
                $channelRaw = $api->getChannelById($channelId);
                if ($channelRaw !== []) {
                    $usedApi = true;
                    $updatedAt = gmdate('c');
                    $this->cache->write($channelCacheKey, $channelRaw, $ttlChannel);
                }
            }
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            $stale = $this->cache->readAny($channelCacheKey);
            if ($stale !== null) {
                $usedCache = true;
                $channelRaw = (array)$stale['data'];
                $updatedAt = (string)($stale['updated_at'] ?? '');
            }
        }

        if ($channelRaw === []) {
            return $this->fallback($usedCache ? 'cache' : 'fallback', $updatedAt, $error !== '' ? $error : 'No se pudo obtener el canal.');
        }

        $uploadsPlaylist = trim((string)($channelRaw['contentDetails']['relatedPlaylists']['uploads'] ?? ''));
        $channel = $this->mapChannel($channelId, $channelRaw);

        $resolvedVideoId = $featuredVideoId;
        $resolvedVideoRaw = [];
        if ($resolvedVideoId === '') {
            $playlistForFeatured = $playlistId !== '' ? $playlistId : $uploadsPlaylist;
            if ($playlistForFeatured !== '') {
                $playlistCacheKey = 'playlist_' . $playlistForFeatured . '_8';
                try {
                    $cached = $this->cache->readFresh($playlistCacheKey);
                    $playlistItems = [];
                    if ($cached !== null) {
                        $usedCache = true;
                        $playlistItems = (array)($cached['data']['items'] ?? []);
                        if ($updatedAt === '') $updatedAt = (string)($cached['updated_at'] ?? '');
                    } else {
                        $playlistItems = $api->getPlaylistItems($playlistForFeatured, 8);
                        $usedApi = true;
                        if ($updatedAt === '') $updatedAt = gmdate('c');
                        $this->cache->write($playlistCacheKey, ['items' => $playlistItems], $ttlVideos);
                    }

                    $candidateIds = [];
                    foreach ($playlistItems as $item) {
                        if (!is_array($item)) continue;
                        $vid = trim((string)(
                            $item['contentDetails']['videoId']
                            ?? $item['snippet']['resourceId']['videoId']
                            ?? ''
                        ));
                        if ($vid !== '') $candidateIds[] = $vid;
                    }

                    if ($candidateIds !== []) {
                        $videoCandidatesCacheKey = 'playlist_videos_' . $playlistForFeatured . '_' . sha1(implode(',', $candidateIds));
                        $videoCandidates = [];
                        $cachedCandidates = $this->cache->readFresh($videoCandidatesCacheKey);
                        if ($cachedCandidates !== null) {
                            $usedCache = true;
                            $videoCandidates = (array)($cachedCandidates['data']['items'] ?? []);
                        } else {
                            $videoCandidates = $api->getVideosByIds($candidateIds);
                            if ($videoCandidates !== []) {
                                $usedApi = true;
                                $this->cache->write($videoCandidatesCacheKey, ['items' => $videoCandidates], $ttlVideos);
                            }
                        }

                        $byId = [];
                        foreach ($videoCandidates as $videoRaw) {
                            if (!is_array($videoRaw)) continue;
                            $id = trim((string)($videoRaw['id'] ?? ''));
                            if ($id === '') continue;
                            $byId[$id] = $videoRaw;
                        }

                        $selectedRaw = $this->selectPreferredVideo($candidateIds, $byId, $shortsMaxSeconds);

                        if ($selectedRaw !== []) {
                            $resolvedVideoRaw = $selectedRaw;
                            $resolvedVideoId = trim((string)($selectedRaw['id'] ?? ''));
                        }
                    }
                } catch (\Throwable $e) {
                    if ($error === '') $error = $e->getMessage();
                    $stale = $this->cache->readAny($playlistCacheKey);
                    if ($stale !== null) {
                        $usedCache = true;
                        $playlistItems = (array)($stale['data']['items'] ?? []);
                        $candidateIds = [];
                        foreach ($playlistItems as $item) {
                            if (!is_array($item)) continue;
                            $vid = trim((string)(
                                $item['contentDetails']['videoId']
                                ?? $item['snippet']['resourceId']['videoId']
                                ?? ''
                            ));
                            if ($vid !== '') $candidateIds[] = $vid;
                        }

                        if ($candidateIds !== []) {
                            $videoCandidatesCacheKey = 'playlist_videos_' . $playlistForFeatured . '_' . sha1(implode(',', $candidateIds));
                            $cachedCandidates = $this->cache->readAny($videoCandidatesCacheKey);
                            $videoCandidates = (array)($cachedCandidates['data']['items'] ?? []);
                            $byId = [];
                            foreach ($videoCandidates as $videoRaw) {
                                if (!is_array($videoRaw)) continue;
                                $id = trim((string)($videoRaw['id'] ?? ''));
                                if ($id === '') continue;
                                $byId[$id] = $videoRaw;
                            }

                            $selectedRaw = $this->selectPreferredVideo($candidateIds, $byId, $shortsMaxSeconds);
                            if ($selectedRaw !== []) {
                                $resolvedVideoRaw = $selectedRaw;
                                $resolvedVideoId = trim((string)($selectedRaw['id'] ?? ''));
                            } else {
                                $resolvedVideoId = $candidateIds[0] ?? '';
                            }
                        }
                        if ($updatedAt === '') $updatedAt = (string)($stale['updated_at'] ?? '');
                    }
                }
            }
        }

        $featured = $this->emptyFeatured();
        if ($resolvedVideoRaw !== []) {
            $featured = $this->mapFeatured($resolvedVideoRaw);
        } elseif ($resolvedVideoId !== '') {
            $videoCacheKey = 'video_' . $resolvedVideoId;
            try {
                $cached = $this->cache->readFresh($videoCacheKey);
                $videoRaw = [];
                if ($cached !== null) {
                    $usedCache = true;
                    $videoRaw = (array)($cached['data']['video'] ?? []);
                    if ($updatedAt === '') $updatedAt = (string)($cached['updated_at'] ?? '');
                } else {
                    $list = $api->getVideosByIds([$resolvedVideoId]);
                    $videoRaw = is_array($list[0] ?? null) ? $list[0] : [];
                    if ($videoRaw !== []) {
                        $usedApi = true;
                        if ($updatedAt === '') $updatedAt = gmdate('c');
                        $this->cache->write($videoCacheKey, ['video' => $videoRaw], $ttlVideos);
                    }
                }
                if ($videoRaw !== []) {
                    $featured = $this->mapFeatured($videoRaw);
                }
            } catch (\Throwable $e) {
                if ($error === '') $error = $e->getMessage();
                $stale = $this->cache->readAny($videoCacheKey);
                if ($stale !== null) {
                    $usedCache = true;
                    $videoRaw = (array)($stale['data']['video'] ?? []);
                    if ($videoRaw !== []) {
                        $featured = $this->mapFeatured($videoRaw);
                    }
                    if ($updatedAt === '') $updatedAt = (string)($stale['updated_at'] ?? '');
                }
            }
        }

        $source = 'fallback';
        if ($usedCache) $source = 'cache';
        elseif ($usedApi) $source = 'api';

        return [
            'channel' => $channel,
            'featured_video' => $featured,
            'videos' => [],
            'meta' => [
                'source' => $source,
                'updated_at' => $updatedAt !== '' ? $updatedAt : gmdate('c'),
                'has_error' => $error !== '',
                'error_message' => $error,
            ],
        ];
    }

    /** @return array<string,mixed> */
    private function mapChannel(string $channelId, array $raw): array
    {
        $snippet = (array)($raw['snippet'] ?? []);
        $stats = (array)($raw['statistics'] ?? []);
        $thumbs = (array)($snippet['thumbnails'] ?? []);
        $thumb = (string)(
            $thumbs['high']['url']
            ?? $thumbs['medium']['url']
            ?? $thumbs['default']['url']
            ?? ''
        );

        return [
            'title' => (string)($snippet['title'] ?? 'Canal de YouTube'),
            'thumbnail' => $thumb,
            'subscriber_count' => (int)($stats['subscriberCount'] ?? 0),
            'video_count' => (int)($stats['videoCount'] ?? 0),
            'url' => 'https://www.youtube.com/channel/' . rawurlencode($channelId),
        ];
    }

    /** @return array<string,mixed> */
    private function mapFeatured(array $raw): array
    {
        $snippet = (array)($raw['snippet'] ?? []);
        $stats = (array)($raw['statistics'] ?? []);
        $content = (array)($raw['contentDetails'] ?? []);
        $thumbs = (array)($snippet['thumbnails'] ?? []);
        $videoId = trim((string)($raw['id'] ?? ''));
        $thumb = (string)(
            $thumbs['maxres']['url']
            ?? $thumbs['high']['url']
            ?? $thumbs['medium']['url']
            ?? $thumbs['default']['url']
            ?? ''
        );

        return [
            'video_id' => $videoId,
            'title' => (string)($snippet['title'] ?? ''),
            'published_at' => (string)($snippet['publishedAt'] ?? ''),
            'thumbnail' => $thumb,
            'duration' => (string)($content['duration'] ?? ''),
            'view_count' => (int)($stats['viewCount'] ?? 0),
            'url' => $videoId !== '' ? 'https://www.youtube.com/watch?v=' . rawurlencode($videoId) : '',
            'embed_url' => $videoId !== '' ? 'https://www.youtube-nocookie.com/embed/' . rawurlencode($videoId) : '',
        ];
    }

    /** @param array<string,array<string,mixed>> $byId */
    private function selectPreferredVideo(array $candidateIds, array $byId, int $shortsMaxSeconds): array
    {
        foreach ($candidateIds as $candidateId) {
            $raw = (array)($byId[$candidateId] ?? []);
            if ($raw === []) continue;
            if (!$this->isShortVideo($raw, $shortsMaxSeconds)) {
                return $raw;
            }
        }

        foreach ($candidateIds as $candidateId) {
            $raw = (array)($byId[$candidateId] ?? []);
            if ($raw !== []) {
                return $raw;
            }
        }

        return [];
    }

    private function isShortVideo(array $raw, int $shortsMaxSeconds = 180): bool
    {
        $snippet = (array)($raw['snippet'] ?? []);
        $content = (array)($raw['contentDetails'] ?? []);
        $description = mb_strtolower((string)($snippet['description'] ?? ''), 'UTF-8');
        $title = mb_strtolower((string)($snippet['title'] ?? ''), 'UTF-8');
        $tags = array_map(
            static fn($tag): string => mb_strtolower(trim((string)$tag), 'UTF-8'),
            array_values(array_filter((array)($snippet['tags'] ?? []), 'is_scalar'))
        );

        if (
            ($title !== '' && str_contains($title, '#shorts'))
            || ($description !== '' && str_contains($description, '#shorts'))
            || in_array('#shorts', $tags, true)
            || in_array('shorts', $tags, true)
        ) {
            return true;
        }

        $seconds = $this->isoDurationToSeconds((string)($content['duration'] ?? ''));
        if ($seconds > 0 && $seconds <= 70) {
            return true;
        }

        $publishedAt = trim((string)($snippet['publishedAt'] ?? ''));
        if ($this->isThreeMinuteShortWindow($publishedAt) && $seconds > 0 && $seconds <= $shortsMaxSeconds) {
            return true;
        }

        return false;
    }

    private function isThreeMinuteShortWindow(string $publishedAt): bool
    {
        $ts = strtotime($publishedAt);
        if ($ts === false) return false;

        $shortsExpandedAt = strtotime('2024-10-15T00:00:00Z');
        if ($shortsExpandedAt === false) return false;

        return $ts >= $shortsExpandedAt;
    }

    private function isoDurationToSeconds(string $iso): int
    {
        $iso = trim($iso);
        if ($iso === '') return 0;
        try {
            $interval = new \DateInterval($iso);
            return ((int)$interval->d * 86400)
                + ((int)$interval->h * 3600)
                + ((int)$interval->i * 60)
                + (int)$interval->s;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** @return array<string,mixed> */
    private function emptyFeatured(): array
    {
        return [
            'video_id' => '',
            'title' => '',
            'published_at' => '',
            'thumbnail' => '',
            'duration' => '',
            'view_count' => 0,
            'url' => '',
            'embed_url' => '',
        ];
    }

    /** @return array<string,mixed> */
    private function fallback(string $source, string $updatedAt, string $error): array
    {
        return [
            'channel' => [
                'title' => 'Canal de YouTube',
                'thumbnail' => '',
                'subscriber_count' => 0,
                'video_count' => 0,
                'url' => '',
            ],
            'featured_video' => $this->emptyFeatured(),
            'videos' => [],
            'meta' => [
                'source' => $source,
                'updated_at' => $updatedAt,
                'has_error' => $error !== '',
                'error_message' => $error,
            ],
        ];
    }
}
