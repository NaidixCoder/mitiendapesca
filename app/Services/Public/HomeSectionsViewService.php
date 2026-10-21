<?php
namespace App\Services\Public;

use App\Repositories\Catalog\BrandRepository;
use App\Repositories\Catalog\CategoryRepository;

final class HomeSectionsViewService
{
    public function __construct(
        private BrandRepository $brands,
        private CategoryRepository $categories
    ) {}

    /**
     * @return array<int,array{title:string,href:string,img:string,slug:string}>
     */
    public function categories(int $limit = 4): array
    {
        $rows = $this->categories->listHomeFeatured($limit);
        $out = [];
        foreach ($rows as $row) {
            $slug = (string)($row['slug'] ?? '');
            if ($slug === '') continue;
            $title = $this->normalizeCategoryTitle(
                (string)($row['name'] ?? ''),
                $slug
            );
            $out[] = [
                'title' => $title !== '' ? $title : 'Categoria',
                'slug' => $slug,
                'href' => url('/categoria') . '?slug=' . urlencode($slug),
                'img' => $this->resolveCategoryCover((string)($row['cover_image'] ?? ''), $slug),
            ];
        }
        return $out;
    }

    /**
     * @return array<int,array{name:string,slug:string,href:string,logo:string}>
     */
    public function brands(int $limit = 6): array
    {
        $rows = $this->brands->listHomeFeatured($limit);
        $out = [];
        foreach ($rows as $row) {
            $slug = (string)($row['slug'] ?? '');
            if ($slug === '') continue;
            $name = (string)($row['name'] ?? '');
            $out[] = [
                'name' => $name !== '' ? $name : 'Marca',
                'slug' => $slug,
                'href' => url('/marca') . '?slug=' . urlencode($slug),
                'logo' => $this->resolveBrandCover((string)($row['cover_image'] ?? ''), $slug),
            ];
        }
        return $out;
    }

    private function resolveCategoryCover(string $value, string $slug): string
    {
        $v = trim($value);
        if ($v !== '') return public_url($v);
        $fallbacks = [
            'reels' => asset('img/cat/reels.webp'),
            'canas' => asset('img/cat/canas.jpg'),
            'senuelos' => asset('img/cat/senuelos.jpg'),
            'lineas' => asset('img/cat/lineas.jpg'),
        ];
        return $fallbacks[$slug] ?? asset('img/placeholder.jpg');
    }

    private function normalizeCategoryTitle(string $name, string $slug): string
    {
        $title = trim($name);

        // Defensa: nunca mostrar rutas/URLs como titulo de categoria.
        if (
            $title === '' ||
            preg_match('~^/?admin/|^https?://~i', $title) === 1 ||
            str_contains($title, '/')
        ) {
            $bySlug = [
                'reels' => 'Reels',
                'canas' => 'Canas',
                'senuelos' => 'Senuelos',
                'se-uelos' => 'Senuelos',
                'lineas' => 'Lineas',
            ];
            if (isset($bySlug[$slug])) {
                return $bySlug[$slug];
            }
            $guess = trim(str_replace('-', ' ', $slug));
            return $guess !== '' ? ucfirst($guess) : 'Categoria';
        }

        return $title;
    }

    private function resolveBrandCover(string $value, string $slug): string
    {
        $v = trim($value);
        if ($v !== '') return public_url($v);
        $fallbacks = [
            'kumasay' => asset('img/brands/kumasay.jpg'),
            'montague' => asset('img/brands/montague.jpg'),
            'gozio' => asset('img/brands/gozio_black.jpg'),
            'don-kb' => asset('img/brands/logo-DonKB.png'),
            'mustad' => asset('img/brands/mustad.webp'),
            'katashi' => asset('img/brands/katashi.jpg'),
        ];
        return $fallbacks[$slug] ?? asset('img/placeholder.jpg');
    }
}
