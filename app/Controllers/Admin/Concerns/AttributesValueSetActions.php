<?php
namespace App\Controllers\Admin\Concerns;

use App\Services\Admin\Variants\VariantValueSetsService;
use App\Services\Admin\Attributes\ViewModels\AttributeValueSetEditViewModel;
use Throwable;

trait AttributesValueSetActions
{
    public function valueSetItemsFragment(): void
    {
        $db = db();
        $id = (int)($_GET['id'] ?? 0);
        $svc = new VariantValueSetsService($db);
        if (!$svc->valueSetsTablesExist() || $id <= 0) {
            http_response_code(400);
            echo '<div class="av3-attributes__notice">No disponible.</div>';
            return;
        }

        $st = $db->prepare("SELECT s.id, s.name, c.name AS category_name, b.name AS brand_name
                            FROM variant_value_sets s
                            LEFT JOIN categories c ON c.id = s.category_id
                            LEFT JOIN brands b ON b.id = s.brand_id
                            WHERE s.id = ? LIMIT 1");
        $st->execute([$id]);
        $set = $st->fetch(\PDO::FETCH_ASSOC);
        if (!$set) {
            http_response_code(404);
            echo '<div class="av3-attributes__notice">No encontrada.</div>';
            return;
        }

        $items = $svc->getValueSetItems($id);
        // Es un endpoint de fragmento: nunca incrustar un segundo layout HTML en el modal.
        require base_path('app/Views/pages/admin/attributes/value_set_items.php');
    }

    public function valueSetDeleteJson(): void
    {
        $db = db();
        header('Content-Type: application/json; charset=utf-8');
        if (!is_post()) {
            http_response_code(405);
            echo json_encode(['ok'=>false,'message'=>'Método inválido.']);
            return;
        }

        try {
            if (!csrf_check($_POST['_csrf'] ?? null)) {
                http_response_code(403);
                echo json_encode(['ok'=>false,'message'=>'CSRF inválido.']);
                return;
            }

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['ok'=>false,'message'=>'ID inválido.']);
                return;
            }

            $svc = new VariantValueSetsService($db);
            if (!$svc->valueSetsTablesExist()) {
                http_response_code(400);
                echo json_encode(['ok'=>false,'message'=>'Plantillas no disponibles en este entorno.']);
                return;
            }

            if (!$svc->getValueSet($id)) {
                http_response_code(404);
                echo json_encode(['ok'=>false,'message'=>'Plantilla no encontrada.']);
                return;
            }
            $ok = $svc->deleteValueSet($id);
            if (!$ok) {
                http_response_code(500);
                echo json_encode(['ok'=>false,'message'=>'No se pudo eliminar la plantilla.']);
                return;
            }

            echo json_encode(['ok'=>true,'message'=>'Plantilla eliminada.']);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok'=>false,'message'=>'Error inesperado.']);
        }
    }

    public function valueSetEditFragment(): void
    {
        $db = db();
        $svc = new VariantValueSetsService($db);

        if (!$svc->valueSetsTablesExist()) {
            http_response_code(400);
            echo '<div class="av3-attributes__notice">No están disponibles las tablas de plantillas.</div>';
            return;
        }

        if (is_post()) {
            verify_csrf_or_abort();
        }

        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo '<div class="av3-attributes__notice">Falta id.</div>';
            return;
        }

        $set = $svc->getValueSet($id);
        if (!$set) {
            http_response_code(404);
            echo '<div class="av3-attributes__notice">No se encontró la plantilla.</div>';
            return;
        }

        // En GET la edición es de solo lectura; las altas de valores requieren POST + CSRF.
        $addAttrId = is_post() ? (int)($_POST['add_attribute_id'] ?? 0) : 0;
        $addValuesAttrId = is_post() ? (int)($_POST['add_values_attribute_id'] ?? 0) : 0;
        $addValuesCsv = is_post() ? trim((string)($_POST['add_values_csv'] ?? '')) : '';
        $postedValues = is_post() ? ($_POST['values'] ?? []) : null;
        $groups = $svc->getValueSetEditGroups($id, $postedValues, $addAttrId, $addValuesAttrId, $addValuesCsv);

        $vm = new AttributeValueSetEditViewModel();
        $fragmentVars = $vm->build([
            'set' => $set,
            'categories' => $svc->listCategories(),
            'brands' => $svc->listBrands(),
            'groups' => $groups,
            'all_attributes' => $svc->listAttributes(),
        ]);
        extract($fragmentVars, EXTR_SKIP);
        require base_path('app/Views/pages/admin/attributes/value_set_edit.php');
    }

    public function valueSetSaveJson(): void
    {
        $db = db();
        $svc = new VariantValueSetsService($db);
        header('Content-Type: application/json; charset=utf-8');

        if (!$svc->valueSetsTablesExist()) {
            http_response_code(400);
            echo json_encode(['ok'=>false,'message'=>'No están disponibles las tablas de plantillas.']);
            return;
        }
        if (!is_post()) {
            http_response_code(405);
            echo json_encode(['ok'=>false,'message'=>'Método inválido.']);
            return;
        }

        try {
            verify_csrf_or_abort();
            $id = (int)($_POST['id'] ?? 0);
            $name = trim((string)($_POST['name'] ?? ''));
            $categoryId = (int)($_POST['category_id'] ?? 0);
            $brandId = (int)($_POST['brand_id'] ?? 0);
            $values = $_POST['values'] ?? [];

            if ($id <= 0) throw new \Exception('Falta id.');
            if ($name === '') throw new \Exception('El nombre es obligatorio.');
            if ($categoryId <= 0) throw new \Exception('La categoría es obligatoria.');
            if ($brandId <= 0) throw new \Exception('La marca es obligatoria.');

            if (!is_array($values)) throw new \InvalidArgumentException('Los valores deben ser una lista.');
            $pairs = [];
            foreach ($values as $rawAid => $vids) {
                if (!ctype_digit((string)$rawAid) || (int)$rawAid <= 0 || !is_array($vids)) {
                    throw new \InvalidArgumentException('Hay un atributo inválido en la selección.');
                }
                $aid = (int)$rawAid;
                foreach ($vids as $rawVid) {
                    // El 0 es un placeholder oculto de los checkboxes del formulario.
                    if ((string)$rawVid === '0') continue;
                    if (!is_scalar($rawVid) || !ctype_digit((string)$rawVid) || (int)$rawVid <= 0) {
                        throw new \InvalidArgumentException('Hay un valor inválido en la selección.');
                    }
                    $pairs[] = [$aid, (int)$rawVid];
                }
            }
            if (count($pairs) <= 0) throw new \Exception('Seleccioná al menos 1 valor para guardar la plantilla.');

            $ok = $svc->updateValueSet($id, $name, $categoryId, $brandId, $pairs);
            if (!$ok) {
                $code = $svc->lastErrorCode();
                http_response_code($code === 'NOT_FOUND' ? 404 : ($code === 'DUPLICATE_SET' ? 409 : ($code === 'SQL_ERROR' ? 500 : 422)));
                echo json_encode(['ok'=>false, 'code'=>$code, 'message'=>$svc->lastError() ?: 'No se pudo guardar.']);
                return;
            }
            $row = $svc->getValueSetAdminRow($id);

            echo json_encode(['ok'=>true,'message'=>'Plantilla actualizada.','data'=>$row]);
        } catch (Throwable $e) {
            http_response_code(400);
            echo json_encode(['ok'=>false,'message'=>$e->getMessage()]);
        }
    }
}
