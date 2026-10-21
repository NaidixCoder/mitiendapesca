<?php
namespace App\Services\Seo;

use App\Repositories\Seo\SeoRedirectRepository;
use App\Repositories\Seo\SeoSettingsRepository;
use App\Repositories\Seo\SeoSlugHistoryRepository;
use PDO;

/**
 * Lote 1: Contratos base para SEO PRO.
 * - Settings avanzados (canonical/robots/social/schema) via seo_settings
 * - Historial de slugs + redirects (301)
 *
 * Nota: La UI y la integración en front se implementan en lotes posteriores.
 */
final class SeoManagerService
{
    private SeoSettingsRepository $settingsRepo;
    private SeoRedirectRepository $redirectRepo;
    private SeoSlugHistoryRepository $slugRepo;

    public function __construct(PDO $db)
    {
        $this->settingsRepo = new SeoSettingsRepository($db);
        $this->redirectRepo = new SeoRedirectRepository($db);
        $this->slugRepo = new SeoSlugHistoryRepository($db);
    }

    public function getSettings(string $entityType, int $entityId): array
    {
        $row = $this->settingsRepo->get($entityType, $entityId);
        if (!$row) return [];
        return $this->adaptSettingsRowForUi($row);
    }

    public function saveSettings(string $entityType, int $entityId, array $data): void
    {
        $payload = $this->normalizeSettingsPayload($data);
        $this->settingsRepo->upsert($entityType, $entityId, $payload);
    }

    /**
     * Registra slug actual en historial (útil al crear entidad o al guardar).
     */
    public function registerSlug(string $entityType, int $entityId, string $slug): void
    {
        $slug = $this->normalizePathSlug($slug);
        if ($slug === '') return;
        $this->slugRepo->add($entityType, $entityId, $slug);
    }

    /**
     * Registra cambio de slug y crea redirect 301 desde el slug anterior al nuevo.
     * $basePrefix: ej "" o "/mi-pesca/public_html" según entorno.
     */
    public function recordSlugChange(string $entityType, int $entityId, string $oldSlug, string $newSlug, string $basePrefix = ''): void
    {
        $oldSlug = $this->normalizePathSlug($oldSlug);
        $newSlug = $this->normalizePathSlug($newSlug);
        if ($oldSlug === '' || $newSlug === '' || $oldSlug === $newSlug) return;

        $from = rtrim($basePrefix, '/') . '/' . ltrim($oldSlug, '/');
        $to   = rtrim($basePrefix, '/') . '/' . ltrim($newSlug, '/');

        // Guardar historial del viejo + nuevo (por si se alterna)
        $this->slugRepo->add($entityType, $entityId, $oldSlug);
        $this->slugRepo->add($entityType, $entityId, $newSlug);

        // Crear / actualizar 301 (evita pérdida SEO al cambiar URL)
        $this->redirectRepo->upsert($from, $to, 301);
    }

    /**
     * Productos (Catálogo): la URL pública es /producto?slug=...
     * Este método crea 301 desde el slug anterior al nuevo y registra historial.
     */
    public function recordProductSlugChange(int $productId, string $oldSlug, string $newSlug): void
    {
        $oldSlug = $this->normalizeSlugValue($oldSlug);
        $newSlug = $this->normalizeSlugValue($newSlug);
        if ($oldSlug === '' || $newSlug === '' || $oldSlug === $newSlug) return;

        $from = '/producto?slug='.$oldSlug;
        $to   = '/producto?slug='.$newSlug;

        // Historial (evita pérdida al volver atrás o auditar duplicados)
        $this->slugRepo->add('product', $productId, $oldSlug);
        $this->slugRepo->add('product', $productId, $newSlug);

        // Evitar cadenas: si ya había redirects que apuntaban al viejo canonical, los retargeteamos.
        $this->redirectRepo->retargetTo($from, $to);

        // 301 principal
        $this->redirectRepo->upsert($from, $to, 301);
    }

    /**
     * Categorías (landings SEO): la URL pública es /categoria?slug=...
     */
    public function recordCategorySlugChange(int $categoryId, string $oldSlug, string $newSlug): void
    {
        $oldSlug = $this->normalizeSlugValue($oldSlug);
        $newSlug = $this->normalizeSlugValue($newSlug);
        if ($oldSlug === '' || $newSlug === '' || $oldSlug === $newSlug) return;

        $from = '/categoria?slug='.$oldSlug;
        $to   = '/categoria?slug='.$newSlug;

        $this->slugRepo->add('category', $categoryId, $oldSlug);
        $this->slugRepo->add('category', $categoryId, $newSlug);

        $this->redirectRepo->retargetTo($from, $to);
        $this->redirectRepo->upsert($from, $to, 301);
    }

    /**
     * Marcas (landings SEO): la URL pública es /marca?slug=...
     */
    public function recordBrandSlugChange(int $brandId, string $oldSlug, string $newSlug): void
    {
        $oldSlug = $this->normalizeSlugValue($oldSlug);
        $newSlug = $this->normalizeSlugValue($newSlug);
        if ($oldSlug === '' || $newSlug === '' || $oldSlug === $newSlug) return;

        $from = '/marca?slug='.$oldSlug;
        $to   = '/marca?slug='.$newSlug;

        $this->slugRepo->add('brand', $brandId, $oldSlug);
        $this->slugRepo->add('brand', $brandId, $newSlug);

        $this->redirectRepo->retargetTo($from, $to);
        $this->redirectRepo->upsert($from, $to, 301);
    }


    /**
     * Normaliza el payload de settings para que matchee las columnas reales de `seo_settings`.
     *
     * Compatibilidad: hoy el Admin de productos envía keys legacy:
     * - robots ("index,follow"), canonical_override, og_image, schema_override_jsonld
     */
    private function normalizeSettingsPayload(array $data): array
    {
        $out = [];

        // canonical
        $canonical = $data['canonical_url'] ?? ($data['canonical_override'] ?? ($data['seo_canonical_override'] ?? null));
        if (is_string($canonical)) {
            $canonical = trim($canonical);
            if ($canonical === '') $canonical = null;
        }
        $out['canonical_url'] = $canonical;

        // robots (index/follow)
        $robotsIndex  = $data['robots_index'] ?? ($data['seo_robots_index'] ?? null);
        $robotsFollow = $data['robots_follow'] ?? ($data['seo_robots_follow'] ?? null);

        // Legacy "robots" string: "index,follow"
        $robotsStr = $data['robots'] ?? ($data['seo_robots'] ?? null);
        if ($robotsIndex === null || $robotsFollow === null) {
            if (is_string($robotsStr) && trim($robotsStr) !== '') {
                $r = strtolower(trim($robotsStr));
                $robotsIndex  = (str_contains($r, 'noindex')) ? 0 : 1;
                $robotsFollow = (str_contains($r, 'nofollow')) ? 0 : 1;
            }
        }
        $out['robots_index']  = ($robotsIndex === null) ? 1 : (int)(bool)$robotsIndex;
        $out['robots_follow'] = ($robotsFollow === null) ? 1 : (int)(bool)$robotsFollow;

        // OpenGraph
        foreach (['og_title','og_description'] as $k) {
            if (array_key_exists($k, $data)) {
                $v = trim((string)$data[$k]);
                $out[$k] = ($v === '') ? null : $v;
            }
        }
        $ogPath = $data['og_image_path'] ?? ($data['og_image'] ?? ($data['seo_og_image'] ?? null));
        if (is_string($ogPath)) {
            $ogPath = trim($ogPath);
            if ($ogPath === '') $ogPath = null;
        }
        $out['og_image_path'] = $ogPath;

        // Twitter
        $twitterCard = $data['twitter_card'] ?? ($data['seo_twitter_card'] ?? null);
        if (is_string($twitterCard)) {
            $twitterCard = trim($twitterCard);
            if ($twitterCard === '') $twitterCard = null;
        }
        if ($twitterCard !== null && !in_array($twitterCard, ['summary','summary_large_image'], true)) {
            $twitterCard = 'summary_large_image';
        }
        $out['twitter_card'] = $twitterCard ?? 'summary_large_image';

        foreach (['twitter_title','twitter_description'] as $k) {
            if (array_key_exists($k, $data)) {
                $v = trim((string)$data[$k]);
                $out[$k] = ($v === '') ? null : $v;
            }
        }
        $twPath = $data['twitter_image_path'] ?? ($data['twitter_image'] ?? ($data['seo_twitter_image'] ?? null));
        if (is_string($twPath)) {
            $twPath = trim($twPath);
            if ($twPath === '') $twPath = null;
        }
        $out['twitter_image_path'] = $twPath;

        // Schema override (JSON string)
        $schema = $data['schema_override'] ?? ($data['schema_override_jsonld'] ?? ($data['seo_schema_override_jsonld'] ?? null));
        if (is_string($schema)) {
            $schema = trim($schema);
            if ($schema === '') $schema = null;
        }
        $out['schema_override'] = $schema;

        return $out;
    }

    /**
     * Devuelve un array compatible con la UI actual (keys legacy) sin perder las columnas reales.
     */
    private function adaptSettingsRowForUi(array $row): array
    {
        $out = $row;

        $out['canonical_override'] = trim((string)($row['canonical_url'] ?? ''));

        $idx = isset($row['robots_index']) ? (int)$row['robots_index'] : 1;
        $fol = isset($row['robots_follow']) ? (int)$row['robots_follow'] : 1;
        $out['robots'] = ($idx === 0 ? 'noindex' : 'index') . ',' . ($fol === 0 ? 'nofollow' : 'follow');

        // UI espera paths (no URLs absolutas)
        $out['og_image'] = (string)($row['og_image_path'] ?? '');
        $out['twitter_image'] = (string)($row['twitter_image_path'] ?? '');

        $schema = $row['schema_override'] ?? null;
        if (is_array($schema) || is_object($schema)) {
            $schema = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $out['schema_override_jsonld'] = is_string($schema) ? (string)$schema : '';

        return $out;
    }

    private function normalizePathSlug(string $slug): string
    {
        $slug = trim($slug);
        $slug = preg_replace('~^/+~', '', $slug);
        $slug = preg_replace('~\s+~', '-', $slug);
        return $slug ?? '';
    }

    private function normalizeSlugValue(string $slug): string
    {
        $slug = trim($slug);
        $slug = preg_replace('~^/+~', '', $slug);
        $slug = preg_replace('~\s+~', '-', $slug);
        // El slug ya llega slugificado (ProductsController), esto es defensivo.
        return $slug ?? '';
    }
}
