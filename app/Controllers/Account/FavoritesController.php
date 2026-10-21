<?php

namespace App\Controllers\Account;

use App\Repositories\Account\ProductFavoriteRepository;
use App\Services\Catalog\ProductCardViewModelService;

final class FavoritesController
{
    private function slugify(string $value): string
    {
        $value = trim(mb_strtolower($value));
        if ($value === '') {
            return '';
        }
        $value = preg_replace('/[^\p{L}\p{N}]+/u', '-', $value) ?? '';
        return trim($value, '-');
    }

    /**
     * @param array<int,array<string,mixed>> $items
     * @return array<int,array{key:string,label:string,count:int}>
     */
    private function buildCategoryOptions(array $items): array
    {
        $seen = [];
        foreach ($items as $item) {
            $label = isset($item['category_name']) ? trim((string)$item['category_name']) : '';
            if ($label === '') {
                continue;
            }
            $key = $this->slugify($label);
            if ($key === '') {
                continue;
            }
            if (!isset($seen[$key])) {
                $seen[$key] = ['label' => $label, 'count' => 0];
            }
            $seen[$key]['count']++;
        }

        uasort($seen, static function (array $a, array $b): int {
            return strcasecmp((string)$a['label'], (string)$b['label']);
        });
        $options = [];
        foreach ($seen as $key => $meta) {
            $options[] = [
                'key' => (string)$key,
                'label' => (string)($meta['label'] ?? ''),
                'count' => (int)($meta['count'] ?? 0),
            ];
        }
        return $options;
    }

    /**
     * @param array<int,array<string,mixed>> $items
     * @return array<int,array<string,mixed>>
     */
    private function filterByCategory(array $items, string $selectedCategory): array
    {
        if ($selectedCategory === '') {
            return $items;
        }

        $out = [];
        foreach ($items as $item) {
            $label = isset($item['category_name']) ? (string)$item['category_name'] : '';
            if ($this->slugify($label) === $selectedCategory) {
                $out[] = $item;
            }
        }
        return $out;
    }

    public function legacyToggleRedirect(): void
    {
        redirect('/cuenta/favoritos');
    }

    public function index(): void
    {
        auth_required('/cuenta/favoritos');
        $userId = (int)($_SESSION['uid'] ?? 0);
        $items = [];
        $cards = [];
        $selectedCategory = $this->slugify((string)($_GET['category'] ?? ''));
        $categoryOptions = [];
        if ($userId > 0) {
            $items = (new ProductFavoriteRepository(db()))->listByUser($userId, 100);
            $categoryOptions = $this->buildCategoryOptions($items);
            $items = $this->filterByCategory($items, $selectedCategory);
            $cards = (new ProductCardViewModelService())->buildMany($items);
        }

        render_page('cuenta/favoritos', [
            'pageId' => 'account-favorites',
            'items' => $items,
            'cards' => $cards,
            'categoryOptions' => $categoryOptions,
            'selectedCategory' => $selectedCategory,
        ]);
    }

    public function toggle(): void
    {
        verify_csrf_or_abort();

        $userId = (int)($_SESSION['uid'] ?? 0);
        $nextRaw = trim((string)($_POST['next'] ?? ''));
        $next = sanitize_next_url($nextRaw, '/');
        if ($next === '/cuenta/favoritos/toggle') {
            $referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
            if ($referer !== '') {
                $parts = parse_url($referer);
                $path = (string)($parts['path'] ?? '/');
                $query = (string)($parts['query'] ?? '');
                $candidate = $path . ($query !== '' ? ('?' . $query) : '');
                $next = sanitize_next_url($candidate, '/');
            } else {
                $next = '/';
            }
        }

        if ($userId <= 0) {
            json([
                'ok' => false,
                'auth' => false,
                'login_url' => url('/login') . '?next=' . urlencode($next),
            ], 401);
        }

        $productId = (int)($_POST['product_id'] ?? 0);
        if ($productId <= 0) {
            json(['ok' => false, 'error' => 'producto_invalido'], 422);
        }

        $repo = new ProductFavoriteRepository(db());
        if (!$repo->productExists($productId)) {
            json(['ok' => false, 'error' => 'producto_no_encontrado'], 404);
        }

        $active = $repo->toggle($userId, $productId);
        json([
            'ok' => true,
            'active' => $active,
        ]);
    }
}
