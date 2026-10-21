<?php
namespace App\Services\Billing;

/**
 * Adjuntos de Compras (IVA Compras): subida + listado + descarga segura.
 * Archivos en storage/purchase_invoices/invoice_<id>/
 * No depende de DB (evita migraciones).
 */
final class PurchaseInvoiceAttachmentService
{
    /** @return array<int, array{file:string,name:string,size:int,created_at:string}> */
    public function listForInvoice(int $invoiceId): array
    {
        $dir = $this->invoiceDir($invoiceId);
        if (!is_dir($dir)) return [];

        $out = [];
        $files = glob($dir . '/*');
        if (!$files) return [];

        foreach ($files as $path) {
            if (!is_file($path)) continue;
            $base = basename($path);
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

        usort($out, fn($a,$b) => strcmp((string)$b['created_at'], (string)$a['created_at']));
        return $out;
    }

    /**
     * @param array<string,mixed> $file (de $_FILES)
     * @return array{0:bool,1:string,2:?string} ok, mensaje, filename
     */
    public function saveForInvoice(int $invoiceId, array $file): array
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
        if ($size > 8 * 1024 * 1024) return [false, 'Máx 8MB', null];

        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_file($tmp)) return [false, 'Archivo temporal inválido', null];

        $mime = null;
        if (class_exists(\finfo::class)) {
            $fi = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $fi->file($tmp);
        }
        $allowed = [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
        ];
        if (!isset($allowed[$mime])) return [false, 'Formato no permitido (PDF/JPG/PNG)', null];

        $ext = $allowed[$mime];
        $dir = $this->invoiceDir($invoiceId);
        @mkdir($dir, 0775, true);
        if (!is_writable($dir)) return [false, 'Carpeta no escribible', null];

        $orig = (string)($file['name'] ?? ('comprobante.' . $ext));
        $orig = $this->sanitizeName($orig);
        if ($orig === '') $orig = 'comprobante.' . $ext;

        $prefix = bin2hex(random_bytes(6));
        $name = $prefix . '_' . $orig;
        if (!str_ends_with(strtolower($name), '.' . $ext)) $name .= '.' . $ext;

        $dest = $dir . '/' . $name;
        if (!@move_uploaded_file($tmp, $dest)) {
            if (!@copy($tmp, $dest)) return [false, 'No se pudo mover el archivo', null];
        }

        return [true, 'Adjunto guardado.', $name];
    }

    public function resolvePath(int $invoiceId, string $file): ?string
    {
        $file = basename($file);
        if ($file === '') return null;
        $path = $this->invoiceDir($invoiceId) . '/' . $file;
        return is_file($path) ? $path : null;
    }

    public function delete(int $invoiceId, string $file): bool
    {
        $path = $this->resolvePath($invoiceId, $file);
        if (!$path) return false;
        return @unlink($path);
    }

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

    public function isPreviewableMime(string $mime): bool
    {
        return in_array($mime, ['image/jpeg','image/png','application/pdf'], true);
    }

    private function invoiceDir(int $invoiceId): string
    {
        $base = function_exists('base_path') ? base_path('storage/purchase_invoices') : __DIR__ . '/../../../storage/purchase_invoices';
        return rtrim((string)$base, '/\\') . '/invoice_' . $invoiceId;
    }

    private function sanitizeName(string $name): string
    {
        $name = trim($name);
        $name = basename($name);
        $name = preg_replace('/[^a-zA-Z0-9._ -]/', '', $name) ?? '';
        $name = preg_replace('/\s+/', ' ', $name) ?? '';
        return trim($name);
    }
}
