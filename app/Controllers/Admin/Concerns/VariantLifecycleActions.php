<?php
namespace App\Controllers\Admin\Concerns;

use App\Services\Admin\Variants\VariantAttributesService;
use App\Services\Admin\Variants\VariantLifecycleService;
use App\Services\Admin\Variants\VariantWriteService;
use PDO;
use Throwable;

trait VariantLifecycleActions
{
    public function generate(): void
    {
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();
        $productId = (int)($_POST['product_id'] ?? 0);
        if ($productId<=0) { $this->json(['ok'=>false,'message'=>'Producto inválido.'], 400); return; }

        $selRaw = (string)($_POST['selections'] ?? '');
        $selections = [];
        if ($selRaw !== '') {
            $tmp = json_decode($selRaw, true);
            if (is_array($tmp)) $selections = $tmp;
        }

        $obsoleteMode = strtolower(trim((string)($_POST['obsolete_mode'] ?? 'pause')));
        if (!in_array($obsoleteMode, ['pause','delete'], true)) $obsoleteMode = 'pause';

        $db = db();
        $writeSvc = new VariantWriteService($db);
        $attrSvc = new VariantAttributesService($db);
        $lifeSvc = new VariantLifecycleService($db);
        $startedTx = false;

        try {
            if (!$db->inTransaction()) { $db->beginTransaction(); $startedTx = true; }

            if ($selections) {
                foreach ($selections as $attrId => $valueIds) {
                    $aid = (int)$attrId;
                    if ($aid <= 0) continue;
                    if (!is_array($valueIds)) $valueIds = [];
                    $ids = array_values(array_unique(array_filter(array_map('intval', $valueIds), fn($n)=>$n>0)));
                    $attrSvc->setAttributeValues($productId, $aid, $ids);
                }
            }

            $gen = $writeSvc->generateVariants($productId);
            $obs = $lifeSvc->cleanupObsoleteVariants($productId, $obsoleteMode);
            if ($startedTx) $db->commit();

            $msgParts = [];
            $created = (int)($gen['created'] ?? 0);
            $skipped = (int)($gen['skipped'] ?? 0);
            if ($created > 0) $msgParts[] = "+{$created} creadas";
            if ($skipped > 0) $msgParts[] = "{$skipped} ya existían";
            $obsTotal = (int)($obs['total'] ?? 0);
            if ($obsTotal > 0) {
                $paused = (int)($obs['paused'] ?? 0);
                $deleted = (int)($obs['deleted'] ?? 0);
                if ($paused > 0) $msgParts[] = "{$paused} sobrantes pausadas";
                if ($deleted > 0) $msgParts[] = "{$deleted} sobrantes eliminadas";
            }
            $message = $msgParts ? ('Combinaciones actualizadas: ' . implode('  ·  ', $msgParts) . '.') : 'Combinaciones actualizadas.';
            $this->json(['ok'=>true,'message'=>$message,'data'=>['generate'=>$gen,'obsolete'=>$obs]]);
        } catch (Throwable $e) {
            if ($startedTx) { try { $db->rollBack(); } catch (Throwable $_) {} }
            if (function_exists('log_error')) log_error('Admin\ProductVariantsController@generate: '.$e->getMessage());
            $this->json(['ok'=>false,'message'=>'No se pudieron generar variantes.'], 500);
        }
    }

    public function saveVariant(): void
    {
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();
        $productId = (int)($_POST['product_id'] ?? 0);
        $variantId = (int)($_POST['variant_id'] ?? 0);
        if ($productId<=0 || $variantId<=0) { $this->json(['ok'=>false,'message'=>'Datos inválidos.'], 400); return; }

        $payload = [
            'sku'   => trim((string)($_POST['sku'] ?? '')),
            'price' => ($_POST['price'] ?? '') !== '' ? (float)$_POST['price'] : null,
            'list_price' => ($_POST['list_price'] ?? '') !== '' ? (float)$_POST['list_price'] : null,
            'cost'  => ($_POST['cost'] ?? '') !== '' ? (float)$_POST['cost'] : null,
            'margin_pct' => (function() {
                $raw = trim((string)($_POST['margin_pct'] ?? ''));
                if ($raw === '') return null;
                $raw = str_replace(['%',' '], '', $raw);
                $raw = str_replace(',', '.', $raw);
                $n = (float)$raw;
                if ($n >= 1.0) $n = $n / 100.0;
                return $n;
            })(),
            'stock' => (int)($_POST['stock'] ?? 0),
            'low_stock_threshold' => (int)($_POST['low_stock_threshold'] ?? 3),
            'is_active' => (int)($_POST['is_active'] ?? 1) ? 1 : 0,
            'ship_weight' => (($_POST['ship_weight'] ?? '') !== '') ? max(1, (int)$_POST['ship_weight']) : null,
            'ship_height' => (($_POST['ship_height'] ?? '') !== '') ? max(1, (int)$_POST['ship_height']) : null,
            'ship_width'  => (($_POST['ship_width']  ?? '') !== '') ? max(1, (int)$_POST['ship_width'])  : null,
            'ship_length' => (($_POST['ship_length'] ?? '') !== '') ? max(1, (int)$_POST['ship_length']) : null,
            'tag_ids' => (function () {
                $raw = $_POST['tag_ids'] ?? [];
                if (is_string($raw)) {
                    $parts = array_map('trim', explode(',', $raw));
                    return array_values(array_unique(array_filter(array_map('intval', $parts))));
                }
                if (is_array($raw)) {
                    return array_values(array_unique(array_filter(array_map('intval', $raw))));
                }
                return [];
            })(),
        ];

        if (!isset($_POST['sku']) || trim((string)$_POST['sku']) === '') {
            try {
                $stSku = db()->prepare("SELECT sku FROM product_variants WHERE id=:id AND product_id=:pid LIMIT 1");
                $stSku->execute([':id'=>$variantId, ':pid'=>$productId]);
                $currentSku = (string)($stSku->fetchColumn() ?: '');
                if ($currentSku !== '') { $payload['sku'] = $currentSku; }
            } catch (Throwable $_) {}
        }

        $db = db();
        $before = null;
        try { if ($db instanceof PDO) $before = $this->auditService($db)->snapshotVariant($productId, $variantId); } catch (Throwable $e) { $before = null; }

        $svc = new VariantWriteService($db);
        try {
            $svc->saveVariant($productId, $variantId, $payload);
            try {
                if ($db instanceof PDO) {
                    $after = $this->auditService($db)->snapshotVariant($productId, $variantId);
                    $this->auditService($db)->logVariantSave((int)$productId, (int)$variantId, $before, $after);
                }
            } catch (Throwable $e) {}
            $this->json(['ok'=>true]);
        } catch (\RuntimeException $e) {
            $msg = $e->getMessage();
            $field = 'sku';
            if (stripos($msg, 'SKU') === false) $field = '';
            $payload = ['ok'=>false,'message'=>$msg];
            if ($field !== '') {
                $payload['field_errors'] = [$field => $msg];
                $payload['errors'] = [$field => $msg];
            }
            $this->json($payload, 422);
        } catch (Throwable $e) {
            if (function_exists('log_error')) log_error('Admin\\ProductVariantsController@saveVariant: '.$e->getMessage());
            $this->json(['ok'=>false,'message'=>'No se pudo guardar la variante.'], 500);
        }
    }

    public function toggle(): void
    {
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();
        $productId = (int)($_POST['product_id'] ?? 0);
        $variantId = (int)($_POST['variant_id'] ?? 0);
        $active    = (int)($_POST['is_active'] ?? 1) ? 1 : 0;
        if ($productId<=0 || $variantId<=0) { $this->json(['ok'=>false,'message'=>'Datos inválidos.'], 400); return; }

        $db = db();
        $before = null;
        try { if ($db instanceof PDO) $before = $this->auditService($db)->snapshotVariant($productId, $variantId); } catch (Throwable $e) { $before = null; }

        $svc = new VariantLifecycleService($db);
        try {
            $svc->toggle($productId, $variantId, $active);
            try {
                if ($db instanceof PDO) {
                    $after = $this->auditService($db)->snapshotVariant($productId, $variantId);
                    $this->auditService($db)->logVariantToggle((int)$productId, (int)$variantId, $before, $after);
                }
            } catch (Throwable $e) {}
            $this->json(['ok'=>true]);
        } catch (Throwable $e) {
            if (function_exists('log_error')) log_error('Admin\\ProductVariantsController@toggle: '.$e->getMessage());
            $this->json(['ok'=>false,'message'=>'No se pudo actualizar.'], 500);
        }
    }

    public function deleteVariant(): void
    {
        if (function_exists('auth_required')) auth_required();
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();

        $productId = (int)($_POST['product_id'] ?? 0);
        $variantId = (int)($_POST['variant_id'] ?? 0);
        $confirm   = trim((string)($_POST['confirm'] ?? ''));
        if ($productId <= 0 || $variantId <= 0) { $this->json(['ok'=>false,'message'=>'Datos inválidos.'], 400); return; }
        if ($confirm !== 'ELIMINAR') { $this->json(['ok'=>false,'message'=>'Confirmación inválida. Escribí ELIMINAR.'], 422); return; }

        $db = db();
        $before = null;
        try { if ($db instanceof PDO) $before = $this->auditService($db)->snapshotVariant($productId, $variantId); } catch (Throwable $e) { $before = null; }

        $svc = new VariantLifecycleService($db);
        try {
            $out = $svc->deleteVariant($productId, $variantId);
            $action = (string)($out['action'] ?? '');
            if ($action === 'paused') {
                try {
                    if ($db instanceof PDO) {
                        $afterSnapshot = $this->auditService($db)->snapshotVariant($productId, $variantId);
                        $this->auditService($db)->logVariantDeletePaused((int)$productId, (int)$variantId, $before, $afterSnapshot);
                    }
                } catch (Throwable $e) {}
                $this->json(['ok'=>true,'data'=>['action'=>'paused'],'message'=>'La variante tiene pedidos: se pausó (no se puede eliminar).']);
                return;
            }

            try {
                $this->auditService($db)->logVariantDeleteDeleted((int)$productId, (int)$variantId, $before);
            } catch (Throwable $e) {}
            $cleared = !empty($out['cleared_config']);
            $msg = $cleared
                ? 'Variante eliminada. Como ya no quedan variantes, se limpiaron los atributos para comenzar de cero.'
                : 'Variante eliminada.';
            $this->json(['ok'=>true,'data'=>['action'=>'deleted','cleared_config'=>$cleared],'message'=>$msg]);
        } catch (\RuntimeException $e) {
            $this->json(['ok'=>false,'message'=>$e->getMessage()], 404);
        } catch (Throwable $e) {
            if (function_exists('log_error')) log_error('Admin\ProductVariantsController@deleteVariant: '.$e->getMessage());
            $this->json(['ok'=>false,'message'=>'No se pudo eliminar la variante.'], 500);
        }
    }

    public function cleanupObsolete(): void
    {
        if (function_exists('auth_required')) auth_required();
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();

        $productId = (int)($_POST['product_id'] ?? 0);
        $mode      = trim((string)($_POST['mode'] ?? 'pause'));
        $confirm   = trim((string)($_POST['confirm'] ?? ''));
        if ($productId <= 0) { $this->json(['ok'=>false,'message'=>'Producto inválido.'], 400); return; }

        $mode = strtolower($mode);
        if (!in_array($mode, ['pause','delete'], true)) $mode = 'pause';
        if ($mode === 'delete' && $confirm !== 'ELIMINAR') {
            $this->json(['ok'=>false,'message'=>'Confirmación inválida. Escribí ELIMINAR.'], 422);
            return;
        }

        $db = db();
        $svc = new VariantLifecycleService($db);
        try {
            $out = $svc->cleanupObsoleteVariants($productId, $mode);
            $tot = (int)($out['total'] ?? 0);
            $del = (int)($out['deleted'] ?? 0);
            $pau = (int)($out['paused'] ?? 0);
            if ($tot <= 0) {
                $this->json(['ok'=>true,'data'=>$out,'message'=>'No hay variantes sobrantes para limpiar.']);
                return;
            }

            $msg = ($mode === 'pause')
                ? ('Sobrantes pausadas: '.$pau.'.')
                : ('Sobrantes eliminadas: '.$del.'. Pausadas (con pedidos): '.$pau.'.');
            try {
                $this->auditService($db)->logCleanupObsolete((int)$productId, $mode, $tot, $del, $pau);
            } catch (Throwable $e) {}

            $this->json(['ok'=>true,'data'=>$out,'message'=>$msg]);
        } catch (Throwable $e) {
            $this->json(['ok'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    public function deleteIncomplete(): void
    {
        if (function_exists('auth_required')) auth_required();
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();

        $productId = (int)($_POST['product_id'] ?? 0);
        $confirm   = trim((string)($_POST['confirm'] ?? ''));
        if ($productId <= 0) { $this->json(['ok'=>false,'message'=>'Producto inválido.'], 400); return; }
        if ($confirm !== 'ELIMINAR') {
            $this->json(['ok'=>false,'message'=>'Confirmación inválida. Escribí ELIMINAR.'], 422);
            return;
        }

        $db = db();
        $svc = new VariantLifecycleService($db);
        try {
            $out = $svc->deleteIncompleteVariants($productId);
            $msg = 'Acción completada.';
            $del = (int)($out['deleted'] ?? 0);
            $pau = (int)($out['paused'] ?? 0);
            if ($del || $pau) $msg = 'Eliminadas: '.$del.'. Pausadas: '.$pau.'.';

            try {
                $this->auditService($db)->logDeleteIncomplete((int)$productId, $del, $pau);
            } catch (Throwable $e) {}

            $this->json(['ok'=>true,'data'=>$out,'message'=>$msg]);
        } catch (\RuntimeException $e) {
            $this->json(['ok'=>false,'message'=>$e->getMessage()], 409);
        } catch (Throwable $e) {
            if (function_exists('log_error')) log_error('Admin\\ProductVariantsController@deleteIncomplete: '.$e->getMessage());
            $this->json(['ok'=>false,'message'=>'No se pudieron eliminar las variantes incompletas.'], 500);
        }
    }
}
