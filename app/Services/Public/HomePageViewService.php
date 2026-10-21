<?php
namespace App\Services\Public;

use App\Services\Integrations\YouTube\YouTubeService;
use App\Repositories\Catalog\BrandRepository;
use App\Repositories\Catalog\CategoryRepository;
use App\Repositories\Catalog\ProductReadRepository;
use App\Services\Catalog\ProductCardViewModelService;
use App\Services\Settings\HomeSectionsConfigService;

final class HomePageViewService
{
    private function variantPriceStatsByProduct(array $ids): array
    {
        if ($ids === []) return [];
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $st = db()->prepare(
            "SELECT v.product_id,
                    MIN(NULLIF(v.price, 0)) AS min_price,
                    MAX(NULLIF(v.price, 0)) AS max_price
               FROM product_variants v
              WHERE v.is_active = 1
                AND v.product_id IN ($ph)
              GROUP BY v.product_id"
        );
        $st->execute($ids);
        $rows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $out = [];
        foreach ($rows as $r) {
            $pid = (int)($r['product_id'] ?? 0);
            $min = (int)($r['min_price'] ?? 0);
            $max = (int)($r['max_price'] ?? 0);
            if ($pid > 0 && $min > 0 && $max > 0) {
                $out[$pid] = ['min' => $min, 'max' => $max];
            }
        }
        return $out;
    }

    private function buildProductRowsById(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn(int $v): bool => $v > 0)));
        if ($ids === []) return [];

        $statsByProduct = $this->variantPriceStatsByProduct($ids);

        $repo = new ProductReadRepository(db());
        $rows = $repo->findManyForCart($ids);
        $cards = new ProductCardViewModelService();
        $productRowsById = [];
        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);
            if ($id <= 0) continue;
            if ((int)($row['is_active'] ?? 0) !== 1) continue;
            $slug = trim((string)($row['slug'] ?? ''));
            if ($slug === '') continue;

            $card = $cards->build($row);
            $basePrice = (int)($row['price'] ?? 0);
            $stats = (array)($statsByProduct[$id] ?? []);
            $min = (int)($stats['min'] ?? 0);
            $max = (int)($stats['max'] ?? 0);

            if ($min > 0 && $max > $min) {
                $priceLabel = 'Desde ' . money($min);
            } elseif ($min > 0) {
                $priceLabel = money($min);
            } else {
                $priceLabel = money($basePrice);
            }

            $productRowsById[$id] = [
                'id' => $id,
                'title' => (string)($row['name'] ?? ''),
                'price' => $priceLabel,
                'img' => (string)($row['thumb'] ?? asset('img/placeholder.jpg')),
                'slug' => $slug,
                'card' => array_merge($card, [
                    'price_label' => $priceLabel,
                    'has_offer' => !empty($card['has_offer']),
                    'discount_pct' => (int)($card['discount_pct'] ?? 0),
                ]),
            ];
        }
        return $productRowsById;
    }

    private function buildRenderableSections(array $sections, array $productRowsById): array
    {
        if ($sections === [] || $productRowsById === []) return [];

        $renderable = [];
        foreach ($sections as $section) {
            if (!is_array($section)) continue;
            if (empty($section['enabled'])) continue;

            $items = [];
            foreach ((array)($section['product_ids'] ?? []) as $id) {
                $id = (int)$id;
                if ($id > 0 && isset($productRowsById[$id])) {
                    $items[] = $productRowsById[$id];
                }
            }
            if ($items === []) continue;

            $renderable[] = [
                'title' => (string)($section['title'] ?? 'Seccion'),
                'subtitle' => (string)($section['subtitle'] ?? 'Seleccionados para vos'),
                'badge' => (string)($section['badge'] ?? 'En stock'),
                'items' => $items,
            ];
        }

        return $renderable;
    }

    private function buildYoutubeUsedProducts(array $ids, array $productRowsById): array
    {
        $used = [];
        foreach ($ids as $id) {
            $id = (int)$id;
            if ($id <= 0 || !isset($productRowsById[$id])) continue;
            $row = (array)$productRowsById[$id];
            $slug = trim((string)($row['slug'] ?? ''));
            if ($slug === '') continue;

            $used[] = [
                'card' => (array)($row['card'] ?? []),
            ];
        }
        return $used;
    }

    public function build(): array
    {
        $homeConfig = (new HomeSectionsConfigService())->read((string)app_setting('home_sections_config', ''));
        $sections = is_array($homeConfig['sections'] ?? null) ? $homeConfig['sections'] : [];
        $youtubeCfg = is_array($homeConfig['youtube'] ?? null) ? $homeConfig['youtube'] : [];
        $youtubeProductIds = (array)($youtubeCfg['product_ids'] ?? []);
        $youtubeAvatarFallback = trim((string)($youtubeCfg['avatar_fallback_url'] ?? ''));

        $allIds = [];
        foreach ($sections as $section) {
            if (!is_array($section)) continue;
            foreach ((array)($section['product_ids'] ?? []) as $id) {
                $id = (int)$id;
                if ($id > 0) $allIds[] = $id;
            }
        }
        foreach ($youtubeProductIds as $id) {
            $id = (int)$id;
            if ($id > 0) $allIds[] = $id;
        }
        $productRowsById = $this->buildProductRowsById($allIds);

        $sectionsService = new HomeSectionsViewService(
            new BrandRepository(db()),
            new CategoryRepository(db())
        );

        $youtube = (new YouTubeService())->forHome();
        $youtube['channel']['fallback_thumbnail'] = $youtubeAvatarFallback !== '' ? url($youtubeAvatarFallback) : '';
        $youtube['used_products'] = $this->buildYoutubeUsedProducts($youtubeProductIds, $productRowsById);

        $heroDescription = trim((string)app_setting('home_hero_description', home_hero_description_default()));
        $legacyHeroDescription = 'Canas, reels, lineas y accesorios elegidos por pescadores. Calidad, garantia y asesoramiento real.';
        if ($heroDescription === '' || $heroDescription === $legacyHeroDescription) {
            $heroDescription = home_hero_description_default();
        }

        return [
            'pageTitle' => 'Mi Pesca & Aventura - Equipos, canas, reels y mas',
            'pageDesc' => 'Catalogo profesional de pesca deportiva. Envios a todo el pais. Ofertas, novedades y marcas lideres.',
            'heroSlides' => home_hero_slides_view(6),
            'heroTitle' => (string)app_setting('home_hero_title', 'Equipá tu próxima pesca con productos de calidad'),
            'heroDescription' => $heroDescription,
            'homeBrands' => $sectionsService->brands(6),
            'homeCategories' => $sectionsService->categories(4),
            'renderableSections' => $this->buildRenderableSections($sections, $productRowsById),
            'youtube' => $youtube,
        ];
    }
}
