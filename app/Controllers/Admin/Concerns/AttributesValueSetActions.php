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
            echo '<div class="text-sm text-[color:var(--admin-muted)]">No disponible.</div>';
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
            echo '<div class="text-sm text-[color:var(--admin-muted)]">No encontrada.</div>';
            return;
        }

        $items = $svc->getValueSetItems($id);
        render_page('admin/attributes/value_set_items', [
            'set' => $set,
            'items' => $items,
        ]);
    }

    public function valueSetDeleteJson(): void
    {
        $db = db();
        header('Content-Type: application/json; charset=utf-8');

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
            echo '<div class="text-sm text-[color:var(--admin-muted)]">No están disponibles las tablas de plantillas.</div>';
            return;
        }

        $id = (int)($_REQUEST['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo '<div class="text-sm text-[color:var(--admin-muted)]">Falta id.</div>';
            return;
        }

        $set = $svc->getValueSet($id);
        if (!$set) {
            http_response_code(404);
            echo '<div class="text-sm text-[color:var(--admin-muted)]">No se encontró la plantilla.</div>';
            return;
        }

        $addAttrId = (int)($_REQUEST['add_attribute_id'] ?? 0);
        $addValuesAttrId = (int)($_REQUEST['add_values_attribute_id'] ?? 0);
        $addValuesCsv = trim((string)($_REQUEST['add_values_csv'] ?? ''));
        $postedValues = $_REQUEST['values'] ?? null;
        $groups = $svc->getValueSetEditGroups($id, $postedValues, $addAttrId, $addValuesAttrId, $addValuesCsv);

        $vm = new AttributeValueSetEditViewModel();
        render_page('admin/attributes/value_set_edit', $vm->build([
            'set' => $set,
            'categories' => $svc->listCategories(),
            'brands' => $svc->listBrands(),
            'groups' => $groups,
            'all_attributes' => $svc->listAttributes(),
        ]));
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

            $pairs = [];
            if (is_array($values)) {
                foreach ($values as $aid => $vids) {
                    $aid = (int)$aid;
                    if ($aid <= 0 || !is_array($vids)) continue;
                    foreach ($vids as $vid) {
                        $vid = (int)$vid;
                        if ($vid <= 0) continue;
                        $pairs[] = [$aid, $vid];
                    }
                }
            }
            if (count($pairs) <= 0) throw new \Exception('Seleccioná al menos 1 valor para guardar la plantilla.');

            $ok = $svc->updateValueSet($id, $name, $categoryId, $brandId, $pairs);
            if (!$ok) throw new \Exception('No se pudo guardar.');
            $row = $svc->getValueSetAdminRow($id);

            echo json_encode(['ok'=>true,'message'=>'Plantilla actualizada.','data'=>$row]);
        } catch (Throwable $e) {
            http_response_code(400);
            echo json_encode(['ok'=>false,'message'=>$e->getMessage()]);
        }
    }
}
