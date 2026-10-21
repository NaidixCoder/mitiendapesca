<?php
namespace App\Controllers\Admin;

use App\Repositories\Catalog\ProductReadRepository;
use App\Repositories\Marketing\EmailAutomationRepository;
use App\Repositories\Settings\AppSettingsRepository;
use App\Repositories\Settings\HomeHeroSlideRepository;
use App\Services\Admin\Settings\ViewModels\SettingsEmailMarketingViewModel;
use App\Services\Admin\Settings\ViewModels\SettingsFiscalViewModel;
use App\Services\Admin\Settings\ViewModels\SettingsHomeHeroViewModel;
use App\Services\Catalog\ProductPublicationSyncService;
use App\Services\Settings\HomeSectionsConfigService;

class SettingsController extends BaseController {
    private function findProductsByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn(int $v): bool => $v > 0)));
        if ($ids === []) return [];

        $repo = new ProductReadRepository(db());
        $rows = $repo->findManyForCart($ids);
        $byId = [];
        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);
            if ($id <= 0) continue;
            $byId[$id] = [
                'id' => $id,
                'name' => (string)($row['name'] ?? ''),
                'sku' => (string)($row['sku'] ?? ''),
                'slug' => (string)($row['slug'] ?? ''),
                'thumb' => (string)($row['thumb'] ?? ''),
            ];
        }

        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) $ordered[] = $byId[$id];
        }
        return $ordered;
    }

    private function renderHomeHero(): void
    {
        $db = db();
        $settings = new AppSettingsRepository($db);
        $repo = new HomeHeroSlideRepository($db);
        $rows = (new SettingsHomeHeroViewModel())->buildRows($repo->listAll(), 4);
        render_page('admin/settings/home_hero', [
            'tab' => 'design',
            'designSection' => 'home-hero',
            'rows' => $rows,
            'heroTitle' => (string)$settings->get('home_hero_title', 'Equipá tu próxima pesca con productos de calidad'),
            'heroDescription' => (string)$settings->get('home_hero_description', home_hero_description_default()),
            'pageId' => 'admin-settings-home-hero',
        ]);
    }

    private function saveHomeHero(): void
    {
        $db = db();
        $settings = new AppSettingsRepository($db);
        $repo = new HomeHeroSlideRepository($db);

        $titles = (array)($_POST['slide_title'] ?? []);
        $alts = (array)($_POST['slide_alt'] ?? []);
        $desktopImages = (array)($_POST['slide_image_desktop'] ?? []);
        $mobileImages = (array)($_POST['slide_image_mobile'] ?? []);
        $urls = (array)($_POST['slide_url'] ?? []);
        $activeFlags = (array)($_POST['slide_active'] ?? []);
        $sortOrders = (array)($_POST['slide_sort'] ?? []);
        $startsAt = (array)($_POST['slide_starts_at'] ?? []);
        $endsAt = (array)($_POST['slide_ends_at'] ?? []);

        $heroTitle = trim((string)($_POST['home_hero_title'] ?? ''));
        $heroDescription = trim((string)($_POST['home_hero_description'] ?? ''));
        if ($heroTitle === '') {
            $heroTitle = 'Equipá tu próxima pesca con productos de calidad';
        }
        if ($heroDescription === '') {
            $heroDescription = home_hero_description_default();
        }

        $toDateTime = static function ($value): ?string {
            $s = trim((string)$value);
            if ($s === '') return null;
            $s = str_replace('T', ' ', $s);
            if (strlen($s) === 16) $s .= ':00';
            $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $s);
            if (!$dt) return null;
            return $dt->format('Y-m-d H:i:s');
        };

        $toWebAssetUrl = static function ($value): string {
            $s = trim((string)$value);
            if ($s === '') return '';
            $s = str_replace('\\', '/', $s);
            if (preg_match('~^https?://~i', $s)) return $s;
            $posPublic = stripos($s, '/public_html/');
            if ($posPublic !== false) {
                $s = substr($s, $posPublic + strlen('/public_html/'));
            }
            if (str_starts_with($s, 'assets/')) return url('/' . ltrim($s, '/'));
            if (str_starts_with($s, 'img/')) return asset($s);
            if (str_starts_with($s, '/assets/')) return url($s);
            if (str_starts_with($s, '/img/')) return url('/assets' . $s);
            return url('/assets/' . ltrim($s, '/'));
        };

        $toTargetUrl = static function ($value): string {
            $s = trim((string)$value);
            if ($s === '') return '';
            $s = str_replace('\\', '/', $s);
            if (preg_match('~^https?://~i', $s)) return $s;
            return url('/' . ltrim($s, '/'));
        };

        $rows = [];
        $indices = array_unique(array_merge(
            array_keys($desktopImages),
            array_keys($urls),
            array_keys($titles)
        ));

        foreach ($indices as $idx) {
            $desktop = $toWebAssetUrl($desktopImages[$idx] ?? '');
            $url = trim((string)($urls[$idx] ?? ''));
            $title = trim((string)($titles[$idx] ?? ''));
            $alt = trim((string)($alts[$idx] ?? ''));
            $mobile = $toWebAssetUrl($mobileImages[$idx] ?? '');

            if ($desktop === '' && $url === '' && $title === '') continue;
            if ($desktop === '' || $url === '') continue;

            $rows[] = [
                'is_active' => isset($activeFlags[$idx]) ? 1 : 0,
                'title' => $title,
                'alt_text' => $alt,
                'image_desktop' => $desktop,
                'image_mobile' => $mobile,
                'target_url' => $toTargetUrl($url),
                'sort_order' => (int)($sortOrders[$idx] ?? 0),
                'starts_at' => $toDateTime($startsAt[$idx] ?? ''),
                'ends_at' => $toDateTime($endsAt[$idx] ?? ''),
            ];
        }

        usort($rows, static fn(array $a, array $b): int => ($a['sort_order'] <=> $b['sort_order']));
        try {
            $settings->set('home_hero_title', $heroTitle);
            $settings->set('home_hero_description', $heroDescription);
            $repo->replaceAll($rows);
            flash('ok', 'Hero del home actualizado.');
        } catch (\Throwable $e) {
            flash('error', 'No se pudo guardar el hero. Verifica migraciones de base de datos.');
        }
        redirect(url('/admin/settings/design/home-hero'));
    }

    public function designHomeHero(): void
    {
        $this->renderHomeHero();
    }

    public function designHomeSections(): void
    {
        $db = db();
        $settings = new AppSettingsRepository($db);
        $configService = new HomeSectionsConfigService();
        $config = $configService->read($settings->get('home_sections_config', ''));

        $sections = is_array($config['sections'] ?? null) ? $config['sections'] : [];
        $youtube = is_array($config['youtube'] ?? null) ? $config['youtube'] : [];
        $youtubeProductIds = (array)($youtube['product_ids'] ?? []);
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
        $allIds = array_values(array_unique($allIds));

        $productsById = [];
        foreach ($this->findProductsByIds($allIds) as $product) {
            $productsById[(int)$product['id']] = $product;
        }

        $sectionProductsByKey = [];
        $youtubeProducts = [];
        foreach ($sections as $section) {
            if (!is_array($section)) continue;
            $key = (string)($section['key'] ?? '');
            if ($key === '') continue;
            $sectionProductsByKey[$key] = [];
            foreach ((array)($section['product_ids'] ?? []) as $id) {
                $id = (int)$id;
                if ($id > 0 && isset($productsById[$id])) {
                    $sectionProductsByKey[$key][] = $productsById[$id];
                }
            }
        }

        foreach ($youtubeProductIds as $id) {
            $id = (int)$id;
            if ($id > 0 && isset($productsById[$id])) {
                $youtubeProducts[] = $productsById[$id];
            }
        }

        render_page('admin/settings/home_sections', [
            'tab' => 'design',
            'designSection' => 'home-sections',
            'pageId' => 'admin-settings-home-sections',
            'config' => $config,
            'sections' => $sections,
            'sectionProductsByKey' => $sectionProductsByKey,
            'youtubeProducts' => $youtubeProducts,
            'youtubeAvatarFallbackUrl' => (string)($youtube['avatar_fallback_url'] ?? ''),
        ]);
    }

    private function uploadHomeSectionsImage(array $file, string $subdir = 'youtube'): string
    {
        $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Upload invalido.');
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        $size = (int)($file['size'] ?? 0);
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new \RuntimeException('Archivo temporal invalido.');
        }
        if ($size <= 0 || $size > 4 * 1024 * 1024) {
            throw new \RuntimeException('La imagen supera el maximo de 4MB.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($tmp);
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];
        if (!isset($map[$mime])) {
            throw new \RuntimeException('Formato no permitido (JPG/PNG/WebP/GIF).');
        }

        $name = bin2hex(random_bytes(16)) . '.' . $map[$mime];
        $dir = base_path('public_html/uploads/home-sections/' . trim($subdir, '/\\'));
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $dest = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $name;
        if (!move_uploaded_file($tmp, $dest)) {
            throw new \RuntimeException('No se pudo guardar la imagen.');
        }

        return '/uploads/home-sections/' . trim($subdir, '/\\') . '/' . $name;
    }

    private function deleteLocalUploadIfManaged(?string $raw, array $allowedPrefixes = ['/uploads/home-sections/']): void
    {
        $value = trim((string)$raw);
        if ($value === '') return;

        $path = (string)(parse_url($value, PHP_URL_PATH) ?? '');
        if ($path === '') $path = $value;

        $basePath = rtrim((string)(parse_url(url('/'), PHP_URL_PATH) ?? ''), '/');
        if ($basePath !== '' && str_starts_with($path, $basePath . '/')) {
            $path = substr($path, strlen($basePath));
        }

        $allowed = false;
        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $allowed = true;
                break;
            }
        }
        if (!$allowed) return;

        $full = base_path('public_html' . str_replace('/', DIRECTORY_SEPARATOR, $path));
        if (is_file($full)) {
            @unlink($full);
        }
    }

    public function saveDesignHomeSections(): void
    {
        verify_csrf_or_abort();

        $db = db();
        $settings = new AppSettingsRepository($db);
        $configService = new HomeSectionsConfigService();
        $keys = (array)($_POST['section_key'] ?? []);
        $titles = (array)($_POST['section_title'] ?? []);
        $subtitles = (array)($_POST['section_subtitle'] ?? []);
        $badges = (array)($_POST['section_badge'] ?? []);
        $enabled = (array)($_POST['section_enabled'] ?? []);
        $productIds = (array)($_POST['section_product_ids'] ?? []);
        $youtubeProductIds = $configService->sanitizeProductIds($_POST['youtube_product_ids'] ?? '');
        $youtubeAvatarFallbackUrl = $configService->sanitizeAssetUrl($_POST['youtube_avatar_fallback_url'] ?? '');
        $removeYoutubeAvatarFallback = !empty($_POST['youtube_avatar_fallback_remove']);

        $current = $configService->read($settings->get('home_sections_config', ''));
        $currentYoutube = is_array($current['youtube'] ?? null) ? $current['youtube'] : [];
        $currentYoutubeAvatarFallback = $configService->sanitizeAssetUrl($currentYoutube['avatar_fallback_url'] ?? '');

        if (!empty($_FILES['youtube_avatar_fallback']) && is_array($_FILES['youtube_avatar_fallback'])) {
            $file = $_FILES['youtube_avatar_fallback'];
            $uploadErr = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($uploadErr !== UPLOAD_ERR_NO_FILE) {
                try {
                    $youtubeAvatarFallbackUrl = $this->uploadHomeSectionsImage($file, 'youtube');
                    if ($currentYoutubeAvatarFallback !== '' && $currentYoutubeAvatarFallback !== $youtubeAvatarFallbackUrl) {
                        $this->deleteLocalUploadIfManaged($currentYoutubeAvatarFallback);
                    }
                    $removeYoutubeAvatarFallback = false;
                } catch (\Throwable $e) {
                    flash('error', $e->getMessage());
                    redirect(url('/admin/settings/design/home-sections'));
                    return;
                }
            }
        }

        if ($youtubeAvatarFallbackUrl === '') {
            $youtubeAvatarFallbackUrl = $currentYoutubeAvatarFallback;
        }
        if ($removeYoutubeAvatarFallback) {
            if ($currentYoutubeAvatarFallback !== '') {
                $this->deleteLocalUploadIfManaged($currentYoutubeAvatarFallback);
            }
            $youtubeAvatarFallbackUrl = '';
        }

        $indices = array_values(array_unique(array_merge(
            array_keys($keys),
            array_keys($titles),
            array_keys($subtitles),
            array_keys($badges),
            array_keys($enabled),
            array_keys($productIds)
        )));

        $sections = [];
        foreach ($indices as $idx) {
            $title = trim((string)($titles[$idx] ?? ''));
            $subtitle = trim((string)($subtitles[$idx] ?? ''));
            $badge = trim((string)($badges[$idx] ?? ''));
            $ids = $configService->sanitizeProductIds($productIds[$idx] ?? '');
            if ($title === '' && $ids === []) {
                continue;
            }

            $sections[] = [
                'key' => (string)($keys[$idx] ?? ('section_' . ((int)$idx + 1))),
                'template' => 'products_grid',
                'enabled' => isset($enabled[$idx]) ? 1 : 0,
                'title' => $title !== '' ? $title : 'Seccion',
                'subtitle' => $subtitle !== '' ? $subtitle : 'Seleccionados para vos',
                'badge' => $badge !== '' ? $badge : 'En stock',
                'product_ids' => $ids,
            ];
        }

        $payload = $configService->normalize([
            'sections' => array_values($sections),
            'youtube' => [
                'product_ids' => $youtubeProductIds,
                'avatar_fallback_url' => $youtubeAvatarFallbackUrl,
            ],
        ]);

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json) || $json === '') {
            flash('error', 'No se pudo serializar la configuracion.');
            redirect(url('/admin/settings/design/home-sections'));
            return;
        }

        $ok = $settings->set('home_sections_config', $json);
        if (!$ok) {
            flash('error', 'No se pudo guardar la configuracion de secciones.');
            redirect(url('/admin/settings/design/home-sections'));
            return;
        }

        flash('ok', 'Secciones del Home actualizadas.');
        redirect(url('/admin/settings/design/home-sections'));
    }

    public function saveDesignHomeHero(): void
    {
        verify_csrf_or_abort();
        $this->saveHomeHero();
    }

    public function uploadHomeHeroImage(): void
    {
        if (!verify_csrf()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'CSRF'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        if (empty($_FILES['image']) || !is_array($_FILES['image'])) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Falta archivo (image)'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $file = $_FILES['image'];
        $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_OK) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Upload invalido', 'code' => $err], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        $size = (int)($file['size'] ?? 0);
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Archivo temporal invalido'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        if ($size <= 0 || $size > 4 * 1024 * 1024) {
            http_response_code(413);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'La imagen supera el maximo de 4MB'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($tmp);
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];
        if (!isset($map[$mime])) {
            http_response_code(415);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Formato no permitido (JPG/PNG/WebP/GIF)'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $ext = $map[$mime];
        $name = bin2hex(random_bytes(16)) . '.' . $ext;

        $dir = base_path('public_html/uploads/home-hero');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $dest = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $name;
        if (!move_uploaded_file($tmp, $dest)) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'No se pudo guardar la imagen'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'url' => url('/uploads/home-hero/' . $name),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function deleteHomeHeroImage(): void
    {
        if (!verify_csrf()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'CSRF'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $raw = trim((string)($_POST['url'] ?? ''));
        if ($raw === '') {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'URL vacia'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $path = (string)(parse_url($raw, PHP_URL_PATH) ?? '');
        if ($path === '') {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'URL invalida'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $basePath = rtrim((string)(parse_url(url('/'), PHP_URL_PATH) ?? ''), '/');
        if ($basePath !== '' && str_starts_with($path, $basePath . '/')) {
            $path = substr($path, strlen($basePath));
        }

        $allowed = (
            str_starts_with($path, '/uploads/home-hero/') ||
            str_starts_with($path, '/uploads/email/')
        );
        if (!$allowed) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Solo se pueden eliminar imagenes de uploads'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $full = base_path('public_html' . str_replace('/', DIRECTORY_SEPARATOR, $path));
        $deleted = false;
        if (is_file($full)) {
            $deleted = @unlink($full) === true;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'deleted' => $deleted,
            'path' => $path,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function index(){
        $tab = (string)($_GET['tab'] ?? 'email-marketing');
        $tab = trim($tab);
        if ($tab === '') $tab = 'email-marketing';

        if ($tab === 'email-marketing') {
            $db = db();
            $settings = new AppSettingsRepository($db);
            $repo = new EmailAutomationRepository($db);

            // Asegura que existan automatizaciones base.
            // IMPORTANTE: ensureDefaults NO debe pisar delays custom (ver repo).
            try { $repo->ensureDefaults(); } catch (\Throwable $e) { /* best-effort */ }

            $rows = [];
            try { $rows = $repo->listAutomationsWithSteps(); } catch (\Throwable $e) { /* best-effort */ }

            $cfg = [
                'double_optin' => $settings->get('email_marketing_double_optin', null),
                'sched_limit'  => $settings->get('email_marketing_scheduler_limit', ''),
                'work_limit'   => $settings->get('email_marketing_worker_limit', ''),
                'broadcast_limit' => $settings->get('email_marketing_broadcast_limit', ''),
                'open_tracking' => $settings->get('email_marketing_open_tracking', '0'),
                'cap_per_day' => $settings->get('email_marketing_cap_per_day', ''),
                'cap_per_week' => $settings->get('email_marketing_cap_per_week', ''),
                // Etapa 5 (fallback): runner vía web con token
                'web_runner_enabled' => $settings->get('email_marketing_web_runner_enabled', '0'),
                'web_runner_token'   => $settings->get('email_marketing_web_runner_token', ''),
            ];

            $emailMarketingPayload = (new SettingsEmailMarketingViewModel())->build($cfg, $rows);

            render_page('admin/settings/email_marketing', [
                'tab' => 'email-marketing',
                'cfg' => $cfg,
                'automationRows' => $rows,
                'settings_email_marketing_payload' => $emailMarketingPayload,
            ]);
            return;
        }

        if ($tab === 'fiscal') {
            $db = db();
            $settings = new AppSettingsRepository($db);

            $cfg = [
                'seller_tax_regime' => $settings->get('fiscal_seller_tax_regime', 'monotributo'),
                'seller_cuit' => $settings->get('fiscal_seller_cuit', ''),
                'seller_business_name' => $settings->get('fiscal_seller_business_name', ''),
                'seller_fiscal_address' => $settings->get('fiscal_seller_fiscal_address', ''),
                // ARCA (facturación electrónica)
                'arca_env' => $settings->get('arca_env', 'homologacion'),
                'arca_pto_vta' => $settings->get('arca_pto_vta', '1'),
                'arca_auto_emit' => $settings->get('arca_auto_emit', '0'),
                'arca_auto_emit_trigger' => $settings->get('arca_auto_emit_trigger', 'paid'),
                'arca_ws_service' => $settings->get('arca_ws_service', 'wsfe'),

                // M3: Conciliación (qué estados se consideran facturables / no facturables)
                // Guardamos como CSV para evitar migraciones. Defaults prudentes.
                'arca_facturable_statuses' => $settings->get('arca_facturable_statuses', 'paid,processing,packed,shipped,delivered'),
                'arca_non_facturable_statuses' => $settings->get('arca_non_facturable_statuses', 'pending_payment,payment_failed,payment_expired,cancelled,chargeback,refunded'),
            ];

            $fiscalPayload = (new SettingsFiscalViewModel())->build($cfg);

            render_page('admin/settings/fiscal', [
                'tab' => 'fiscal',
                'cfg' => $cfg,
                'fiscal_payload' => $fiscalPayload,
            ]);
            return;
        }

        if ($tab === 'general') {
            $db = db();
            $settings = new AppSettingsRepository($db);

            $cfg = [
                'catalog_hide_inactive_category_products' => $settings->get('catalog_hide_inactive_category_products', '0'),
                'catalog_allow_publish_without_images' => $settings->get('catalog_allow_publish_without_images', '0'),
                'catalog_show_empty_categories' => $settings->get('catalog_show_empty_categories', '0'),
                'catalog_show_empty_brands' => $settings->get('catalog_show_empty_brands', '0'),
                'contact_support_email' => $settings->get('contact_support_email', ''),
                'social_instagram_url' => $settings->get('social_instagram_url', ''),
                'social_facebook_url' => $settings->get('social_facebook_url', ''),
                'social_youtube_url' => $settings->get('social_youtube_url', ''),
                'social_whatsapp_url' => $settings->get('social_whatsapp_url', ''),
            ];
            render_page('admin/settings/general', ['tab' => 'general', 'cfg' => $cfg]);
            return;
        }

        if ($tab === 'pagos') {
            $db = db();
            $settings = new AppSettingsRepository($db);

            $cfg = [
                'pricing_installments_enabled' => $settings->get('pricing_installments_enabled', '1'),
                'pricing_installments_count' => $settings->get('pricing_installments_count', '3'),
                'pricing_interest_rate_percent' => $settings->get('pricing_interest_rate_percent', '0'),
                'pricing_debit_discount_percent' => $settings->get('pricing_debit_discount_percent', '0'),
                'payments_transfer_reservation_hours' => $settings->get('payments_transfer_reservation_hours', '24'),
            ];
            render_page('admin/settings/pagos', ['tab' => 'pagos', 'cfg' => $cfg]);
            return;
        }

        if ($tab === 'home-hero') {
            // Compatibilidad URL vieja
            redirect(url('/admin/settings/design/home-hero'));
            return;
        }


        
        if ($tab === "mayoristas") {
            $db = db();
            $settings = new AppSettingsRepository($db);

            // Ver (GET)
            $cfg = [
                'wh_enabled' => $settings->get('wh_enabled', '0'),
                'wh1_discount_percent' => $settings->get('wh1_discount_percent', ''),
                'wh2_discount_percent' => $settings->get('wh2_discount_percent', ''),
                'wh_round_enabled' => $settings->get('wh_round_enabled', '0'),
                'wh_round_to' => $settings->get('wh_round_to', '0'),
                'wh_round_mode' => $settings->get('wh_round_mode', 'round'),
            ];

            render_page('admin/settings/mayoristas', [
                'tab' => 'mayoristas',
                'cfg' => $cfg,
            ]);
            return;
        }

if ($tab === 'envios') {
            $db = db();
            $settings = new AppSettingsRepository($db);

            $cfg = [
                'correo_enabled' => $settings->get('correo_enabled', '0'),
                'correo_mode' => $settings->get('correo_mode', 'test'),
                'correo_use_env' => $settings->get('correo_use_env', '1'),

                // Si NO usan env (no recomendado), se guardan en app_settings.
                'correo_api_username' => $settings->get('correo_api_username', ''),
                'correo_api_password' => $settings->get('correo_api_password', ''),
                'correo_user_email' => $settings->get('correo_user_email', ''),
                'correo_user_password' => $settings->get('correo_user_password', ''),

                // Cache operativo
                'correo_customer_id' => $settings->get('correo_customer_id', ''),

                // QA/Dev: mock import (solo TEST)
                'correo_mock_import' => $settings->get('correo_mock_import', '0'),

                // Checkout / Cotización
                'shipping_origin_postal_code' => $settings->get('shipping_origin_postal_code', ''),
                'shipping_free_min_amount' => $settings->get('shipping_free_min_amount', '0'),
                'shipping_local_pickup_enabled' => $settings->get('shipping_local_pickup_enabled', '1'),
                'shipping_local_pickup_label' => $settings->get('shipping_local_pickup_label', 'Retirar en el local'),
                'shipping_local_pickup_address' => $settings->get('shipping_local_pickup_address', ''),
                'shipping_local_pickup_notes' => $settings->get('shipping_local_pickup_notes', ''),
                'shipping_local_pickup_cost' => $settings->get('shipping_local_pickup_cost', '0'),
                'shipping_local_pickup_maps_url' => $settings->get('shipping_local_pickup_maps_url', ''),
                'shipping_local_pickup_place_id' => $settings->get('shipping_local_pickup_place_id', ''),
                'shipping_local_pickup_map_lat' => $settings->get('shipping_local_pickup_map_lat', ''),
                'shipping_local_pickup_map_lng' => $settings->get('shipping_local_pickup_map_lng', ''),
                'shipping_local_pickup_map_zoom' => $settings->get('shipping_local_pickup_map_zoom', '17'),
                'shipping_default_weight' => $settings->get('shipping_default_weight', '1000'),
                'shipping_default_height' => $settings->get('shipping_default_height', '10'),
                'shipping_default_width'  => $settings->get('shipping_default_width', '10'),
                'shipping_default_length' => $settings->get('shipping_default_length', '10'),
                'shipping_packaging_extra_cm' => $settings->get('shipping_packaging_extra_cm', '0'),
                'shipping_packaging_extra_weight_pct' => $settings->get('shipping_packaging_extra_weight_pct', '0'),

                // Remitente fijo (FIX517)
                'shipping_sender_name' => $settings->get('shipping_sender_name', ''),
                'shipping_sender_email' => $settings->get('shipping_sender_email', ''),
                'shipping_sender_phone' => $settings->get('shipping_sender_phone', ''),
                'shipping_sender_cellphone' => $settings->get('shipping_sender_cellphone', ''),
                'shipping_sender_street' => $settings->get('shipping_sender_street', ''),
                'shipping_sender_number' => $settings->get('shipping_sender_number', ''),
                'shipping_sender_city' => $settings->get('shipping_sender_city', ''),
                'shipping_sender_state' => $settings->get('shipping_sender_state', ''),
                'shipping_sender_zip' => $settings->get('shipping_sender_zip', ''),
                'shipping_sender_floor' => $settings->get('shipping_sender_floor', ''),
                'shipping_sender_apartment' => $settings->get('shipping_sender_apartment', ''),
            ];

            render_page('admin/settings/envios', [
                'tab' => 'envios',
                'cfg' => $cfg,
                'pageId' => 'admin-settings-envios',
            ]);
            return;
        }

        if ($tab === 'general') {
            $db = db();
            $settings = new AppSettingsRepository($db);

            $cfg = [
                // Catálogo
                // Híbrido: si está ON, se ocultan en el sitio los productos cuya categoría esté inactiva.
                'catalog_hide_inactive_category_products' => $settings->get('catalog_hide_inactive_category_products', '0'),
                'catalog_allow_publish_without_images' => $settings->get('catalog_allow_publish_without_images', '0'),
                'catalog_show_empty_categories' => $settings->get('catalog_show_empty_categories', '0'),
                'catalog_show_empty_brands' => $settings->get('catalog_show_empty_brands', '0'),
                'contact_support_email' => $settings->get('contact_support_email', ''),
                'social_instagram_url' => $settings->get('social_instagram_url', ''),
                'social_facebook_url' => $settings->get('social_facebook_url', ''),
                'social_youtube_url' => $settings->get('social_youtube_url', ''),
                'social_whatsapp_url' => $settings->get('social_whatsapp_url', ''),
                'pricing_installments_enabled' => $settings->get('pricing_installments_enabled', '1'),
                'pricing_installments_count' => $settings->get('pricing_installments_count', '3'),
                'pricing_interest_rate_percent' => $settings->get('pricing_interest_rate_percent', '0'),
                'pricing_debit_discount_percent' => $settings->get('pricing_debit_discount_percent', '0'),
            ];

            render_page('admin/settings/general', [
                'tab' => 'general',
                'cfg' => $cfg,
            ]);
            return;
        }

        render_page('admin/settings/index', [
            'tab' => $tab,
        ]);
    }

    public function save(): void
    {
        verify_csrf_or_abort();
        $tab = (string)($_GET['tab'] ?? $_POST['tab'] ?? 'email-marketing');
        $tab = trim($tab);

        if ($tab === 'general') {
            $db = db();
            $settings = new AppSettingsRepository($db);
            $normalizeUrl = static function ($value): string {
                $url = trim((string)$value);
                if ($url === '') return '';
                if (preg_match('~^https?://~i', $url)) return $url;
                return 'https://' . ltrim($url, '/');
            };

            $prevAllowWithoutImages = ((string)($settings->get('catalog_allow_publish_without_images', '0') ?? '0') === '1');
            $val = isset($_POST['catalog_hide_inactive_category_products']) ? '1' : '0';
            $settings->set('catalog_hide_inactive_category_products', $val);
            $newAllowWithoutImages = isset($_POST['catalog_allow_publish_without_images']);
            $settings->set('catalog_allow_publish_without_images', $newAllowWithoutImages ? '1' : '0');
            $settings->set('catalog_show_empty_categories', isset($_POST['catalog_show_empty_categories']) ? '1' : '0');
            $settings->set('catalog_show_empty_brands', isset($_POST['catalog_show_empty_brands']) ? '1' : '0');
            $settings->set('contact_support_email', trim((string)($_POST['contact_support_email'] ?? '')));
            $settings->set('social_instagram_url', $normalizeUrl($_POST['social_instagram_url'] ?? ''));
            $settings->set('social_facebook_url', $normalizeUrl($_POST['social_facebook_url'] ?? ''));
            $settings->set('social_youtube_url', $normalizeUrl($_POST['social_youtube_url'] ?? ''));
            $settings->set('social_whatsapp_url', $normalizeUrl($_POST['social_whatsapp_url'] ?? ''));

            if ($prevAllowWithoutImages !== $newAllowWithoutImages) {
                (new ProductPublicationSyncService($db))->reconcileAll($newAllowWithoutImages);
            }

            flash('success', 'Ajustes guardados.');
            redirect(url('/admin/settings?tab=general'));
            return;
        }

        if ($tab === 'pagos') {
            $db = db();
            $settings = new AppSettingsRepository($db);

            $settings->set('pricing_installments_enabled', isset($_POST['pricing_installments_enabled']) ? '1' : '0');

            $count = (int)($_POST['pricing_installments_count'] ?? 3);
            if ($count < 2) $count = 3;
            if ($count > 24) $count = 24;
            $settings->set('pricing_installments_count', (string)$count);

            $rate = trim((string)($_POST['pricing_interest_rate_percent'] ?? '0'));
            $rate = str_replace(',', '.', $rate);
            $rateFloat = max(0, (float)$rate);
            $settings->set('pricing_interest_rate_percent', rtrim(rtrim(number_format($rateFloat, 2, '.', ''), '0'), '.'));

            $debitDiscount = trim((string)($_POST['pricing_debit_discount_percent'] ?? '0'));
            $debitDiscount = str_replace(',', '.', $debitDiscount);
            $debitDiscountFloat = max(0, min(100, (float)$debitDiscount));
            $settings->set('pricing_debit_discount_percent', rtrim(rtrim(number_format($debitDiscountFloat, 2, '.', ''), '0'), '.'));

            $reservationHours = (int)($_POST['payments_transfer_reservation_hours'] ?? 24);
            if ($reservationHours < 1) $reservationHours = 24;
            if ($reservationHours > 720) $reservationHours = 720;
            $settings->set('payments_transfer_reservation_hours', (string)$reservationHours);

            flash('success', 'Ajustes guardados.');
            redirect(url('/admin/settings?tab=pagos'));
            return;
        }

        if ($tab === 'home-hero') {
            // Compatibilidad URL vieja
            $this->saveHomeHero();
            return;
        }

        if ($tab === 'fiscal') {
            $db = db();
            $settings = new AppSettingsRepository($db);

            $regime = strtolower(trim((string)($_POST['seller_tax_regime'] ?? 'monotributo')));
            $allowed = ['monotributo','responsable_inscripto','exento','no_facturar'];
            if (!in_array($regime, $allowed, true)) $regime = 'monotributo';

            $cuit = trim((string)($_POST['seller_cuit'] ?? ''));
            $name = trim((string)($_POST['seller_business_name'] ?? ''));
            $addr = trim((string)($_POST['seller_fiscal_address'] ?? ''));

            $settings->set('fiscal_seller_tax_regime', $regime);
            $settings->set('fiscal_seller_cuit', $cuit);
            $settings->set('fiscal_seller_business_name', $name);
            $settings->set('fiscal_seller_fiscal_address', $addr);

            // ARCA (facturación electrónica) — no guardamos secretos aquí
            $arcaEnv = strtolower(trim((string)($_POST['arca_env'] ?? 'homologacion')));
            if (!in_array($arcaEnv, ['homologacion','produccion'], true)) $arcaEnv = 'homologacion';
            $ptoVta = (int)($_POST['arca_pto_vta'] ?? 1);
            if ($ptoVta <= 0) $ptoVta = 1;
            $autoEmit = isset($_POST['arca_auto_emit']) ? '1' : '0';
            if ($regime === 'no_facturar') {
                $autoEmit = '0';
            }

            $trigger = strtolower(trim((string)($_POST['arca_auto_emit_trigger'] ?? 'paid')));
            if (!in_array($trigger, ['paid','shipped'], true)) $trigger = 'paid';
            // Si está apagada la auto-emisión, igual persistimos trigger por si luego lo prenden.
            if ($regime === 'no_facturar') {
                $trigger = 'paid';
            }

            $wsService = trim((string)($_POST['arca_ws_service'] ?? 'wsfe'));
            if ($wsService === '') $wsService = 'wsfe';

            // M3: Estados facturables / no facturables (para conciliación)
            // Se aceptan como arrays (checkboxes) o como string CSV.
            $readCsv = static function($v): string {
                if (is_array($v)) {
                    $vals = array_values(array_filter(array_map('strval', $v), fn($x) => trim($x) !== ''));
                    $vals = array_map(fn($x) => strtolower(trim($x)), $vals);
                    $vals = array_values(array_unique($vals));
                    return implode(',', $vals);
                }
                $s = strtolower(trim((string)$v));
                $s = preg_replace('/\s+/', '', $s);
                return $s;
            };

            $facturableCsv = $readCsv($_POST['arca_facturable_statuses'] ?? '');
            $noFacturableCsv = $readCsv($_POST['arca_non_facturable_statuses'] ?? '');

            // Defaults si vacíos
            if ($facturableCsv === '') $facturableCsv = 'paid,processing,packed,shipped,delivered';
            if ($noFacturableCsv === '') $noFacturableCsv = 'pending_payment,payment_failed,payment_expired,cancelled,chargeback,refunded';

            // Regla simple: que no haya intersección (si la hay, prioriza facturable)
            $factArr = array_values(array_filter(explode(',', $facturableCsv)));
            $noArr = array_values(array_filter(explode(',', $noFacturableCsv)));
            $noArr = array_values(array_diff($noArr, $factArr));
            $noFacturableCsv = implode(',', $noArr);

            $settings->set('arca_env', $arcaEnv);
            $settings->set('arca_pto_vta', (string)$ptoVta);
            $settings->set('arca_auto_emit', $autoEmit);
            $settings->set('arca_auto_emit_trigger', $trigger);
            $settings->set('arca_ws_service', $wsService);

            $settings->set('arca_facturable_statuses', $facturableCsv);
            $settings->set('arca_non_facturable_statuses', $noFacturableCsv);

            // Checkout / Cotización (FIX516)
            $settings->set('shipping_origin_postal_code', trim((string)($_POST['shipping_origin_postal_code'] ?? '')));

            $freeMin = preg_replace('/[^0-9]/', '', (string)($_POST['shipping_free_min_amount'] ?? '0'));
            if ($freeMin === '') $freeMin = '0';
            $settings->set('shipping_free_min_amount', (string)((int)$freeMin));

            $settings->set('shipping_default_weight', (string)max(1, (int)($_POST['shipping_default_weight'] ?? 1000)));
            $settings->set('shipping_default_height', (string)max(1, (int)($_POST['shipping_default_height'] ?? 10)));
            $settings->set('shipping_default_width',  (string)max(1, (int)($_POST['shipping_default_width'] ?? 10)));
            $settings->set('shipping_default_length', (string)max(1, (int)($_POST['shipping_default_length'] ?? 10)));

            flash('ok', 'Ajustes fiscales guardados.');
            redirect('/admin/settings?tab=fiscal');
            return;
        }


        if ($tab === 'mayoristas') {
            $db = db();
            $settings = new AppSettingsRepository($db);

            $enabled = isset($_POST['wh_enabled']) ? '1' : '0';

            $wh1 = trim((string)($_POST['wh1_discount_percent'] ?? ''));
            $wh2 = trim((string)($_POST['wh2_discount_percent'] ?? ''));

            $wh1n = str_replace(',', '.', $wh1);
            $wh2n = str_replace(',', '.', $wh2);

            if ($wh1n !== '' && !is_numeric($wh1n)) $wh1n = '';
            if ($wh2n !== '' && !is_numeric($wh2n)) $wh2n = '';

            if ($wh1n !== '') {
                $v = (float)$wh1n;
                if ($v < 0) $v = 0;
                if ($v > 100) $v = 100;
                $wh1n = rtrim(rtrim(sprintf('%.4F', $v), '0'), '.');
            }
            if ($wh2n !== '') {
                $v = (float)$wh2n;
                if ($v < 0) $v = 0;
                if ($v > 100) $v = 100;
                $wh2n = rtrim(rtrim(sprintf('%.4F', $v), '0'), '.');
            }

            $roundEnabled = isset($_POST['wh_round_enabled']) ? '1' : '0';
            $roundTo = (int)($_POST['wh_round_to'] ?? 0);
            if ($roundTo < 0) $roundTo = 0;

            $roundMode = strtolower(trim((string)($_POST['wh_round_mode'] ?? 'round')));
            if (!in_array($roundMode, ['round','ceil','floor'], true)) $roundMode = 'round';

            $ok = true;
            $ok = $ok && $settings->set('wh_enabled', $enabled);
            $ok = $ok && $settings->set('wh1_discount_percent', $wh1n);
            $ok = $ok && $settings->set('wh2_discount_percent', $wh2n);
            $ok = $ok && $settings->set('wh_round_enabled', $roundEnabled);
            $ok = $ok && $settings->set('wh_round_to', (string)$roundTo);
            $ok = $ok && $settings->set('wh_round_mode', $roundMode);

            if (!$ok) {
                flash('error', 'No se pudieron guardar los ajustes de mayoristas.');
                redirect('/admin/settings?tab=mayoristas');
                return;
            }

            flash('ok', 'Ajustes de mayoristas guardados.');
            redirect('/admin/settings?tab=mayoristas');
            return;
        }


        
if ($tab === 'envios') {
            $db = db();
            $settings = new AppSettingsRepository($db);

            $enabled = isset($_POST['correo_enabled']) ? '1' : '0';
            $mode = strtolower(trim((string)($_POST['correo_mode'] ?? 'test')));
            if (!in_array($mode, ['test','prod'], true)) $mode = 'test';

            $useEnv = isset($_POST['correo_use_env']) ? '1' : '0';

            $settings->set('correo_enabled', $enabled);
            $settings->set('correo_mode', $mode);
            $settings->set('correo_use_env', $useEnv);

            // QA/Dev: mock import (solo TEST). En PROD se ignora en runtime.
            $mockImport = isset($_POST['correo_mock_import']) ? '1' : '0';
            $settings->set('correo_mock_import', $mockImport);

            // Si usan env, no guardamos secretos en DB.
            if ($useEnv === '0') {
                $settings->set('correo_api_username', trim((string)($_POST['correo_api_username'] ?? '')));
                $settings->set('correo_api_password', trim((string)($_POST['correo_api_password'] ?? '')));
                $settings->set('correo_user_email', trim((string)($_POST['correo_user_email'] ?? '')));
                $settings->set('correo_user_password', trim((string)($_POST['correo_user_password'] ?? '')));
            }

            // Checkout / Cotización (FIX516)
            $settings->set('shipping_origin_postal_code', trim((string)($_POST['shipping_origin_postal_code'] ?? '')));

            $freeMin = preg_replace('/[^0-9]/', '', (string)($_POST['shipping_free_min_amount'] ?? '0'));
            if ($freeMin === '') $freeMin = '0';
            $settings->set('shipping_free_min_amount', (string)((int)$freeMin));

            $settings->set('shipping_local_pickup_enabled', isset($_POST['shipping_local_pickup_enabled']) ? '1' : '0');
            $settings->set('shipping_local_pickup_label', trim((string)($_POST['shipping_local_pickup_label'] ?? 'Retirar en el local')));
            $settings->set('shipping_local_pickup_address', trim((string)($_POST['shipping_local_pickup_address'] ?? '')));
            $settings->set('shipping_local_pickup_notes', trim((string)($_POST['shipping_local_pickup_notes'] ?? '')));
            $localPickupCost = preg_replace('/[^0-9]/', '', (string)($_POST['shipping_local_pickup_cost'] ?? '0'));
            if ($localPickupCost === '') $localPickupCost = '0';
            $settings->set('shipping_local_pickup_cost', (string)((int)$localPickupCost));
            $settings->set('shipping_local_pickup_maps_url', trim((string)($_POST['shipping_local_pickup_maps_url'] ?? '')));
            $settings->set('shipping_local_pickup_place_id', trim((string)($_POST['shipping_local_pickup_place_id'] ?? '')));
            $localPickupLat = str_replace(',', '.', trim((string)($_POST['shipping_local_pickup_map_lat'] ?? '')));
            $localPickupLng = str_replace(',', '.', trim((string)($_POST['shipping_local_pickup_map_lng'] ?? '')));
            $localPickupZoom = (int)($_POST['shipping_local_pickup_map_zoom'] ?? 17);
            if ($localPickupZoom < 1) $localPickupZoom = 17;
            if ($localPickupZoom > 21) $localPickupZoom = 21;
            $settings->set('shipping_local_pickup_map_lat', is_numeric($localPickupLat) ? (string)$localPickupLat : '');
            $settings->set('shipping_local_pickup_map_lng', is_numeric($localPickupLng) ? (string)$localPickupLng : '');
            $settings->set('shipping_local_pickup_map_zoom', (string)$localPickupZoom);

            $settings->set('shipping_default_weight', (string)max(1, (int)($_POST['shipping_default_weight'] ?? 1000)));
            $settings->set('shipping_default_height', (string)max(1, (int)($_POST['shipping_default_height'] ?? 10)));
            $settings->set('shipping_default_width',  (string)max(1, (int)($_POST['shipping_default_width'] ?? 10)));
            $settings->set('shipping_default_length', (string)max(1, (int)($_POST['shipping_default_length'] ?? 10)));

            $settings->set('shipping_packaging_extra_cm', (string)max(0, (int)($_POST['shipping_packaging_extra_cm'] ?? 0)));
            $rawPct = trim((string)($_POST['shipping_packaging_extra_weight_pct'] ?? '0'));
            $rawPct = str_replace(',', '.', $rawPct);
            $pct = (float)$rawPct;
            if ($pct < 0) $pct = 0;
            if ($pct > 100) $pct = 100;
            $settings->set('shipping_packaging_extra_weight_pct', rtrim(rtrim(number_format($pct, 2, '.', ''), '0'), '.'));

            // Remitente fijo (FIX517)
            $settings->set('shipping_sender_name', trim((string)($_POST['shipping_sender_name'] ?? '')));
            $settings->set('shipping_sender_email', trim((string)($_POST['shipping_sender_email'] ?? '')));
            $settings->set('shipping_sender_phone', trim((string)($_POST['shipping_sender_phone'] ?? '')));
            $settings->set('shipping_sender_cellphone', trim((string)($_POST['shipping_sender_cellphone'] ?? '')));
            $settings->set('shipping_sender_street', trim((string)($_POST['shipping_sender_street'] ?? '')));
            $settings->set('shipping_sender_number', trim((string)($_POST['shipping_sender_number'] ?? '')));
            $settings->set('shipping_sender_city', trim((string)($_POST['shipping_sender_city'] ?? '')));
            $settings->set('shipping_sender_state', trim((string)($_POST['shipping_sender_state'] ?? '')));
            $settings->set('shipping_sender_zip', trim((string)($_POST['shipping_sender_zip'] ?? '')));
            $settings->set('shipping_sender_floor', trim((string)($_POST['shipping_sender_floor'] ?? '')));
            $settings->set('shipping_sender_apartment', trim((string)($_POST['shipping_sender_apartment'] ?? '')));


            flash('ok', 'Ajustes de envíos guardados.');
            redirect('/admin/settings?tab=envios');
            return;
        }

        if ($tab !== 'email-marketing') {
            flash('ok', 'Ajustes guardados.');
            redirect('/admin/settings?tab=' . urlencode($tab));
        }

        $db = db();
        $settings = new AppSettingsRepository($db);
        $repo = new EmailAutomationRepository($db);

        // 1) Double opt-in
        $double = isset($_POST['double_optin']) ? '1' : '0';
        $settings->set('email_marketing_double_optin', $double);

        // 2) Límites runner (opcionales)
        $sched = (string)($_POST['sched_limit'] ?? '');
        $work  = (string)($_POST['work_limit'] ?? '');
        $broadcast = (string)($_POST['broadcast_limit'] ?? '');
        if ($sched !== '') {
            $n = max(10, min(2000, (int)$sched));
            $settings->set('email_marketing_scheduler_limit', (string)$n);
        }
        if ($broadcast !== '') {
            $n = max(10, min(2000, (int)$broadcast));
            $settings->set('email_marketing_broadcast_limit', (string)$n);
        }
        if ($work !== '') {
            $n = max(1, min(500, (int)$work));
            $settings->set('email_marketing_worker_limit', (string)$n);
        }

        // 2b) Tracking + frequency caps
        $openTracking = isset($_POST['open_tracking']) ? '1' : '0';
        $settings->set('email_marketing_open_tracking', $openTracking);

        $capDay = (string)($_POST['cap_per_day'] ?? '');
        $capWeek = (string)($_POST['cap_per_week'] ?? '');
        if ($capDay !== '') {
            $n = max(0, min(20, (int)$capDay));
            $settings->set('email_marketing_cap_per_day', (string)$n);
        }
        if ($capWeek !== '') {
            $n = max(0, min(100, (int)$capWeek));
            $settings->set('email_marketing_cap_per_week', (string)$n);
        }

        // 2c) Runner vía web (fallback)
        $webEnabled = isset($_POST['web_runner_enabled']) ? '1' : '0';
        $settings->set('email_marketing_web_runner_enabled', $webEnabled);

        $regenerate = isset($_POST['web_runner_regenerate']) && (string)$_POST['web_runner_regenerate'] === '1';
        $currentToken = (string)($settings->get('email_marketing_web_runner_token', '') ?? '');
        // Genera token si se habilita y no existe, o si pidieron regenerarlo.
        if ($webEnabled === '1' && ($regenerate || $currentToken === '')) {
            try {
                $newToken = bin2hex(random_bytes(16));
            } catch (\Throwable $e) {
                $newToken = bin2hex((string)microtime(true) . (string)rand());
            }
            $settings->set('email_marketing_web_runner_token', $newToken);
        }

        // 3) Delays por paso (value+unit)
        $values = $_POST['delay_value'] ?? [];
        $units  = $_POST['delay_unit'] ?? [];
        if (is_array($values) && is_array($units)) {
            foreach ($values as $sid => $val) {
                $stepId = (int)$sid;
                if ($stepId <= 0) continue;
                $v = (int)$val;
                $u = (string)($units[$sid] ?? 'min');

                $mult = 1;
                if ($u === 'h') $mult = 60;
                if ($u === 'd') $mult = 1440;

                $minutes = $v * $mult;
                $minutes = max(0, min(60 * 24 * 30, $minutes)); // 0..30 días

                try { $repo->setStepDelay($stepId, $minutes); } catch (\Throwable $e) { /* best-effort */ }
            }
        }

        flash('ok', 'Configuración de Email Marketing guardada.');
        redirect('/admin/settings?tab=email-marketing');
    }
}
