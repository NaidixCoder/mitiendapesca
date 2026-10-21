<?php
namespace App\Controllers\Admin\Concerns;

use App\Services\Admin\Variants\VariantValueSetsService;
use App\Services\Admin\Attributes\ViewModels\AttributesIndexViewModel;
use App\Services\Admin\Attributes\ViewModels\AttributesFormViewModel;
use App\Support\Admin\AttributesSchema;
use PDO;
use Throwable;

trait AttributesCatalogActions
{
    public function index(): void
    {
        $db = db();

        $q  = trim($_GET['q'] ?? '');
        $where = '';
        $params = [];
        if ($q !== '') {
            $where = 'WHERE (name LIKE :q OR slug LIKE :q';
            if (AttributesSchema::hasColumn($db, 'attributes', 'code')) {
                $where .= ' OR code LIKE :q';
            }
            $where .= ')';
            $params[':q'] = '%'.$q.'%';
        }

        $selectColumns = implode(', ', AttributesSchema::editableColumns($db)) . ', created_at';
        $st = $db->prepare("SELECT {$selectColumns} FROM attributes $where ORDER BY name ASC");
        $st->execute($params);
        $rows = array_map(static fn(array $row): array => AttributesSchema::normalizeRow($row), $st->fetchAll(PDO::FETCH_ASSOC) ?: []);

        $stats = [
            'attributes' => (int)($db->query('SELECT COUNT(*) FROM attributes')->fetchColumn() ?: 0),
            'values'     => (int)($db->query('SELECT COUNT(*) FROM attribute_values')->fetchColumn() ?: 0),
        ];

        $setsQ = trim((string)($_GET['sets_q'] ?? ''));
        $setsCategoryId = (int)($_GET['sets_category_id'] ?? 0);
        $setsBrandId    = (int)($_GET['sets_brand_id'] ?? 0);

        $svc = new VariantValueSetsService($db);
        $setsTablesOk = $svc->valueSetsTablesExist();
        $categories = $db->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $brands     = $db->query("SELECT id, name FROM brands ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $sets = $setsTablesOk
            ? $svc->listValueSetsAdmin([
                'q' => $setsQ,
                'category_id' => $setsCategoryId,
                'brand_id' => $setsBrandId,
              ], 120)
            : [];

        $vm = (new AttributesIndexViewModel())->build([
            'rows' => $rows,
            'q' => $q,
            'stats' => $stats,
            'setsTablesOk' => $setsTablesOk,
            'sets' => $sets,
            'sets_q' => $setsQ,
            'sets_category_id' => $setsCategoryId,
            'sets_brand_id' => $setsBrandId,
            'categories' => $categories,
            'brands' => $brands,
        ]);

        render_page('admin/attributes/index', array_merge([
            'pageId' => 'admin-attributes',
            'rows'   => $rows,
            'q'      => $q,
            'stats'  => $stats,
            'setsTablesOk' => $setsTablesOk,
            'sets' => $sets,
            'sets_q' => $setsQ,
            'sets_category_id' => $setsCategoryId,
            'sets_brand_id' => $setsBrandId,
            'categories' => $categories,
            'brands' => $brands,
            'pageTitle' => 'Atributos',
        ], $vm));
    }

    public function form(): void
    {
        $db = db();
        $id = (int)($_GET['id'] ?? 0);

        $row = AttributesSchema::normalizeRow(['id'=>0,'name'=>'','slug'=>'','type'=>'select']);
        $values = [];

        if ($id > 0) {
            $selectColumns = implode(', ', AttributesSchema::editableColumns($db));
            $st = $db->prepare("SELECT {$selectColumns} FROM attributes WHERE id=? LIMIT 1");
            $st->execute([$id]);
            $row = AttributesSchema::normalizeRow($st->fetch(PDO::FETCH_ASSOC) ?: $row);

            $baseSql = "
                SELECT
                    av.id,
                    av.value,
                    av.sort,
                    0 AS is_active,
                    COALESCE(pav.cnt, 0) AS selected_in_products,
                    COALESCE(pvv.cnt, 0) AS used_in_variants
                FROM attribute_values av
                LEFT JOIN (
                    SELECT value_id, COUNT(*) AS cnt
                    FROM product_attribute_values
                    GROUP BY value_id
                ) pav ON pav.value_id = av.id
                LEFT JOIN (
                    SELECT value_id, COUNT(*) AS cnt
                    FROM product_variant_values
                    GROUP BY value_id
                ) pvv ON pvv.value_id = av.id
                WHERE av.attribute_id = ?
                ORDER BY av.sort ASC, av.value ASC, av.id ASC
            ";

            $sqlWithSets = "
                SELECT
                    av.id,
                    av.value,
                    av.sort,
                    0 AS is_active,
                    COALESCE(pav.cnt, 0) AS selected_in_products,
                    COALESCE(pvv.cnt, 0) AS used_in_variants,
                    COALESCE(vsi.cnt, 0) AS used_in_sets
                FROM attribute_values av
                LEFT JOIN (
                    SELECT value_id, COUNT(*) AS cnt
                    FROM product_attribute_values
                    GROUP BY value_id
                ) pav ON pav.value_id = av.id
                LEFT JOIN (
                    SELECT value_id, COUNT(*) AS cnt
                    FROM product_variant_values
                    GROUP BY value_id
                ) pvv ON pvv.value_id = av.id
                LEFT JOIN (
                    SELECT value_id, COUNT(*) AS cnt
                    FROM variant_value_set_items
                    GROUP BY value_id
                ) vsi ON vsi.value_id = av.id
                WHERE av.attribute_id = ?
                ORDER BY av.sort ASC, av.value ASC, av.id ASC
            ";

            try {
                $sv = $db->prepare($sqlWithSets);
                $sv->execute([$id]);
                $values = $sv->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Throwable $e) {
                $sv = $db->prepare($baseSql);
                $sv->execute([$id]);
                $values = $sv->fetchAll(PDO::FETCH_ASSOC) ?: [];
                foreach ($values as &$v) {
                    $v['used_in_sets'] = 0;
                }
                unset($v);
            }
        }

        $vm = (new AttributesFormViewModel())->build($row, $values);
        render_page('admin/attributes/form', array_merge([
            'pageId' => 'admin-attributes',
            'row'    => $row,
            'values' => $values,
            'pageTitle' => ($id ? 'Editar atributo' : 'Nuevo atributo'),
        ], $vm));
    }

    public function catalogJson(): void
    {
        $db = db();
        try {
            $selectColumns = implode(', ', AttributesSchema::editableColumns($db));
            $attrs = $db->query("SELECT {$selectColumns} FROM attributes ORDER BY name ASC")
                       ->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $values = $db->query('SELECT id, attribute_id, value, sort FROM attribute_values ORDER BY attribute_id ASC, sort ASC, value ASC')
                         ->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $byAttr = [];
            foreach ($values as $v) {
                $aid = (int)$v['attribute_id'];
                if (!isset($byAttr[$aid])) $byAttr[$aid] = [];
                $byAttr[$aid][] = [
                    'id'   => (int)$v['id'],
                    'value'=> (string)$v['value'],
                    'sort' => (int)($v['sort'] ?? 0),
                ];
            }

            $out = [];
            foreach ($attrs as $raw) {
                $a = AttributesSchema::normalizeRow($raw);
                $aid = (int)$a['id'];
                $out[] = [
                    'id'     => $aid,
                    'name'   => (string)$a['name'],
                    'code'   => (string)($a['code'] ?? ''),
                    'slug'   => (string)$a['slug'],
                    'type'   => (string)$a['type'],
                    'is_variant_axis' => !empty($a['is_variant_axis']),
                    'unit' => (string)($a['unit'] ?? ''),
                    'values' => $byAttr[$aid] ?? [],
                ];
            }

            $this->json(['ok'=>true, 'attributes'=>$out]);
        } catch (Throwable $e) {
            if (function_exists('log_error')) log_error('Admin\\AttributesController@catalogJson: '.$e->getMessage());
            $this->json(['ok'=>false, 'message'=>'No se pudo cargar el catálogo.'], 500);
        }
    }
}
