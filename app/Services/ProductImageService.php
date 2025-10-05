<?php
namespace App\Services;

use PDO;

class ProductImageService
{
    public function __construct(private PDO $db) {}

    public function upload(int $productId, array $file): array {
        if ($file['size'] > 3*1024*1024) return [false, 'Máx 3MB'];

        // Detect MIME
        $mime = null;
        if (class_exists(\finfo::class)) {
            $fi = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $fi->file($file['tmp_name']);
        }
        if (!$mime && function_exists('getimagesize')) {
            $gi = @getimagesize($file['tmp_name']); if ($gi && isset($gi['mime'])) $mime = $gi['mime'];
        }
        $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        if (!isset($allowed[$mime])) return [false,'Formato no permitido (JPG/PNG/WEBP)'];

        $ext = $allowed[$mime];
        $dir = base_path('public_html/uploads/products/'.$productId);
        @mkdir($dir,0775,true);
        if (!is_writable($dir)) return [false,'Carpeta no escribible'];

        $name = bin2hex(random_bytes(6)).'.'.$ext;
        $dest = $dir.'/'.$name;

        if (!@move_uploaded_file($file['tmp_name'],$dest)) {
            if (!@copy($file['tmp_name'],$dest)) return [false,'No se pudo mover el archivo'];
        }

        $hasCover = (int)$this->db->query("SELECT COUNT(*) FROM product_images WHERE product_id={$productId} AND is_cover=1")->fetchColumn();
        $isCover  = $hasCover ? 0 : 1;

        $relative = 'uploads/products/'.$productId.'/'.$name;
        $ok = $this->db->prepare("INSERT INTO product_images (product_id, path, alt, is_cover, sort) VALUES (?,?,?,?,0)")
                       ->execute([$productId,$relative,null,$isCover]);
        if (!$ok) { @unlink($dest); return [false,'No se pudo registrar la imagen']; }

        return [true, $relative, $isCover];
    }

    public function delete(int $productId, int $imageId): array {
        $st = $this->db->prepare("SELECT path,is_cover FROM product_images WHERE id=? AND product_id=?");
        $st->execute([$imageId,$productId]);
        $img = $st->fetch(\PDO::FETCH_ASSOC);
        if (!$img) return [false,'Imagen no encontrada'];

        $ok = $this->db->prepare("DELETE FROM product_images WHERE id=? AND product_id=?")->execute([$imageId,$productId]);
        if (!$ok) return [false,'No se pudo eliminar'];

        $relative = ltrim($img['path'],'/');
        $disk = base_path('public_html/'.$relative);
        if (is_file($disk)) @unlink($disk);

        $expectedPrefix = 'uploads/products/'.$productId.'/';
        if (strpos($relative,$expectedPrefix)===0) {
            $folder = dirname(base_path('public_html/'.$relative));
            @rmdir($folder); // si está vacía
        }

        if ((int)$img['is_cover']===1) {
            $row = $this->db->prepare("SELECT id FROM product_images WHERE product_id=? ORDER BY id ASC LIMIT 1");
            $row->execute([$productId]);
            if ($n = $row->fetch(\PDO::FETCH_ASSOC)) {
                $this->db->prepare("UPDATE product_images SET is_cover=1 WHERE id=?")->execute([$n['id']]);
                $this->db->prepare("UPDATE product_images SET is_cover=0 WHERE product_id=? AND id<>?")->execute([$productId,$n['id']]);
            }
        }
        return [true,'Imagen eliminada'];
    }

    public function makeCover(int $productId, int $imageId): array {
        $own = $this->db->prepare("SELECT COUNT(*) FROM product_images WHERE id=? AND product_id=?");
        $own->execute([$imageId,$productId]);
        if ((int)$own->fetchColumn()===0) return [false,'Imagen inválida'];

        $this->db->prepare("UPDATE product_images SET is_cover=0 WHERE product_id=?")->execute([$productId]);
        $ok = $this->db->prepare("UPDATE product_images SET is_cover=1 WHERE id=?")->execute([$imageId]);
        return $ok ? [true,'Portada actualizada'] : [false,'No se pudo actualizar la portada'];
    }
}
