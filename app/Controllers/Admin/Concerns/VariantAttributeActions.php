<?php
namespace App\Controllers\Admin\Concerns;

use App\Services\Admin\Variants\VariantAttributesService;
use App\Services\Admin\Variants\VariantValueSetsService;
use Throwable;

trait VariantAttributeActions
{
    public function attributeAdd(): void
    {
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();
        $productId   = (int)($_POST['product_id'] ?? 0);
        $attributeId = (int)($_POST['attribute_id'] ?? 0);
        if ($productId<=0 || $attributeId<=0) { $this->json(['ok'=>false,'message'=>'Datos inválidos.'], 400); return; }

        $svc = new VariantAttributesService(db());
        try {
            $svc->addAttribute($productId, $attributeId);
            $this->json(['ok'=>true]);
        } catch (Throwable $e) {
            if (function_exists('log_error')) log_error('Admin\\ProductVariantsController@attributeAdd: '.$e->getMessage());
            $this->json(['ok'=>false,'message'=>'No se pudo agregar el atributo.'], 500);
        }
    }

    public function attributeRemove(): void
    {
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();
        $productId   = (int)($_POST['product_id'] ?? 0);
        $attributeId = (int)($_POST['attribute_id'] ?? 0);
        if ($productId<=0 || $attributeId<=0) { $this->json(['ok'=>false,'message'=>'Datos inválidos.'], 400); return; }

        $svc = new VariantAttributesService(db());
        try {
            $svc->removeAttribute($productId, $attributeId);
            $this->json(['ok'=>true]);
        } catch (Throwable $e) {
            if (function_exists('log_error')) log_error('Admin\\ProductVariantsController@attributeRemove: '.$e->getMessage());
            $this->json(['ok'=>false,'message'=>'No se pudo quitar el atributo.'], 500);
        }
    }

    public function attributeRemovePreview(): void
    {
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();
        $productId   = (int)($_POST['product_id'] ?? 0);
        $attributeId = (int)($_POST['attribute_id'] ?? 0);
        if ($productId<=0 || $attributeId<=0) { $this->json(['ok'=>false,'message'=>'Datos inválidos.'], 400); return; }

        $svc = new VariantAttributesService(db());
        try {
            $preview = $svc->previewRemoveAttribute($productId, $attributeId);
            $this->json(['ok'=>true] + $preview);
        } catch (Throwable $e) {
            if (function_exists('log_error')) log_error('Admin\\ProductVariantsController@attributeRemovePreview: '.$e->getMessage());
            $this->json(['ok'=>false,'message'=>'No se pudo obtener la vista previa.'], 500);
        }
    }

    public function attributeUpdate(): void
    {
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();
        $productId   = (int)($_POST['product_id'] ?? 0);
        $attributeId = (int)($_POST['attribute_id'] ?? 0);
        $sort        = (int)($_POST['sort'] ?? 0);
        $required    = (int)($_POST['is_required'] ?? 1) ? 1 : 0;
        if ($productId<=0 || $attributeId<=0) { $this->json(['ok'=>false,'message'=>'Datos inválidos.'], 400); return; }

        $svc = new VariantAttributesService(db());
        try {
            $svc->updateProductAttribute($productId, $attributeId, $sort, $required);
            $this->json(['ok'=>true]);
        } catch (Throwable $e) {
            if (function_exists('log_error')) log_error('Admin\\ProductVariantsController@attributeUpdate: '.$e->getMessage());
            $this->json(['ok'=>false,'message'=>'No se pudo actualizar.'], 500);
        }
    }

    public function valuesSet(): void
    {
        try {
            verify_csrf_or_abort();
            $productId = (int)($_POST['product_id'] ?? 0);
            $attributeId = (int)($_POST['attribute_id'] ?? 0);
            if ($productId <= 0 || $attributeId <= 0) {
                $this->json(['ok'=>false,'message'=>'Datos inválidos.'], 400);
                return;
            }

            $svc = new VariantAttributesService(db());
            if (isset($_POST['value_id'])) {
                $valueId = (int)($_POST['value_id'] ?? 0);
                $selected = (int)($_POST['selected'] ?? 0) === 1;
                if ($valueId <= 0) {
                    $this->json(['ok'=>false,'message'=>'Datos inválidos.'], 400);
                    return;
                }

                $valueIdsRaw = $_POST['value_ids'] ?? null;
                $ids = [];
                if ($valueIdsRaw !== null) {
                    if (is_string($valueIdsRaw)) {
                        $parts = array_filter(array_map('trim', explode(',', $valueIdsRaw)));
                        $ids = array_values(array_unique(array_map('intval', $parts)));
                    } elseif (is_array($valueIdsRaw)) {
                        $ids = array_values(array_unique(array_map('intval', $valueIdsRaw)));
                    }
                }
                if (!$ids) {
                    $pdo = db();
                    $st = $pdo->prepare("SELECT value_id FROM product_attribute_values WHERE product_id=? AND attribute_id=?");
                    $st->execute([$productId, $attributeId]);
                    $ids = array_map('intval', $st->fetchAll(\PDO::FETCH_COLUMN) ?: []);
                }
                if ($selected) {
                    if (!in_array($valueId, $ids, true)) $ids[] = $valueId;
                } else {
                    $ids = array_values(array_filter($ids, fn($x) => (int)$x !== $valueId));
                }

                $svc->setAttributeValues($productId, $attributeId, $ids);
                $pruned = false;
                if (!$selected && (int)($_POST['prune_unused'] ?? 0) === 1) {
                    $pruned = $svc->pruneAttributeValueIfUnused($valueId);
                }
                $this->json(['ok'=>true, 'pruned'=>$pruned]);
                return;
            }

            $valuesRaw = $_POST['value_ids'] ?? [];
            if (is_string($valuesRaw)) {
                $valuesRaw = array_filter(array_map('trim', explode(',', $valuesRaw)));
            }
            $valueIds = is_array($valuesRaw) ? $valuesRaw : [];
            $svc->setAttributeValues($productId, $attributeId, $valueIds);
            $this->json(['ok'=>true]);
        } catch (Throwable $e) {
            log_error(__METHOD__.': '.$e->getMessage());
            $this->json(['ok'=>false,'message'=>'No se pudieron guardar los valores.'], 500);
        }
    }

    public function valuesCreate(): void
    {
        try {
            verify_csrf_or_abort();
            $productId = (int)($_POST['product_id'] ?? 0);
            $attributeId = (int)($_POST['attribute_id'] ?? 0);
            $raw = (string)($_POST['values'] ?? '');
            if ($productId <= 0 || $attributeId <= 0) {
                $this->json(['ok'=>false,'message'=>'Datos inválidos.'], 400);
                return;
            }
            if (trim($raw) === '') {
                $this->json(['ok'=>false,'message'=>'No hay valores.'], 400);
                return;
            }

            $svc = new VariantAttributesService(db());
            $out = $svc->createValuesAndSelect($productId, $attributeId, $raw);
            $this->json(['ok'=>true,'data'=>$out]);
        } catch (Throwable $e) {
            if (function_exists('log_error')) log_error(__METHOD__.': '.$e->getMessage());
            $msg = 'No se pudieron agregar valores.';
            if (function_exists('env') && filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOL)) {
                $msg .= ' (debug) '.$e->getMessage();
            }
            $this->json(['ok'=>false,'message'=>$msg], 500);
        }
    }

    public function createValueSetFromProduct(): void
    {
        try {
            verify_csrf_or_abort();
            $productId = (int)($_POST['product_id'] ?? 0);
            $name = trim((string)($_POST['name'] ?? ''));
            if ($productId <= 0 || $name === '') {
                $this->json(['ok'=>false,'message'=>'Completá el nombre de la plantilla.'], 400);
                return;
            }

            $svc = new VariantValueSetsService(db());
            $out = $svc->createValueSetFromProduct($productId, $name);
            if (!empty($out['ok'])) {
                $this->json(['ok'=>true,'data'=>$out,'message'=>($out['message'] ?? 'Plantilla guardada.')]);
            } else {
                $this->json(['ok'=>false,'message'=>($out['message'] ?? 'No se pudo guardar la plantilla.')], 422);
            }
        } catch (Throwable $e) {
            if (function_exists('log_error')) log_error(__METHOD__.': '.$e->getMessage());
            $msg = 'No se pudo guardar la plantilla.';
            if (function_exists('env') && filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOL)) {
                $msg .= ' (debug) '.$e->getMessage();
            }
            $this->json(['ok'=>false,'message'=>$msg], 500);
        }
    }

    public function importValueSet(): void
    {
        try {
            verify_csrf_or_abort();
            $productId = (int)($_POST['product_id'] ?? 0);
            $setId = (int)($_POST['set_id'] ?? 0);
            if ($productId <= 0 || $setId <= 0) {
                $this->json(['ok'=>false,'message'=>'Datos inválidos.'], 400);
                return;
            }

            $svc = new VariantValueSetsService(db());
            $out = $svc->importValueSet($productId, $setId);
            if (!empty($out['ok'])) {
                $this->json(['ok'=>true,'data'=>$out,'message'=>($out['message'] ?? 'Plantilla importada.')]);
            } else {
                $this->json(['ok'=>false,'message'=>($out['message'] ?? 'No se pudo importar la plantilla.')], 422);
            }
        } catch (Throwable $e) {
            if (function_exists('log_error')) log_error(__METHOD__.': '.$e->getMessage());
            $msg = 'No se pudo importar la plantilla.';
            if (function_exists('env') && filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOL)) {
                $msg .= ' (debug) '.$e->getMessage();
            }
            $this->json(['ok'=>false,'message'=>$msg], 500);
        }
    }

    public function valuesSuggestFragment(): void
    {
        $productId = (int)($_GET['product_id'] ?? 0);
        $attributeId = (int)($_GET['attribute_id'] ?? 0);
        $q = trim((string)($_GET['q'] ?? ''));
        if ($productId <= 0 || $attributeId <= 0 || $q === '') {
            header('Content-Type: text/html; charset=UTF-8');
            echo '';
            return;
        }

        $svc = new VariantAttributesService(db());
        try {
            $suggestions = $svc->suggestValues($productId, $attributeId, $q, 12);
        } catch (Throwable $e) {
            $suggestions = [];
        }

        $file = base_path('app/Views/partials/admin/products/variants/_value_suggestions.php');
        header('Content-Type: text/html; charset=UTF-8');
        if (!is_file($file)) { echo ''; return; }
        include $file;
    }
}
