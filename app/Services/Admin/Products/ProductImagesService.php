<?php
namespace App\Services\Admin\Products;

use App\Services\Audit\Audit;
use App\Services\Media\ProductImageService;
use PDO;
use Throwable;

final class ProductImagesService
{
    public function __construct(private PDO $db) {}

    public function upload(int $productId, int $variantId, ?array $file): array
    {
        if ($productId <= 0) {
            return $this->error('Producto inválido');
        }

        if ($variantId > 0 && !$this->variantBelongsToProduct($productId, $variantId)) {
            return $this->error('Variante inválida', 422);
        }

        try {
            $scopeVariantId = $variantId > 0 ? $variantId : null;
            $svc = new ProductImageService($this->db);
            [$ok, $msg, $image] = $svc->upload($productId, $file, $scopeVariantId);

            if (is_array($image ?? null)) {
                $image['full_url'] = public_url($image['path'] ?? '');
                if (!empty($image['thumb_path'])) {
                    $image['thumb_url'] = public_url($image['thumb_path']);
                    $image['url'] = $image['thumb_url'];
                } else {
                    $image['url'] = $image['full_url'];
                }
            }

            if ($ok) {
                Audit::log('product.image_upload', 'product', $productId, ['image' => $image]);
                return $this->ok($msg ?: 'Imagen subida', ['image' => $image ?? null]);
            }

            return $this->error($msg ?: 'No se pudo subir la imagen');
        } catch (Throwable $e) {
            if (function_exists('log_error')) {
                log_error('ProductImagesService@upload: ' . $e->getMessage());
            }
            return $this->error('Error subiendo imagen');
        }
    }

    public function delete(int $productId, int $imageId, int $variantId): array
    {
        if ($productId <= 0 || $imageId <= 0) {
            return $this->error('Parámetros inválidos');
        }
        if ($variantId > 0 && !$this->variantBelongsToProduct($productId, $variantId)) {
            return $this->error('Variante inválida', 422);
        }

        try {
            $scopeVariantId = $variantId > 0 ? $variantId : null;
            $svc = new ProductImageService($this->db);
            [$ok, $msg, $meta] = $svc->delete($productId, $imageId, $scopeVariantId);
            $newCoverId = is_array($meta ?? null) ? ($meta['new_cover_id'] ?? null) : null;

            if ($ok) {
                Audit::log('product.image_delete', 'product', $productId, [
                    'image_id' => $imageId,
                    'new_cover_id' => $newCoverId,
                ]);
            }

            return [
                'ok' => (bool)$ok,
                'message' => $ok ? ($msg ?: 'Imagen eliminada') : ($msg ?: 'No se pudo eliminar'),
                'data' => [
                    'product_id' => $productId,
                    'deleted_id' => $imageId,
                    'new_cover_id' => $newCoverId,
                ],
            ];
        } catch (Throwable $e) {
            if (function_exists('log_error')) {
                log_error('ProductImagesService@delete: ' . $e->getMessage());
            }
            return $this->error('Error eliminando imagen');
        }
    }

    public function cover(int $productId, int $imageId, int $variantId): array
    {
        if ($productId <= 0 || $imageId <= 0) {
            return $this->error('Parámetros inválidos');
        }
        if ($variantId > 0 && !$this->variantBelongsToProduct($productId, $variantId)) {
            return $this->error('Variante inválida', 422);
        }

        try {
            $scopeVariantId = $variantId > 0 ? $variantId : null;
            $svc = new ProductImageService($this->db);
            [$ok, $msg] = $svc->makeCover($productId, $imageId, $scopeVariantId);

            if ($ok) {
                Audit::log('product.image_cover', 'product', $productId, [
                    'image_id' => $imageId,
                    'variant_id' => $scopeVariantId,
                ]);
            }

            return [
                'ok' => (bool)$ok,
                'message' => $ok ? ($msg ?: 'Imagen marcada como portada') : ($msg ?: 'No se pudo marcar la portada'),
                'data' => ['product_id' => $productId, 'image_id' => $imageId],
            ];
        } catch (Throwable $e) {
            if (function_exists('log_error')) {
                log_error('ProductImagesService@cover: ' . $e->getMessage());
            }
            return $this->error('Error marcando portada');
        }
    }

    public function assign(int $productId, int $imageId, int $variantId): array
    {
        if ($productId <= 0 || $imageId <= 0) {
            return $this->error('Parámetros inválidos');
        }
        if ($variantId > 0 && !$this->variantBelongsToProduct($productId, $variantId)) {
            return $this->error('Variante inválida', 422);
        }

        $targetVariantId = $variantId > 0 ? $variantId : null;

        try {
            $st = $this->db->prepare("SELECT id, variant_id, is_cover FROM product_images WHERE id=:iid AND product_id=:pid LIMIT 1");
            $st->execute([':iid' => $imageId, ':pid' => $productId]);
            $row = $st->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$row) {
                return $this->error('Imagen no encontrada');
            }

            $currentVariantId = ($row['variant_id'] === null || $row['variant_id'] === '') ? null : (int)$row['variant_id'];
            $wasCover = ((int)($row['is_cover'] ?? 0) === 1);

            if ($currentVariantId === $targetVariantId) {
                return $this->ok('Sin cambios');
            }

            $this->db->beginTransaction();

            if ($wasCover) {
                if ($currentVariantId === null) {
                    $this->db->prepare("UPDATE product_images SET is_cover=0 WHERE product_id=:pid AND variant_id IS NULL")->execute([':pid' => $productId]);
                } else {
                    $this->db->prepare("UPDATE product_images SET is_cover=0 WHERE product_id=:pid AND variant_id=:vid")->execute([':pid' => $productId, ':vid' => $currentVariantId]);
                }
            }

            $newSort = 1;
            if ($targetVariantId === null) {
                $mx = $this->db->prepare("SELECT COALESCE(MAX(sort),0) FROM product_images WHERE product_id=:pid AND variant_id IS NULL");
                $mx->execute([':pid' => $productId]);
                $newSort = ((int)$mx->fetchColumn()) + 1;
            } else {
                $mx = $this->db->prepare("SELECT COALESCE(MAX(sort),0) FROM product_images WHERE product_id=:pid AND variant_id=:vid");
                $mx->execute([':pid' => $productId, ':vid' => $targetVariantId]);
                $newSort = ((int)$mx->fetchColumn()) + 1;
            }

            $upd = $this->db->prepare("UPDATE product_images SET variant_id=:vid, sort=:sort, is_cover=0 WHERE id=:iid AND product_id=:pid");
            $upd->execute([
                ':vid' => $targetVariantId,
                ':sort' => $newSort,
                ':iid' => $imageId,
                ':pid' => $productId,
            ]);

            if ($wasCover) {
                if ($currentVariantId === null) {
                    $cand = $this->db->prepare("SELECT id FROM product_images WHERE product_id=:pid AND variant_id IS NULL ORDER BY sort ASC, id ASC LIMIT 1");
                    $cand->execute([':pid' => $productId]);
                } else {
                    $cand = $this->db->prepare("SELECT id FROM product_images WHERE product_id=:pid AND variant_id=:vid ORDER BY sort ASC, id ASC LIMIT 1");
                    $cand->execute([':pid' => $productId, ':vid' => $currentVariantId]);
                }
                $newCoverId = (int)($cand->fetchColumn() ?: 0);
                if ($newCoverId > 0) {
                    $this->db->prepare("UPDATE product_images SET is_cover=1 WHERE id=:id")->execute([':id' => $newCoverId]);
                }
            }

            $this->db->commit();

            Audit::log('product.image_assign', 'product', $productId, [
                'image_id' => $imageId,
                'from_variant_id' => $currentVariantId,
                'to_variant_id' => $targetVariantId,
            ]);

            return $this->ok('Imagen asignada');
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            if (function_exists('log_error')) {
                log_error('ProductImagesService@assign: ' . $e->getMessage());
            }
            return $this->error('No se pudo asignar la imagen');
        }
    }

    public function bulkAssign(int $productId, int $targetVariantId, string|array $idsRaw): array
    {
        $ids = $this->normalizeIds($idsRaw);
        if ($productId <= 0 || empty($ids)) {
            return $this->error('Datos inválidos');
        }

        if ($targetVariantId > 0 && !$this->variantBelongsToProduct($productId, $targetVariantId)) {
            return $this->error('Variante inválida', 400);
        }

        $svc = new ProductImageService($this->db);

        try {
            $this->db->beginTransaction();

            $place = implode(',', array_fill(0, count($ids), '?'));
            $stSel = $this->db->prepare("SELECT id, variant_id, is_cover FROM product_images WHERE product_id = ? AND id IN ($place)");
            $stSel->execute(array_merge([$productId], $ids));
            $rows = $stSel->fetchAll(PDO::FETCH_ASSOC) ?: [];
            if (!$rows) {
                $this->db->rollBack();
                return $this->error('No se encontraron imágenes', 404);
            }

            $destVariant = ($targetVariantId > 0 ? $targetVariantId : null);
            if ($destVariant === null) {
                $stMax = $this->db->prepare("SELECT COALESCE(MAX(sort),0) FROM product_images WHERE product_id = :pid AND variant_id IS NULL");
                $stMax->execute([':pid' => $productId]);
            } else {
                $stMax = $this->db->prepare("SELECT COALESCE(MAX(sort),0) FROM product_images WHERE product_id = :pid AND variant_id = :vid");
                $stMax->execute([':pid' => $productId, ':vid' => $destVariant]);
            }
            $nextSort = ((int)$stMax->fetchColumn()) + 1;

            $coverRemovedScopes = [];
            $stUp = $this->db->prepare("UPDATE product_images SET variant_id = :vid, is_cover = 0, sort = :sort WHERE id = :id AND product_id = :pid");

            foreach ($rows as $r) {
                $oldVid = ($r['variant_id'] ?? null);
                $oldVid = ($oldVid === null || $oldVid === '' ? null : (int)$oldVid);
                $oldKey = ($oldVid === null ? '0' : (string)$oldVid);
                $wasCover = (int)($r['is_cover'] ?? 0) === 1;

                if ($wasCover) {
                    $coverRemovedScopes[$oldKey] = true;
                }

                $stUp->execute([
                    ':vid' => $destVariant,
                    ':sort' => $nextSort++,
                    ':id' => (int)$r['id'],
                    ':pid' => $productId,
                ]);
            }

            foreach (array_keys($coverRemovedScopes) as $scopeKey) {
                $scopeVid = ($scopeKey === '0' ? null : (int)$scopeKey);
                $svc->ensureCover($productId, $scopeVid);
            }

            $this->db->commit();

            $affectedIds = array_values(array_unique(array_map('intval', array_column($rows, 'id'))));
            try {
                Audit::log('product_images_bulk_assign', 'product', $productId, [
                    'variant_id' => $targetVariantId,
                    'image_ids' => $affectedIds,
                    'count' => count($affectedIds),
                ]);
                cache_forget('feed:audit_recent8');
            } catch (Throwable $e2) {
                if (function_exists('log_error')) {
                    log_error('ProductImagesService@bulkAssign audit: ' . $e2->getMessage());
                }
            }

            return $this->ok('Asignación aplicada');
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            if (function_exists('log_error')) {
                log_error('ProductImagesService@bulkAssign: ' . $e->getMessage());
            }
            return $this->error('No se pudo asignar');
        }
    }

    public function bulkDelete(int $productId, string|array $idsRaw): array
    {
        $ids = $this->normalizeIds($idsRaw);
        if ($productId <= 0 || empty($ids)) {
            return $this->error('Datos inválidos');
        }

        $svc = new ProductImageService($this->db);

        try {
            $place = implode(',', array_fill(0, count($ids), '?'));
            $stSel = $this->db->prepare("SELECT id, variant_id FROM product_images WHERE product_id = ? AND id IN ($place)");
            $stSel->execute(array_merge([$productId], $ids));
            $rows = $stSel->fetchAll(PDO::FETCH_ASSOC) ?: [];
            if (!$rows) {
                return $this->error('No se encontraron imágenes', 404);
            }

            $failed = 0;
            foreach ($rows as $r) {
                $vid = ($r['variant_id'] ?? null);
                $vid = ($vid === null || $vid === '' ? null : (int)$vid);
                [$ok] = $svc->delete($productId, (int)$r['id'], $vid);
                if (!$ok) {
                    $failed++;
                }
            }

            if ($failed > 0) {
                return $this->error("No se pudieron eliminar {$failed} imagen(es)");
            }

            Audit::log('product_images_bulk_delete', 'product', $productId, [
                'image_ids' => array_values($ids),
                'count' => count($ids),
            ]);
            cache_forget('feed:audit_recent8');

            return $this->ok('Imágenes eliminadas');
        } catch (Throwable $e) {
            if (function_exists('log_error')) {
                log_error('ProductImagesService@bulkDelete: ' . $e->getMessage());
            }
            return $this->error('No se pudo eliminar');
        }
    }

    public function sort(int $productId, array $order, int $variantId): array
    {
        if ($productId <= 0 || !is_array($order)) {
            return $this->error('Parámetros inválidos');
        }

        $wanted = array_values(array_unique(array_map('intval', $order)));
        $wanted = array_values(array_filter($wanted, fn($v) => $v > 0));

        if ($variantId > 0 && !$this->variantBelongsToProduct($productId, $variantId)) {
            return $this->error('Variante inválida', 422);
        }
        $scopeVid = $variantId > 0 ? $variantId : null;

        if ($scopeVid === null) {
            $stmt = $this->db->prepare("SELECT id FROM product_images WHERE product_id = ? AND variant_id IS NULL ORDER BY sort ASC, id ASC");
            $stmt->execute([$productId]);
        } else {
            $stmt = $this->db->prepare("SELECT id FROM product_images WHERE product_id = ? AND variant_id = ? ORDER BY sort ASC, id ASC");
            $stmt->execute([$productId, $scopeVid]);
        }
        $current = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $current = array_map('intval', $current);

        if (!$current) {
            return $this->error('No hay imágenes para ordenar');
        }

        $allowed = array_values(array_intersect($wanted, $current));
        $rest = array_values(array_diff($current, $allowed));
        $final = array_merge($allowed, $rest);

        try {
            $this->db->beginTransaction();
            if ($scopeVid === null) {
                $upd = $this->db->prepare("UPDATE product_images SET sort = ? WHERE product_id = ? AND variant_id IS NULL AND id = ?");
            } else {
                $upd = $this->db->prepare("UPDATE product_images SET sort = ? WHERE product_id = ? AND variant_id = ? AND id = ?");
            }

            $pos = 1;
            foreach ($final as $id) {
                if ($scopeVid === null) {
                    $upd->execute([$pos++, $productId, $id]);
                } else {
                    $upd->execute([$pos++, $productId, $scopeVid, $id]);
                }
            }

            $this->db->commit();
            return $this->ok('Orden guardado', ['count' => count($final)]);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            if (function_exists('log_error')) {
                log_error('ProductImagesService@sort: ' . $e->getMessage());
            }
            return $this->error('DB_ERROR');
        }
    }

    private function variantBelongsToProduct(int $productId, int $variantId): bool
    {
        if ($productId <= 0 || $variantId <= 0) {
            return false;
        }
        try {
            $st = $this->db->prepare("SELECT COUNT(*) FROM product_variants WHERE id = ? AND product_id = ? LIMIT 1");
            $st->execute([$variantId, $productId]);
            return (int)($st->fetchColumn() ?: 0) > 0;
        } catch (Throwable) {
            return false;
        }
    }

    private function normalizeIds(string|array $idsRaw): array
    {
        if (is_array($idsRaw)) {
            $idsRaw = implode(',', $idsRaw);
        }
        $idsRaw = (string)$idsRaw;
        return array_values(array_unique(array_filter(array_map('intval', preg_split('/[^0-9]+/', $idsRaw)))));
    }

    private function ok(string $message, array $data = []): array
    {
        return ['ok' => true, 'message' => $message, 'data' => $data];
    }

    private function error(string $message, int $status = 400): array
    {
        return ['ok' => false, 'message' => $message, 'status' => $status, 'data' => []];
    }
}

