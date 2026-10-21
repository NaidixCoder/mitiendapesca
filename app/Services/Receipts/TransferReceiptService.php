<?php
namespace App\Services\Receipts;

/**
 * Maneja comprobantes de transferencia (subida + listado + descarga segura).
 *
 * Diseño Sprint 1: SIN DB (evitamos migraciones). Los archivos viven en storage/receipts/order_<id>/
 * y se listan por nombre. Descarga siempre pasa por controller con auth.
 */
class TransferReceiptService
{
    /** @return string MIME detectado o application/octet-stream */
    public function detectMime(string $path): string
    {
        $mime = 'application/octet-stream';
        if (!is_file($path)) return $mime;
        if (class_exists(\finfo::class)) {
            $fi = new \finfo(FILEINFO_MIME_TYPE);
            $m = $fi->file($path);
            if (is_string($m) && $m !== '') $mime = $m;
        }
        return $mime;
    }

    /** Solo previsualizamos JPG/PNG/PDF (inline). */
    public function isPreviewableMime(string $mime): bool
    {
        return in_array($mime, ['image/jpeg','image/png','application/pdf'], true);
    }

    /** @return array<int, array{file:string, name:string, size:int, created_at:string}> */
    public function listForOrder(int $orderId): array
    {
        $dir = $this->orderDir($orderId);
        if (!is_dir($dir)) return [];

        $out = [];
        $files = glob($dir . '/*');
        if (!$files) return [];

        foreach ($files as $path) {
            if (!is_file($path)) continue;
            $base = basename($path);
            // nombre humano (sin el prefijo random)
            $name = preg_replace('/^[a-f0-9]{12}_/i', '', $base) ?: $base;
            $size = (int)@filesize($path);
            $ts = (int)@filemtime($path);
            $out[] = [
                'file' => $base,
                'name' => $name,
                'size' => $size,
                'created_at' => $ts > 0 ? date('Y-m-d H:i:s', $ts) : '',
            ];
        }

        // Más reciente primero
        usort($out, fn($a,$b) => strcmp((string)$b['created_at'], (string)$a['created_at']));
        return $out;
    }

    /**
     * @param array<string,mixed> $file (de $_FILES)
     * @return array{0:bool,1:string,2:?string} ok, mensaje, filename
     */
    public function saveForOrder(int $orderId, array $file): array
    {
        if (!$file || !is_array($file)) return [false, 'Archivo no recibido', null];
        if (!isset($file['error']) || (int)$file['error'] !== UPLOAD_ERR_OK) {
            $err = (int)($file['error'] ?? -1);
            $map = [
                UPLOAD_ERR_INI_SIZE   => 'El archivo supera el tamaño permitido por el servidor',
                UPLOAD_ERR_FORM_SIZE  => 'El archivo supera el tamaño permitido por el formulario',
                UPLOAD_ERR_PARTIAL    => 'La subida quedó incompleta',
                UPLOAD_ERR_NO_FILE    => 'No se seleccionó ningún archivo',
                UPLOAD_ERR_NO_TMP_DIR => 'Falta carpeta temporal en el servidor',
                UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en disco',
                UPLOAD_ERR_EXTENSION  => 'La subida fue bloqueada por una extensión del servidor',
            ];
            return [false, $map[$err] ?? 'Error subiendo archivo', null];
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0) return [false, 'Archivo vacío', null];
        if ($size > 6 * 1024 * 1024) return [false, 'Máx 6MB', null];

        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_file($tmp)) return [false, 'Archivo temporal inválido', null];

        // Detect MIME
        $mime = null;
        if (class_exists(\finfo::class)) {
            $fi = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $fi->file($tmp);
        }
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'application/pdf' => 'pdf',
        ];
        if (!isset($allowed[$mime])) return [false, 'Formato no permitido (JPG/PNG/PDF)', null];

        $ext = $allowed[$mime];
        $dir = $this->orderDir($orderId);
        @mkdir($dir, 0775, true);
        if (!is_writable($dir)) return [false, 'Carpeta no escribible', null];

        $orig = (string)($file['name'] ?? 'comprobante.' . $ext);
        $orig = $this->sanitizeName($orig);
        if ($orig === '') $orig = 'comprobante.' . $ext;

        $prefix = bin2hex(random_bytes(6));
        $name = $prefix . '_' . $orig;
        // asegurar extensión
        if (!str_ends_with(strtolower($name), '.' . $ext)) {
            $name .= '.' . $ext;
        }

        $dest = $dir . '/' . $name;
        if (!@move_uploaded_file($tmp, $dest)) {
            if (!@copy($tmp, $dest)) return [false, 'No se pudo mover el archivo', null];
        }

        return [true, 'Comprobante adjuntado.', $name];
    }

    public function resolvePath(int $orderId, string $file): ?string
    {
        $file = basename($file); // evita traversal
        if ($file === '') return null;
        $path = $this->orderDir($orderId) . '/' . $file;
        return is_file($path) ? $path : null;
    }

    private function orderDir(int $orderId): string
    {
        $base = function_exists('base_path') ? base_path('storage/receipts') : __DIR__ . '/../../../storage/receipts';
        return rtrim((string)$base, '/\\') . '/order_' . $orderId;
    }

    private function sanitizeName(string $name): string
    {
        $name = trim($name);
        // quitar rutas
        $name = basename($name);
        // reemplazar caracteres raros
        $name = preg_replace('/[^a-zA-Z0-9._ -]/', '', $name) ?? '';
        $name = preg_replace('/\s+/', ' ', $name) ?? '';
        return trim($name);
    }
}
