<?php
declare(strict_types=1);

namespace App\Services\Admin\Brands;

/** Resuelve portadas locales o HTTP(S) sin prefijar URLs externas con el host. */
final class BrandCoverUrl
{
    public static function resolve(string $path): string
    {
        return preg_match('#^https?://#i', $path) ? $path : public_url($path);
    }
}
