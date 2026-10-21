<?php
namespace App\Controllers\Admin\Concerns;

use App\Services\Audit\Audit;
use App\Services\Admin\Variants\VariantAttributesService;
use App\Support\Admin\AttributesSchema;
use Throwable;

trait AttributesWriteActions
{
    public function quick(): void
    {
        rate_limit_or_fail('admin:'.client_ip().':'.($_SESSION['uid']??0).':attr_quick', 20, 60);
        if (!verify_csrf()) { http_response_code(403); exit('CSRF'); }

        header('Content-Type: application/json; charset=utf-8');
        require_once base_path('app/Support/str.php');

        $db = db();
        $name = trim((string)($_POST['name'] ?? ''));
        $type = trim((string)($_POST['type'] ?? 'select'));
        $valuesJson = (string)($_POST['values'] ?? '');
        $isVariantAxis = 1;

        if ($name === '') { http_response_code(422); echo json_encode(['ok'=>false, 'message'=>'El nombre es obligatorio.']); return; }
        $allowed = ['select','text','number'];
        if (!in_array($type, $allowed, true)) { http_response_code(422); echo json_encode(['ok'=>false, 'message'=>'Tipo inválido.']); return; }

        $slug = str_slug($name);
        $values = [];
        if ($type === 'select' && $valuesJson !== '') {
            $decoded = json_decode($valuesJson, true);
            if (is_array($decoded)) {
                foreach ($decoded as $v) {
                    $v = trim((string)$v);
                    if ($v !== '') $values[] = $v;
                }
            } else {
                $parts = array_map('trim', explode(',', $valuesJson));
                foreach ($parts as $v) {
                    if ($v !== '') $values[] = $v;
                }
            }
            $values = array_values(array_unique($values));
        }

        try {
            $db->beginTransaction();
            $selectColumns = implode(', ', AttributesSchema::editableColumns($db));
            $st = $db->prepare("SELECT {$selectColumns} FROM attributes WHERE slug=? OR name=? LIMIT 1");
            $st->execute([$slug, $name]);
            $ex = $st->fetch(\PDO::FETCH_ASSOC);
            if ($ex) {
                $ex = AttributesSchema::normalizeRow($ex);
                $attrId = (int)$ex['id'];
                if ($type === 'select' && $values) {
                    $ins = $db->prepare("INSERT IGNORE INTO attribute_values (attribute_id, value, sort) VALUES (?,?,0)");
                    foreach ($values as $v) $ins->execute([$attrId, $v]);
                }
                $db->commit();
                echo json_encode([
                    'ok' => true,
                    'id' => $attrId,
                    'name' => (string)$ex['name'],
                    'code' => (string)($ex['code'] ?? ''),
                    'slug' => (string)$ex['slug'],
                    'type' => (string)$ex['type'],
                ]);
                return;
            }

            $columns = ['name', 'slug', 'type'];
            $placeholders = ['?', '?', '?'];
            $params = [$name, $slug, $type];

            if (AttributesSchema::hasColumn($db, 'attributes', 'is_variant_axis')) {
                $columns[] = 'is_variant_axis';
                $placeholders[] = '?';
                $params[] = $isVariantAxis;
            }

            $insAttr = $db->prepare("INSERT INTO attributes (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")");
            $insAttr->execute($params);
            $attrId = (int)$db->lastInsertId();
            if ($type === 'select' && $values) {
                $insVal = $db->prepare("INSERT INTO attribute_values (attribute_id, value, sort) VALUES (?,?,0)");
                foreach ($values as $v) $insVal->execute([$attrId, $v]);
            }
            $db->commit();

            echo json_encode(['ok' => true, 'id' => $attrId, 'name' => $name, 'slug' => $slug, 'type' => $type]);
            return;
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            if (function_exists('log_error')) log_error('Admin\\AttributesController@quick: '.$e->getMessage());
            http_response_code(500);
            echo json_encode(['ok'=>false, 'message'=>'No se pudo crear el atributo.']);
            return;
        }
    }

    public function save(): void
    {
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();

        $db = db();
        $id   = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $code = trim((string)($_POST['code'] ?? ''));
        $slug = trim((string)($_POST['slug'] ?? ''));
        $slug = $this->slugify($slug !== '' ? $slug : $name);
        $type = trim((string)($_POST['type'] ?? 'select'));
        $isVariantAxis = 1;
        $unit = trim((string)($_POST['unit'] ?? ''));

        if ($name === '') { flash('error', 'El nombre es obligatorio.'); redirect('/admin/attributes'.($id?('/edit?id='.$id):'/new')); return; }
        if ($slug === '') $slug = $this->slugify($name);
        if (!in_array($type, ['select','text','number'], true)) $type = 'select';
        if ($code !== '') $code = $this->slugify($code);

        $stU = $db->prepare("SELECT id FROM attributes WHERE slug = :s LIMIT 1");
        $stU->execute([':s' => $slug]);
        $existingId = (int)($stU->fetchColumn() ?: 0);
        if ($existingId && $existingId !== $id) {
            flash('error', 'Ya existe un atributo con ese slug. Cambialo y volve a intentar.');
            redirect('/admin/attributes'.($id?('/edit?id='.$id):'/new'));
            return;
        }
        if ($code !== '' && AttributesSchema::hasColumn($db, 'attributes', 'code')) {
            $stCode = $db->prepare("SELECT id FROM attributes WHERE code = :c LIMIT 1");
            $stCode->execute([':c' => $code]);
            $existingCodeId = (int)($stCode->fetchColumn() ?: 0);
            if ($existingCodeId && $existingCodeId !== $id) {
                flash('error', 'Ya existe un atributo con ese code. Cambialo y volve a intentar.');
                redirect('/admin/attributes'.($id?('/edit?id='.$id):'/new'));
                return;
            }
        }

        try {
            $existing = [];
            if ($id > 0) {
                $selectColumns = implode(', ', AttributesSchema::editableColumns($db));
                $stExisting = $db->prepare("SELECT {$selectColumns} FROM attributes WHERE id = :id LIMIT 1");
                $stExisting->execute([':id' => $id]);
                $existing = AttributesSchema::normalizeRow($stExisting->fetch(\PDO::FETCH_ASSOC) ?: []);
            }

            $data = [
                ':n' => $name,
                ':s' => $slug,
                ':t' => $type,
            ];
            $assignments = ['name=:n', 'slug=:s', 'type=:t'];
            $columns = ['name', 'slug', 'type'];
            $values = [':n', ':s', ':t'];

            if (AttributesSchema::hasColumn($db, 'attributes', 'code')) {
                $assignments[] = 'code=:c';
                $columns[] = 'code';
                $values[] = ':c';
                $data[':c'] = ($code !== '' ? $code : null);
            }
            if (AttributesSchema::hasColumn($db, 'attributes', 'is_variant_axis')) {
                $assignments[] = 'is_variant_axis=:iva';
                $columns[] = 'is_variant_axis';
                $values[] = ':iva';
                $data[':iva'] = $isVariantAxis;
            }
            if (AttributesSchema::hasColumn($db, 'attributes', 'unit')) {
                $assignments[] = 'unit=:u';
                $columns[] = 'unit';
                $values[] = ':u';
                $data[':u'] = ($unit !== '' ? $unit : null);
            }

            if ($id > 0) {
                $data[':id'] = $id;
                $st = $db->prepare("UPDATE attributes SET " . implode(', ', $assignments) . " WHERE id=:id");
                $st->execute($data);
                Audit::log('attribute_updated', 'attribute', $id, ['id'=>$id]);
                flash('ok', 'Atributo actualizado.');
            } else {
                $st = $db->prepare("INSERT INTO attributes (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ")");
                $st->execute($data);
                $newId = (int)$db->lastInsertId();
                Audit::log('attribute_created', 'attribute', $newId, ['id'=>$newId]);
                flash('ok', 'Atributo creado.');
            }
        } catch (Throwable $e) {
            if (function_exists('log_error')) log_error('Admin\\AttributesController@save: '.$e->getMessage());
            flash('error', 'No se pudo guardar el atributo.' . ($e->getCode() ? (' (' . $e->getCode() . ')') : ''));
        }

        redirect('/admin/attributes');
    }

    public function valueSave(): void
    {
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();

        $db = db();
        $attributeId = (int)($_POST['attribute_id'] ?? 0);
        $valueId     = (int)($_POST['value_id'] ?? 0);
        $value       = trim((string)($_POST['value'] ?? ''));
        $sort        = (int)($_POST['sort'] ?? 0);

        if ($attributeId <= 0) { flash('error', 'Atributo inválido.'); redirect('/admin/attributes'); return; }
        if ($value === '')     { flash('error', 'El valor no puede estar vacío.'); redirect('/admin/attributes/edit?id='.$attributeId); return; }

        try {
            if ($valueId > 0) {
                $st = $db->prepare("UPDATE attribute_values SET value=:v, sort=:s WHERE id=:id AND attribute_id=:aid");
                $st->execute([':v'=>$value, ':s'=>$sort, ':id'=>$valueId, ':aid'=>$attributeId]);
                Audit::log('attribute_value_updated', 'attribute_value', $valueId, ['id'=>$valueId, 'attribute_id'=>$attributeId]);
                flash('ok', 'Valor actualizado.');
            } else {
                $st = $db->prepare("INSERT INTO attribute_values (attribute_id, value, sort) VALUES (:aid,:v,:s)");
                $st->execute([':aid'=>$attributeId, ':v'=>$value, ':s'=>$sort]);
                $newId = (int)$db->lastInsertId();
                Audit::log('attribute_value_created', 'attribute_value', $newId, ['id'=>$newId, 'attribute_id'=>$attributeId]);
                flash('ok', 'Valor agregado.');
            }
        } catch (Throwable $e) {
            if (function_exists('log_error')) log_error('Admin\\AttributesController@valueSave: '.$e->getMessage());
            flash('error', 'No se pudo guardar el valor.');
        }

        redirect('/admin/attributes/edit?id='.$attributeId);
    }

    public function valueDelete(): void
    {
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();
        $attributeId = (int)($_POST['attribute_id'] ?? 0);
        $valueId     = (int)($_POST['value_id'] ?? ($_POST['id'] ?? 0));
        if ($valueId <= 0) { flash('error', 'Datos inválidos.'); redirect('/admin/attributes'); return; }

        try {
            $result = $this->deleteAttributeValueResult($valueId, $attributeId);
            $attributeId = (int)($result['attribute_id'] ?? $attributeId);

            if (!empty($result['ok'])) {
                Audit::log('attribute_value_deleted', 'attribute_value', $valueId, [
                    'id' => $valueId,
                    'attribute_id' => $attributeId,
                    'value' => (string)($result['value'] ?? ''),
                    'usage' => (array)($result['usage'] ?? []),
                ]);
                flash('ok', (string)($result['message'] ?? 'Valor eliminado.'));
            } else {
                $usage = (array)($result['usage'] ?? []);
                $suffix = '';
                if (!empty($usage)) {
                    $suffix = ' (productos: '.(int)($usage['selected_in_products'] ?? 0)
                        .', variantes: '.(int)($usage['used_in_variants'] ?? 0)
                        .', sets: '.(int)($usage['used_in_sets'] ?? 0).')';
                }
                if (function_exists('log_error')) {
                    log_error('Admin\\AttributesController@valueDelete failed: value_id=' . $valueId . ' attribute_id=' . $attributeId . ' code=' . (string)($result['code'] ?? 'UNKNOWN'));
                }
                Audit::log('attribute_value_delete_failed', 'attribute_value', $valueId, [
                    'id' => $valueId,
                    'attribute_id' => $attributeId,
                    'code' => (string)($result['code'] ?? 'UNKNOWN'),
                ]);
                flash('error', (string)($result['message'] ?? 'No se pudo eliminar el valor.') . $suffix);
            }
        } catch (Throwable $e) {
            if (function_exists('log_error')) log_error('Admin\\AttributesController@valueDelete: '.$e->getMessage());
            flash('error', 'No se pudo eliminar el valor.');
        }

        if ($attributeId > 0) {
            redirect('/admin/attributes/edit?id='.$attributeId);
            return;
        }
        redirect('/admin/attributes');
    }

    public function valueDeleteJson(): void
    {
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();
        $attributeId = (int)($_POST['attribute_id'] ?? 0);
        $valueId     = (int)($_POST['value_id'] ?? 0);
        if ($valueId <= 0) {
            $this->json(['ok'=>false,'message'=>'Datos inválidos.'], 400);
            return;
        }

        try {
            $result = $this->deleteAttributeValueResult($valueId, $attributeId);
            $status = 200;
            if (empty($result['ok'])) {
                $code = (string)($result['code'] ?? '');
                if ($code === 'NOT_FOUND') $status = 404;
                elseif ($code === 'INVALID' || $code === 'MISMATCH') $status = 400;
                elseif ($code === 'VALUE_IN_USE' || $code === 'VALUE_IN_SET') $status = 409;
                else $status = 500;

                if (function_exists('log_error')) {
                    log_error('Admin\\AttributesController@valueDeleteJson failed: value_id=' . $valueId . ' attribute_id=' . $attributeId . ' code=' . $code);
                }
                Audit::log('attribute_value_delete_failed', 'attribute_value', $valueId, [
                    'id' => $valueId,
                    'attribute_id' => (int)($result['attribute_id'] ?? $attributeId),
                    'code' => $code,
                ]);
            } else {
                Audit::log('attribute_value_deleted', 'attribute_value', $valueId, [
                    'id' => $valueId,
                    'attribute_id' => (int)($result['attribute_id'] ?? $attributeId),
                    'value' => (string)($result['value'] ?? ''),
                    'usage' => (array)($result['usage'] ?? []),
                ]);
            }

            $payload = [
                'ok' => (bool)($result['ok'] ?? false),
                'code' => (string)($result['code'] ?? ''),
                'message' => (string)($result['message'] ?? ''),
                'value' => (string)($result['value'] ?? ''),
                'usage' => array_merge([
                    'attribute_id' => (int)($result['attribute_id'] ?? $attributeId),
                    'value_id' => (int)($result['value_id'] ?? $valueId),
                ], (array)($result['usage'] ?? [])),
            ];
            $this->json($payload, $status);
        } catch (Throwable $e) {
            if (function_exists('log_error')) log_error('Admin\\AttributesController@valueDeleteJson: '.$e->getMessage());
            $this->json(['ok'=>false,'message'=>'No se pudo eliminar el valor.'], 500);
        }
    }

    private function deleteAttributeValueResult(int $valueId, int $attributeId): array
    {
        $svc = new VariantAttributesService(db());
        return $svc->deleteAttributeValue($valueId, $attributeId > 0 ? $attributeId : null);
    }

    private function slugify(string $s): string
    {
        $s = trim(mb_strtolower($s, 'UTF-8'));
        $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
        $s = preg_replace('/[^a-z0-9]+/i', '-', $s) ?? $s;
        $s = trim($s, '-');
        return $s ?: 'atributo';
    }
}

