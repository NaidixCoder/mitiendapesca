<?php
namespace App\Services\Media;

use PDO;

class ProductImageService
{
    private ?bool $hasVariantScope = null;

    public function __construct(private PDO $db) {}

    private function hasVariantIdColumn(): bool
    {
        if ($this->hasVariantScope !== null) return $this->hasVariantScope;
        try {
            $st = $this->db->query("SHOW COLUMNS FROM product_images LIKE 'variant_id'");
            $row = $st ? $st->fetch(PDO::FETCH_ASSOC) : null;
            $this->hasVariantScope = $row ? true : false;
        } catch (\Throwable $e) {
            $this->hasVariantScope = false;
        }
        return $this->hasVariantScope;
    }

    private function normVariantId(?int $variantId): ?int
    {
        $v = (int)($variantId ?? 0);
        return $v > 0 ? $v : null;
    }

    /**
     * Crea una miniatura liviana para usar en la galería del admin.
     * - Output preferido: WEBP si está disponible, si no JPG.
     * - Best-effort: si no hay GD o falla, retorna null.
     *
     * @return array{thumb_relative:string, thumb_disk:string}|null
     */
    private function createThumbnail(string $srcDisk, string $srcMime, string $relativeOriginal, int $maxSide = 600): ?array
    {
        if (!extension_loaded('gd')) return null;
        if (!function_exists('getimagesize')) return null;

        $gi = @getimagesize($srcDisk);
        if (!$gi || empty($gi[0]) || empty($gi[1])) return null;

        [$w, $h] = [(int)$gi[0], (int)$gi[1]];
        if ($w <= 0 || $h <= 0) return null;

        $scale = min(1.0, $maxSide / max($w, $h));
        $tw = max(1, (int)round($w * $scale));
        $th = max(1, (int)round($h * $scale));

        $src = null;
        try {
            switch ($srcMime) {
                case 'image/jpeg': $src = @imagecreatefromjpeg($srcDisk); break;
                case 'image/png':  $src = @imagecreatefrompng($srcDisk);  break;
                case 'image/webp': $src = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($srcDisk) : null; break;
                default: return null;
            }
        } catch (\Throwable $e) {
            return null;
        }
        if (!$src) return null;

        $dst = @imagecreatetruecolor($tw, $th);
        if (!$dst) { @imagedestroy($src); return null; }

        // Preservar alpha para PNG/WEBP
        if (in_array($srcMime, ['image/png','image/webp'], true)) {
            @imagealphablending($dst, false);
            @imagesavealpha($dst, true);
            $transparent = @imagecolorallocatealpha($dst, 0, 0, 0, 127);
            @imagefilledrectangle($dst, 0, 0, $tw, $th, $transparent);
        }

        @imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, $w, $h);

        $outExt = function_exists('imagewebp') ? 'webp' : 'jpg';
        $thumbRelative = preg_replace('/\.(jpe?g|png|webp)$/i', '_thumb.'.$outExt, $relativeOriginal);
        $thumbRelative = ltrim((string)$thumbRelative, '/');
        $thumbDisk = base_path('public_html/'.$thumbRelative);

        $thumbDir = dirname($thumbDisk);
        @mkdir($thumbDir, 0775, true);

        $ok = false;
        try {
            if ($outExt === 'webp') $ok = @imagewebp($dst, $thumbDisk, 78);
            else $ok = @imagejpeg($dst, $thumbDisk, 82);
        } catch (\Throwable $e) {
            $ok = false;
        }

        @imagedestroy($dst);
        @imagedestroy($src);

        if (!$ok || !is_file($thumbDisk)) return null;
        return ['thumb_relative' => $thumbRelative, 'thumb_disk' => $thumbDisk];
    }

    /**
     * Devuelve posibles paths de thumb para un path original (por compatibilidad).
     * @return string[] relative paths
     */
    private function thumbCandidates(string $relativeOriginal): array
    {
        $rel = ltrim($relativeOriginal, '/');
        $base = preg_replace('/\.(jpe?g|png|webp)$/i', '', $rel);
        if (!$base) return [];
        return [$base.'_thumb.webp', $base.'_thumb.jpg', $base.'_thumb.jpeg'];
    }

    /**
     * Sube imagen y crea el registro en DB.
     *
     * @return array{0:bool,1:string,2:?array}
     */
    public function upload(int $productId, ?array $file, ?int $variantId = null): array
    {
        $variantId = $this->normVariantId($variantId);
        if (!$this->hasVariantIdColumn()) $variantId = null;

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
        if ($size > 3 * 1024 * 1024) return [false, 'Máx 3MB', null];

        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_file($tmp)) return [false, 'Archivo temporal inválido', null];

        // Detect MIME
        $mime = null;
        if (class_exists(\finfo::class)) {
            $fi = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $fi->file($tmp);
        }
        if (!$mime && function_exists('getimagesize')) {
            $gi = @getimagesize($tmp);
            if ($gi && isset($gi['mime'])) $mime = $gi['mime'];
        }
        $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        if (!isset($allowed[$mime])) return [false,'Formato no permitido (JPG/PNG/WEBP)', null];

        $ext = $allowed[$mime];
        $dir = base_path('public_html/uploads/products/'.$productId);
        @mkdir($dir,0775,true);
        if (!is_writable($dir)) return [false,'Carpeta no escribible', null];

        $name = bin2hex(random_bytes(6)).'.'.$ext;
        $dest = $dir.'/'.$name;

        if (!@move_uploaded_file($tmp, $dest)) {
            if (!@copy($tmp, $dest)) return [false,'No se pudo mover el archivo', null];
        }

        $relative = 'uploads/products/'.$productId.'/'.$name;

        // Thumb (best-effort)
        $thumbRelative = null;
        $thumbDisk = null;
        $thumb = $this->createThumbnail($dest, (string)$mime, $relative, 600);
        if (is_array($thumb ?? null)) {
            $thumbRelative = $thumb['thumb_relative'] ?? null;
            $thumbDisk     = $thumb['thumb_disk'] ?? null;
        }

        // Scope WHERE
        $scopeWhere = 'product_id = ?';
        $scopeParams = [$productId];
        if ($this->hasVariantIdColumn()) {
            if ($variantId === null) $scopeWhere .= ' AND variant_id IS NULL';
            else { $scopeWhere .= ' AND variant_id = ?'; $scopeParams[] = $variantId; }
        }

        // Portada automática: si no hay, esta queda como portada (por scope)
        $stCover = $this->db->prepare('SELECT COUNT(*) FROM product_images WHERE '.$scopeWhere.' AND is_cover = 1');
        $stCover->execute($scopeParams);
        $hasCover = (int)($stCover->fetchColumn() ?: 0);
        $isCover  = $hasCover ? 0 : 1;

        // Orden: empujar al final (max sort + 10) por scope
        $stMax = $this->db->prepare('SELECT COALESCE(MAX(sort),0) FROM product_images WHERE '.$scopeWhere);
        $stMax->execute($scopeParams);
        $maxSort = (int)($stMax->fetchColumn() ?: 0);
        $nextSort = $maxSort + 10;

        // Insert
        if ($this->hasVariantIdColumn()) {
            $ok = $this->db->prepare('INSERT INTO product_images (product_id, variant_id, path, alt, is_cover, sort) VALUES (?,?,?,?,?,?)')
                           ->execute([$productId, $variantId, $relative, null, $isCover, $nextSort]);
        } else {
            $ok = $this->db->prepare('INSERT INTO product_images (product_id, path, alt, is_cover, sort) VALUES (?,?,?,?,?)')
                           ->execute([$productId, $relative, null, $isCover, $nextSort]);
        }

        if (!$ok) {
            @unlink($dest);
            if ($thumbDisk && is_file($thumbDisk)) @unlink($thumbDisk);
            return [false,'No se pudo registrar la imagen', null];
        }

        $id = (int)$this->db->lastInsertId();

        $image = [
            'id'       => $id,
            'path'     => $relative,
            'alt'      => null,
            'is_cover' => $isCover,
            'sort'     => $nextSort,
            'thumb_path' => $thumbRelative,
            'variant_id' => $variantId,
        ];

        return [true, 'Imagen subida', $image];
    }

    public function delete(int $productId, int $imageId, ?int $variantId = null): array
    {
        $variantId = $this->normVariantId($variantId);
        if (!$this->hasVariantIdColumn()) $variantId = null;

        $params = [$imageId, $productId];
        $where = 'id=? AND product_id=?';
        if ($this->hasVariantIdColumn()) {
            if ($variantId === null) $where .= ' AND variant_id IS NULL';
            else { $where .= ' AND variant_id = ?'; $params[] = $variantId; }
        }

        $st = $this->db->prepare("SELECT path,is_cover".($this->hasVariantIdColumn() ? ",variant_id" : "")." FROM product_images WHERE $where");
        $st->execute($params);
        $img = $st->fetch(PDO::FETCH_ASSOC);
        if (!$img) return [false,'Imagen no encontrada', null];

        $relative = ltrim((string)$img['path'],'/');
        $disk = base_path('public_html/'.$relative);

        // Scope helper
        $scopeWhere = 'product_id = ?';
        $scopeParams = [$productId];
        if ($this->hasVariantIdColumn()) {
            if ($variantId === null) $scopeWhere .= ' AND variant_id IS NULL';
            else { $scopeWhere .= ' AND variant_id = ?'; $scopeParams[] = $variantId; }
        }

        $newCoverId = null;

        try {
            $this->db->beginTransaction();

            $delParams = [$imageId, $productId];
            $delWhere = 'id=? AND product_id=?';
            if ($this->hasVariantIdColumn()) {
                if ($variantId === null) $delWhere .= ' AND variant_id IS NULL';
                else { $delWhere .= ' AND variant_id = ?'; $delParams[] = $variantId; }
            }

            $ok = $this->db->prepare("DELETE FROM product_images WHERE $delWhere")->execute($delParams);
            if (!$ok) { $this->db->rollBack(); return [false,'No se pudo eliminar', null]; }

            if ((int)($img['is_cover'] ?? 0) === 1) {
                // Candidata dentro del scope
                $row = $this->db->prepare("SELECT id FROM product_images WHERE $scopeWhere ORDER BY sort ASC, id ASC LIMIT 1");
                $row->execute($scopeParams);
                $cand = (int)($row->fetchColumn() ?: 0);

                // Reset cover en scope y marcar candidata si existe
                $this->db->prepare("UPDATE product_images SET is_cover=0 WHERE $scopeWhere")->execute($scopeParams);
                if ($cand > 0) {
                    $updParams = array_merge($scopeParams, [$cand]);
                    $this->db->prepare("UPDATE product_images SET is_cover=1 WHERE $scopeWhere AND id=?")->execute($updParams);
                    $newCoverId = $cand;
                }
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            if (function_exists('log_error')) log_error('ProductImageService@delete: '.$e->getMessage());
            return [false,'Error eliminando imagen', null];
        }

        // Disco: best-effort
        try {
            if (is_file($disk) && !@unlink($disk)) {
                if (function_exists('log_error')) log_error('ProductImageService@delete: no se pudo borrar archivo '.$disk);
            }
            foreach ($this->thumbCandidates($relative) as $tRel) {
                $tDisk = base_path('public_html/'.$tRel);
                if (is_file($tDisk) && !@unlink($tDisk)) {
                    if (function_exists('log_error')) log_error('ProductImageService@delete: no se pudo borrar thumb '.$tDisk);
                }
            }
        } catch (\Throwable $e) {
            if (function_exists('log_error')) log_error('ProductImageService@delete unlink: '.$e->getMessage());
        }

        // Intentar borrar carpeta vacía (best-effort)
        $expectedPrefix = 'uploads/products/'.$productId.'/';
        if (strpos($relative,$expectedPrefix)===0) {
            $folder = dirname(base_path('public_html/'.$relative));
            try { @rmdir($folder); } catch (\Throwable $e) { /* best-effort */ }
        }

        return [true,'Imagen eliminada', ['deleted_id'=>$imageId,'new_cover_id'=>$newCoverId]];
    }

    
    /**
     * Asegura que exista una portada (is_cover=1) dentro de un alcance (General o Variante).
     * Si ya hay una portada válida, no hace nada.
     * Devuelve el id de la portada resultante (o null si no hay imágenes en ese alcance).
     */
    public function ensureCover(int $productId, ?int $variantId = null): ?int
    {
        $variantId = $this->normVariantId($variantId);
        if (!$this->hasVariantIdColumn()) $variantId = null;

        // Scope helper
        $scopeWhere = 'product_id = ?';
        $scopeParams = [$productId];
        if ($this->hasVariantIdColumn()) {
            if ($variantId === null) $scopeWhere .= ' AND variant_id IS NULL';
            else { $scopeWhere .= ' AND variant_id = ?'; $scopeParams[] = $variantId; }
        }

        $started = false;

        try {
            // Ya hay portada
            $stHas = $this->db->prepare("SELECT id FROM product_images WHERE $scopeWhere AND is_cover = 1 ORDER BY sort ASC, id ASC LIMIT 1");
            $stHas->execute($scopeParams);
            $hasId = (int)($stHas->fetchColumn() ?: 0);
            if ($hasId > 0) return $hasId;

            // Candidata
            $stCand = $this->db->prepare("SELECT id FROM product_images WHERE $scopeWhere ORDER BY sort ASC, id ASC LIMIT 1");
            $stCand->execute($scopeParams);
            $candId = (int)($stCand->fetchColumn() ?: 0);
            if ($candId <= 0) return null;

            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
                $started = true;
            }

            // Reset covers en scope y marcar candidata
            $this->db->prepare("UPDATE product_images SET is_cover = 0 WHERE $scopeWhere")->execute($scopeParams);
            $updParams = array_merge($scopeParams, [$candId]);
            $this->db->prepare("UPDATE product_images SET is_cover = 1 WHERE $scopeWhere AND id = ?")->execute($updParams);

            if ($started) $this->db->commit();
            return $candId;
        } catch (\Throwable $e) {
            if ($started && $this->db->inTransaction()) $this->db->rollBack();
            if (function_exists('log_error')) log_error('ProductImageService@ensureCover: '.$e->getMessage());
            return null;
        }
    }

public function makeCover(int $productId, int $imageId, ?int $variantId = null): array
    {
        $variantId = $this->normVariantId($variantId);
        if (!$this->hasVariantIdColumn()) $variantId = null;

        $params = [$imageId, $productId];
        $where = 'id=? AND product_id=?';
        if ($this->hasVariantIdColumn()) {
            if ($variantId === null) $where .= ' AND variant_id IS NULL';
            else { $where .= ' AND variant_id = ?'; $params[] = $variantId; }
        }

        $own = $this->db->prepare("SELECT COUNT(*) FROM product_images WHERE $where");
        $own->execute($params);
        if ((int)$own->fetchColumn() === 0) return [false,'Imagen inválida', null];

        $scopeWhere = 'product_id = ?';
        $scopeParams = [$productId];
        if ($this->hasVariantIdColumn()) {
            if ($variantId === null) $scopeWhere .= ' AND variant_id IS NULL';
            else { $scopeWhere .= ' AND variant_id = ?'; $scopeParams[] = $variantId; }
        }

        try {
            $this->db->beginTransaction();

            $this->db->prepare("UPDATE product_images SET is_cover=0 WHERE $scopeWhere")->execute($scopeParams);
            $updParams = array_merge($scopeParams, [$imageId]);
            $ok = $this->db->prepare("UPDATE product_images SET is_cover=1 WHERE $scopeWhere AND id=?")->execute($updParams);

            if (!$ok) { $this->db->rollBack(); return [false,'No se pudo actualizar la portada', null]; }

            $this->db->commit();
            return [true,'Portada actualizada', ['id'=>$imageId,'is_cover'=>1]];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            if (function_exists('log_error')) log_error('ProductImageService@makeCover: '.$e->getMessage());
            return [false,'Error actualizando portada', null];
        }
    }
}

