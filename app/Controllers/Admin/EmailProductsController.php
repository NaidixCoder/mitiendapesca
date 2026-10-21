<?php

namespace App\Controllers\Admin;

use App\Repositories\Marketing\EmailProductPickerRepository;
use PDO;

/**
 * Endpoint JSON: búsqueda de productos para el editor de campañas.
 * GET /admin/marketing/email/products/search?q=...
 */
class EmailProductsController extends BaseController
{
    private function db(): PDO
    {
        return \db();
    }

    public function search(): void
    {
        $q = (string)($_GET['q'] ?? '');
        $q = trim($q);

        // Permití búsqueda por ID aunque sea 1 dígito (ej. "1").
        // IMPORTANTE: si ingresan "1" como SKU (ej. "001"), igual sirve porque también buscamos por sku LIKE.
        $isNumeric = ($q !== '' && ctype_digit($q));

        // Para texto, evitamos consultas demasiado cortas (ruido).
        if (!$isNumeric && mb_strlen($q) < 2) {
            $this->json([
                'ok' => true,
                'items' => [],
                'hint' => 'Ingresá al menos 2 caracteres (o un ID numérico).',
            ]);
            return;
        }

        try {
            $repo = new EmailProductPickerRepository($this->db());
            $items = $repo->search($q, 20);

            // Normalizar URLs (imagen y producto)
            foreach ($items as &$it) {
                $it['image_url'] = $this->absUrl((string)($it['image_path'] ?? ''));
                $it['product_url'] = $this->productUrl((string)($it['slug'] ?? ''));
                unset($it['image_path']);
            }
            unset($it);

            $this->json([
                'ok' => true,
                'items' => $items,
            ]);
        } catch (\Throwable $e) {
            // Respuesta controlada para no romper el modal (y permitir debug en logs).
            // 1) Intento 1: log_error() (helpers.php)
            try {
                if (function_exists('log_error')) {
                    log_error('EmailProductsController@search failed', [
                        'q' => $q,
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);
                }
            } catch (\Throwable $ignore) {}

            // 2) Intento 2: log dedicado en storage/logs (para evitar "no se guarda nada")
            try {
                $dir = function_exists('storage_path') ? storage_path('logs') : (__DIR__ . '/../../../storage/logs');
                if (!is_dir($dir)) @mkdir($dir, 0775, true);
                $file = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . 'marketing_email_products.log';
                $line = '[' . date('Y-m-d H:i:s') . '] search q=' . $q . ' :: ' . $e->getMessage()
                    . ' @ ' . $e->getFile() . ':' . $e->getLine() . "\n";
                @file_put_contents($file, $line, FILE_APPEND);
            } catch (\Throwable $ignore) {}

            // 3) Fallback mínimo: PHP error log
            try {
                error_log('[EmailProductsController@search] q=' . $q . ' err=' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            } catch (\Throwable $ignore) {}

            http_response_code(200);
            $this->json([
                'ok' => false,
                'items' => [],
                'error' => 'No se pudo buscar productos. Revisá el log del servidor (marketing_email_products.log).',
            ]);
        }
    }

    private function productUrl(string $slug): string
    {
        $slug = trim($slug);
        if ($slug === '') return '';

        $path = '/producto?slug=' . urlencode($slug);
        return function_exists('url') ? url($path) : $path;
    }

    private function absUrl(string $path): string
    {
        $path = trim($path);
        if ($path === '') return '';
        if (preg_match('#^https?://#i', $path)) return $path;

        if ($path[0] !== '/') $path = '/' . $path;

        return function_exists('url') ? url($path) : $path;
    }
}
