<?php
namespace App\Controllers\Admin;

use App\Repositories\Imports\ImportJobRepository;
use App\Repositories\Admin\AuditSearchRepository;
use App\Services\Import\ImportService;
use App\Services\Audit\Audit;
use App\Services\Admin\Imports\ImportsViewPresenterService;
use App\Services\Admin\Imports\ViewModels\ImportsIndexViewModel;
use App\Services\Admin\Imports\ViewModels\ImportsViewViewModel;

final class ImportsController extends BaseController
{
    public function index(): void
    {
        $db = db();
    // FIX535c: guard si products.supplier_id no existe aún (evita 500)
    $hasSupplierId = false;
    try {
        $stCol = $db->query("SHOW COLUMNS FROM products LIKE 'supplier_id'");
        $hasSupplierId = $stCol && $stCol->fetch(\PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {
        $hasSupplierId = false;
    }
        $repo = new ImportJobRepository($db);

        $rows = $repo->listRecent(10);
        $lastRevertibleId = self::computeLastRevertibleId($rows);
        $presenter = new ImportsViewPresenterService();
        $view = [
            'rows' => $rows,
            'lastRevertibleId' => $lastRevertibleId,
            'pageId' => 'admin-imports',
        ];
        $view = array_merge($view, $presenter->presentIndex($rows, $lastRevertibleId));
        $vm = (new ImportsIndexViewModel())->build($view);
        render_page('admin/imports/index', array_merge($view, $vm));
    }

    public function new(): void
    {
        // Ruta legacy: la vista se unificó en /admin/imports
        redirect('/admin/imports');
    }

    /**
     * FIX538: PREVIEW (dry-run) primero, siempre.
     * - Sube/valida archivo, corre dry-run, muestra un reporte fila-por-fila.
     * - No impacta DB.
     */
    public function preview(): void
    {
        verify_csrf_or_abort();

        $db = db();
        $service = ImportService::fromDb($db);
        $repo = new ImportJobRepository($db);

        [$dest, $name, $opt, $type] = self::resolveUploadAndOptionsFromRequest();
        if (!$dest || !$opt) return; // ya flasheó y redirigió

        $dry = $service->run($type, (string)$dest, true, $opt);
        $dryArr = $dry->toArray();
        $dryArr['options'] = $opt;

        // Guardamos detalle de preflight en _errors.json (sirve también para auditoría)
        $errFile = preg_replace('/\.csv$/','_errors.json',(string)$dest);
        @file_put_contents($errFile, json_encode($dryArr, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));

        // Gate de seguridad (altas masivas) + frase requerida
        $gate = self::needsPreflightConfirm($dry);
        $expected = !empty($gate['needs']) ? (string)($gate['phrase'] ?? '') : '';

        // Payload para APPLY
        $optClean = $opt;
        unset($optClean['job_id']);
        $payload = base64_encode(json_encode($optClean, JSON_UNESCAPED_UNICODE));

        // Preflight unificado (una sola UI): plan + detalles + gate
        $preflight = [
            'plan' => (array)($dry->plan ?? []),
            'needs' => (bool)($gate['needs'] ?? false),
            'phrase' => (string)($gate['phrase'] ?? ''),
            'reason' => (string)($gate['reason'] ?? ''),
            'details' => [
                'plan_details' => (array)($dry->plan_details ?? []),
                'gate_details' => (array)($gate['details'] ?? []),
            ],
        ];


        // Render preview en la misma pantalla
        $rows = $repo->listRecent(10);
        $presenter = new ImportsViewPresenterService();
        $view = [
            'pageId' => 'admin-imports',
            'rows' => $rows,
            'lastRevertibleId' => self::computeLastRevertibleId($rows),
            // Reusar la "Vista previa de seguridad" pulida (Marcas/Categorías/Proveedores)
            // para mantener consistencia visual y el gate anti-autocompletado.
            'preflight' => $preflight,
            'preview' => [
                'file' => (string)$dest,
                'original_name' => (string)$name,
                'result' => $dryArr,
                'gate' => $gate,
                'phrase' => $expected,
            ],
            'confirm' => [
                'existing_file' => (string)$dest,
                'opt_b64' => $payload,
            ],
        ];
        $view = array_merge($view, $presenter->presentIndex($rows, (int)$view['lastRevertibleId']));
        $vm = (new ImportsIndexViewModel())->build($view);
        render_page('admin/imports/index', array_merge($view, $vm));
    }

    /**
     * FIX538: APPLY (impacta DB)
     * - Requiere existing_file + opt_b64 (del preview)
     * - Vuelve a correr dry-run con job_id y luego aplica.
     */
    public function apply(): void
    {
        verify_csrf_or_abort();

        $db = db();
        $service = ImportService::fromDb($db);
        $repo = new ImportJobRepository($db);

        $existing = isset($_POST['existing_file']) ? (string)$_POST['existing_file'] : '';
        $optB64   = isset($_POST['opt_b64']) ? (string)$_POST['opt_b64'] : '';
        $confirmPhrase = trim((string)($_POST['confirm_phrase'] ?? ''));

        $realPath = self::validateExistingFile($existing);
        if (!$realPath) {
            flash('error','Archivo temporal inválido. Volvé a subir el CSV.');
            redirect('/admin/imports');
            return;
        }

        $decoded = json_decode(base64_decode($optB64), true);
        if (!is_array($decoded)) {
            flash('error','Opciones inválidas. Volvé a subir el CSV.');
            redirect('/admin/imports');
            return;
        }

        $opt = $decoded;
        $name = (string)($opt['_original_name'] ?? 'archivo.csv');
        $type = (string)($opt['_job_type'] ?? 'products');
        if (!in_array($type, ['products'], true)) $type = 'products';

        // Dry-run para gate/errores (sin job)
        $dry = $service->run($type, (string)$realPath, true, $opt);
        $gate = self::needsPreflightConfirm($dry);
        if (!empty($gate['needs'])) {
            $expected = (string)($gate['phrase'] ?? '');
            if ($confirmPhrase === '' || mb_strtoupper($confirmPhrase) !== mb_strtoupper($expected)) {
                flash('error','Frase de confirmación incorrecta.');
                redirect('/admin/imports');
                return;
            }
        }

        // I05: errores del primer preview también requieren detalle persistido.
        // No asumir que siempre se llamó antes a /preview.
        if ($dry->errors > 0) {
            $errFile = preg_replace('/\.csv$/','_errors.json',(string)$realPath);
            $dryDiagnostic = $dry->toArray();
            $dryDiagnostic['options'] = $opt;
            if (is_string($errFile) && $errFile !== '') {
                @file_put_contents($errFile, json_encode($dryDiagnostic, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), LOCK_EX);
            }
            $jobId = $repo->create($type, $name, (string)$realPath);
            if ($jobId > 0) {
                $repo->markDryFailed($jobId, (int)$dry->total, (int)$dry->ok, (int)$dry->errors);
                Audit::log('import_dry_failed','import_job',$jobId,[
                    'errors'=>(int)$dry->errors,
                    'total'=>(int)$dry->total,
                    'apply_outcome'=>\App\Services\Import\ImportResult::APPLY_NOT_STARTED,
                ]);
            }
            flash('error',"Se encontraron {$dry->errors} errores. No se aplicaron cambios.");
            redirect('/admin/imports/show?id=' . (int)($jobId ?: 0));
            return;
        }

        // Seguridad: si no leyó ninguna fila, no tiene sentido marcar finished.
        if ((int)$dry->total === 0) {
            flash('error','No se leyó ninguna fila del CSV. Revisá el delimitador y el formato (plantilla oficial).');
            redirect('/admin/imports');
            return;
        }

        // Creamos job y aplicamos
        $jobId = $repo->create($type, $name, (string)$realPath);
        if ($jobId <= 0) { flash('error','No se pudo crear el job'); redirect('/admin/imports'); return; }

        $opt['job_id'] = $jobId;

        // Re-dry con job_id para snapshot/rollback coherente
        $dry2 = $service->run($type, (string)$realPath, true, $opt);
        $errFile = preg_replace('/\.csv$/','_errors.json',(string)$realPath);
        $dryArr2 = $dry2->toArray();
        $dryArr2['options'] = $opt;
        @file_put_contents($errFile, json_encode($dryArr2, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));

        if ($dry2->errors > 0) {
            $repo->markDryFailed($jobId, (int)$dry2->total, (int)$dry2->ok, (int)$dry2->errors);
            Audit::log('import_dry_failed','import_job',$jobId,[
                'errors'=>(int)$dry2->errors,
                'total'=>(int)$dry2->total,
                'apply_outcome'=>\App\Services\Import\ImportResult::APPLY_NOT_STARTED,
            ]);
            flash('error',"Se encontraron {$dry2->errors} errores. No se aplicaron cambios.");
            redirect('/admin/imports/show?id='.$jobId);
            return;
        }

        $real = $service->run($type, (string)$realPath, false, $opt);
        $outcome = $real->applyOutcome;

        // I05 obligatorio: el motor cancela toda transacción con errores por fila.
        // El outcome SQL sigue siendo la única fuente para distinguir rollback
        // confirmado de confirmación incierta (sin inferirlo de errores).
        $diag = $real->toArray();
        $diag['options'] = $opt;
        foreach ($diag['errors'] as &$issue) {
            if ((int)($issue['row'] ?? -1) === 0) {
                $issue['error'] = 'La ejecución tuvo un incidente transaccional. Revisá el estado y la auditoría.';
            }
        }
        unset($issue);
        // _errors.json es diagnóstico, no el snapshot de recuperación v3.
        if (is_string($errFile) && $errFile !== '') {
            @file_put_contents($errFile, json_encode($diag, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        if ($outcome === \App\Services\Import\ImportResult::APPLY_ROLLED_BACK
            || $outcome === \App\Services\Import\ImportResult::APPLY_NOT_STARTED) {
            $repo->markFailed($jobId, 'Transacción no aplicada',
                (int)$real->total, (int)$real->ok, (int)$real->errors);
            Audit::log('import_apply_failed', 'import_job', $jobId, [
                'apply_outcome' => $outcome,
                'total' => (int)$real->total,
                'errors' => (int)$real->errors,
            ]);
            flash('error', 'No se aplicaron cambios. La importación falló y la transacción fue cancelada o no llegó a iniciarse.');
            redirect('/admin/imports/show?id='.$jobId);
            return;
        }
        if ($outcome !== \App\Services\Import\ImportResult::APPLY_COMMITTED) {
            $repo->markReviewRequired($jobId, (int)$real->total, (int)$real->ok, (int)$real->errors);
            Audit::log('import_commit_uncertain', 'import_job', $jobId, [
                'apply_outcome' => $outcome,
                'total' => (int)$real->total,
                'errors' => (int)$real->errors,
                'review_required' => true,
            ]);
            flash('error', 'Resultado de commit incierto: puede haber cambios aplicados. Requiere conciliación supervisada; no reviertas ni reintentes automáticamente.');
            redirect('/admin/imports/show?id='.$jobId);
            return;
        }
        // Defensa ante regresiones del motor: COMMITTED + errores viola I05.
        // Nunca declarar finished. No revertir un COMMIT ya confirmado:
        // preservar snapshot, bloquear acciones y conciliar supervisadamente.
        if ((int)$real->errors > 0) {
            $repo->markReviewRequired($jobId, (int)$real->total, (int)$real->ok, (int)$real->errors);
            Audit::log('import_inconsistent_commit', 'import_job', $jobId, [
                'apply_outcome' => $outcome,
                'errors' => (int)$real->errors,
                'review_required' => true,
            ]);
            flash('error', 'Resultado inconsistente: MySQL confirmó cambios pero hay errores por fila. Se suspenden las reversiones hasta conciliación supervisada.');
            redirect('/admin/imports/show?id='.$jobId);
            return;
        }
        $repo->markFinished($jobId, (int)$real->total, (int)$real->ok, (int)$real->errors);

        Audit::log('import_finished','import_job',$jobId,[
            'job_type' => $type,
            'file_name' => $name,
            'total'    => (int)$real->total,
            'ok'       => (int)$real->ok,
            'errors'   => (int)$real->errors,
        ]);

        flash('ok',"Importación completada: {$real->ok} filas. Ningún error por fila.");

        redirect('/admin/imports/show?id='.$jobId);
    }

    // Compatibilidad: algunos enlaces/vistas viejas siguen posteando a /admin/imports/create
    public function create(): void
    {
        $this->preview();
    }

    /**
     * @return array{0:?string,1:string,2:?array,3:string}
     */
    private static function resolveUploadAndOptionsFromRequest(): array
    {
        // Solo preview (primera pasada): siempre debe venir archivo
        $input = isset($_FILES['csv']) ? 'csv' : (isset($_FILES['file']) ? 'file' : null);
        if (!$input || !isset($_FILES[$input]) || ($_FILES[$input]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            flash('error','Subí un archivo CSV válido.');
            redirect('/admin/imports');
            return [null, 'archivo.csv', null, 'products'];
        }

        $file = $_FILES[$input];
        $name = (string)($file['name'] ?? 'archivo.csv');
        $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if ($ext !== 'csv') { flash('error','El archivo debe ser .csv'); redirect('/admin/imports'); return [null,$name,null,'products']; }
        if (((int)($file['size'] ?? 0)) > 10*1024*1024) { flash('error','Máximo 10MB.'); redirect('/admin/imports'); return [null,$name,null,'products']; }

        $destDir = base_path('storage/imports');
        @mkdir($destDir, 0775, true);
        if (!is_dir($destDir) || !is_writable($destDir)) {
            flash('error','Carpeta no escribible: storage/imports');
            redirect('/admin/imports');
            return [null,$name,null,'products'];
        }

        $rand = bin2hex(random_bytes(8));
        $dest = rtrim($destDir, '/\\') . '/upload_' . $rand . '.csv';
        if (!@move_uploaded_file($file['tmp_name'], $dest)) {
            if (!@copy($file['tmp_name'], $dest)) {
                flash('error','No se pudo guardar el archivo');
                redirect('/admin/imports');
                return [null,$name,null,'products'];
            }
        }

        $type = (string)($_POST['job_type'] ?? 'products');
        if (!in_array($type, ['products'], true)) $type = 'products';

        $opt = [
            'delimiter' => (string)($_POST['delimiter'] ?? 'auto'),
            // Catálogo auxiliar (configurable)
            'create_missing_catalog' => isset($_POST['create_missing_catalog']),
            'create_missing_brands' => isset($_POST['create_missing_brands']),
            'create_missing_categories' => isset($_POST['create_missing_categories']),
            'create_missing_suppliers' => isset($_POST['create_missing_suppliers']),
            'create_missing_products' => (isset($_POST['create_missing_products_present']) ? isset($_POST['create_missing_products']) : true),

            // Variantes
            'allow_parent_from_variants' => isset($_POST['allow_parent_from_variants']),

            // Updates
            'update_name' => isset($_POST['u_name']),
            'update_category' => isset($_POST['u_category']),
            'update_brand' => isset($_POST['u_brand']),
            'update_supplier' => isset($_POST['u_supplier']),
            'update_descriptions' => isset($_POST['u_desc']),
            'update_cost' => isset($_POST['u_cost']),
            'update_price' => isset($_POST['u_price']),
            'update_list_price' => isset($_POST['u_list_price']),
            'update_iva' => isset($_POST['u_iva']),
            'update_publish' => isset($_POST['u_publish']),
            'update_slug' => isset($_POST['u_slug']),
            'update_stock' => isset($_POST['u_stock']),
            'default_stock_mode' => isset($_POST['stock_mode_sum']) ? 'suma' : 'fija',
            'update_attributes' => isset($_POST['u_attrs']),
            'update_internal_code' => isset($_POST['u_internal_code']),
            'update_wholesale_overrides' => isset($_POST['u_wholesale_overrides']),
            'update_ship_dims' => isset($_POST['u_ship_dims']),

            // Derivación de precios
            'derive_prices' => isset($_POST['derive_prices']),
            'default_margin_pct' => (string)($_POST['default_margin_pct'] ?? ''),
            'default_iva_rate' => (string)($_POST['default_iva_rate'] ?? ''),
            'list_price_equals_price' => isset($_POST['list_price_equals_price']),

            '_original_name' => $name,
            '_job_type' => $type,
        ];

        return [$dest, $name, $opt, $type];
    }

    
    /**
     * Decide si la importación requiere confirmación explícita por riesgo (altas masivas de catálogo).
     * @param \App\Services\Import\ImportResult $dry
     * @return array{needs:bool,phrase:string,reason:string,details:array<string,mixed>}
     */
    private static function needsPreflightConfirm($dry): array
    {
        $plan = (array)($dry->plan ?? []);
        $brands = (int)($plan['create_brands'] ?? 0);
        $cats   = (int)($plan['create_categories'] ?? 0);
        $suppliers = (int)($plan['create_suppliers'] ?? 0);

        // Umbrales (conservadores)
        $brandLimit = 10;
        $catLimit   = 20;
        $supplierLimit = 15;

        $details = [
            'create_brands' => $brands,
            'create_categories' => $cats,
            'create_suppliers' => $suppliers,
            'brand_limit' => $brandLimit,
            'category_limit' => $catLimit,
            'supplier_limit' => $supplierLimit,
        ];

        $reasons = [];
        if ($brands > $brandLimit) $reasons[] = "Se crearían {$brands} marcas nuevas (límite {$brandLimit}).";
        if ($cats > $catLimit) $reasons[] = "Se crearían {$cats} categorías nuevas (límite {$catLimit}).";
        if ($suppliers > $supplierLimit) $reasons[] = "Se crearían {$suppliers} proveedores nuevos (límite {$supplierLimit}).";

        // Detector simple de autocompletado tipo "Nombre 10..600"
        $pattern = self::detectNumericSeries((array)($dry->plan_details['brands'] ?? []));
        if (!empty($pattern['hit'])) {
            $reasons[] = "Posible autocompletado de Excel: {$pattern['msg']}";
            $details['brand_series'] = $pattern;
        }

        $patternSup = self::detectNumericSeries((array)($dry->plan_details['suppliers'] ?? []));
        if (!empty($patternSup['hit'])) {
            $reasons[] = "Posible autocompletado de Excel en proveedores: {$patternSup['msg']}";
            $details['supplier_series'] = $patternSup;
        }

        $needs = !empty($reasons);
        $phrase = $needs ? ('CREAR ' . max($brands, $cats, $suppliers) . ' NUEVOS') : '';
        $reason = $needs ? implode(' ', $reasons) : '';

        return ['needs'=>$needs,'phrase'=>$phrase,'reason'=>$reason,'details'=>$details];
    }

    /**
     * @param array<int,string> $names
     * @return array{hit:bool,msg:string,groups?:array<int,array{prefix:string,count:int,min:int,max:int}>}
     */
    private static function detectNumericSeries(array $names): array
    {
        if (!$names) return ['hit'=>false,'msg'=>''];
        $groups = [];
        foreach ($names as $n) {
            $n = trim((string)$n);
            if ($n === '') continue;
            if (!preg_match('/^(.*?)(?:\s|\-)?(\d{1,5})$/u', $n, $m)) continue;
            $prefix = trim((string)$m[1]);
            $num = (int)$m[2];
            if ($prefix === '') continue;
            if (!isset($groups[$prefix])) $groups[$prefix] = ['prefix'=>$prefix,'count'=>0,'min'=>$num,'max'=>$num];
            $groups[$prefix]['count']++;
            $groups[$prefix]['min'] = min($groups[$prefix]['min'], $num);
            $groups[$prefix]['max'] = max($groups[$prefix]['max'], $num);
        }
        if (!$groups) return ['hit'=>false,'msg'=>''];
        usort($groups, fn($a,$b)=> ($b['count'] <=> $a['count']));
        $top = $groups[0];
        if (($top['count'] ?? 0) >= 25 && (($top['max']-$top['min']) >= 25)) {
            $msg = "Patrón '{$top['prefix']}': {$top['count']} valores (del {$top['min']} al {$top['max']}).";
            return ['hit'=>true,'msg'=>$msg,'groups'=>array_slice($groups,0,3)];
        }
        return ['hit'=>false,'msg'=>''];
    }

    /**
     * Seguridad: valida que el path sea un upload propio dentro de storage/imports.
     */
    private static function validateExistingFile(string $path): ?string
    {
        $path = trim($path);
        if ($path === '') return null;
        $base = realpath(base_path('storage/imports'));
        $real = realpath($path);
        if (!$base || !$real) return null;
        if (strpos($real, $base) !== 0) return null;
        if (!preg_match('/upload_[a-f0-9]{16}\.csv$/', basename($real))) return null;
        if (!is_file($real) || !is_readable($real)) return null;
        return $real;
    }

public function show(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $db = db();
    // FIX535c: guard si products.supplier_id no existe aún (evita 500)
    $hasSupplierId = false;
    try {
        $stCol = $db->query("SHOW COLUMNS FROM products LIKE 'supplier_id'");
        $hasSupplierId = $stCol && $stCol->fetch(\PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {
        $hasSupplierId = false;
    }
        $repo = new ImportJobRepository($db);

        $job = $repo->find($id);
        if (!$job) { abort(404); return; }

        // Actor (admin) que ejecutó la importación (auditoría)
        $actorName = null;
        try {
            $auditRepo = new AuditSearchRepository($db);
            // El job puede ser fallido, incierto o revertido, no solo finished.
            // Atribuir al operador únicamente si existe registro auténtico.
            foreach (['import_finished','import_apply_failed','import_dry_failed',
                      'import_commit_uncertain','import_inconsistent_commit'] as $event) {
                $actorName = $auditRepo->findLatestActorNameForEntity('import_job', $id, $event);
                if ($actorName !== null) break;
            }
        } catch (\Throwable $e) {
            $actorName = null;
        }

        $summary = null;
        $errFile = preg_replace('/\.csv$/','_errors.json',(string)($job['file_path'] ?? ''));
        if ($errFile && is_file($errFile)) {
            $json = @file_get_contents($errFile);
            if ($json) $summary = json_decode($json, true);
        }

        // Un archivo de Preview puede sobrevivir a un error de escritura del
        // diagnóstico final. No mostrarlo como si probara un COMMIT real.
        $status = (string)($job['status'] ?? '');
        $diagnosticOutcome = is_array($summary) ? (string)($summary['apply_outcome'] ?? '') : '';
        if (in_array($status, ['finished', 'ok'], true)
            && $diagnosticOutcome !== \App\Services\Import\ImportResult::APPLY_COMMITTED) {
            $summary = null;
        } elseif ($status === 'dry-failed'
            && $diagnosticOutcome !== \App\Services\Import\ImportResult::APPLY_NOT_STARTED) {
            $summary = null;
        } elseif ($status === 'failed' && !in_array($diagnosticOutcome, [
            \App\Services\Import\ImportResult::APPLY_ROLLED_BACK,
            \App\Services\Import\ImportResult::APPLY_NOT_STARTED,
        ], true)) {
            $summary = null;
        }

        $lastRevertibleId = self::computeLastRevertibleId($repo->listRecent(50));
        $presenter = new ImportsViewPresenterService();
        $view = [
            'pageId' => 'admin-imports',
            'job' => $job,
            'summary' => $summary,
            'actor_name' => $actorName,
            'lastRevertibleId' => $lastRevertibleId,
        ];
        $view = array_merge($view, $presenter->presentShow($job, is_array($summary) ? $summary : null, $lastRevertibleId));
        $vm = (new ImportsViewViewModel())->build($view);
        render_page('admin/imports/view', array_merge($view, $vm));
    }

    /**
     * Solo el último import revertible (LIFO).
     * Criterio: status OK + revert_status not_reverted + rollback v2 existe.
     * @param array<int,array<string,mixed>> $rows
     */
    private static function computeLastRevertibleId(array $rows): int
    {
        foreach ($rows as $r) {
            $id = (int)($r['id'] ?? 0);
            $st = (string)($r['status'] ?? '');
            $rs = (string)($r['revert_status'] ?? 'not_reverted');
            if ($id <= 0) continue;
            if (!in_array($st, ['finished','ok'], true)) continue;
            if ($rs !== 'not_reverted') continue;

            $csvPath = (string)($r['file_path'] ?? '');
            if ($csvPath !== '' && is_file($csvPath)) {
                $csvAbs = $csvPath;
            } else {
                $csvAbs = storage_path($csvPath);
            }
            $rbAbs = $csvAbs ? preg_replace('/\.csv$/','_rollback.json',$csvAbs) : null;
            if (!$rbAbs || !is_file($rbAbs)) continue;
            $raw = @file_get_contents($rbAbs);
            $j = $raw ? json_decode($raw, true) : null;
            $ver = is_array($j) ? (int)($j['version'] ?? 0) : 0;
            if ($ver !== 2) continue;

            return $id;
        }

        return 0;
    }


    public function template(): void
    {
        // Descarga de plantilla CSV (headers v3) — UTF-8 con BOM para Excel
        // Seguridad: solo admins (este controller ya está bajo /admin)
        $delimiter = ';';
        // Orden oficial (import/export) por bloques: identidad → básicos → fiscal/moneda → precios → mayoristas → stock → descripciones → atributos → envíos
        $headers = [
            'tipo_fila',
            'sku',
            'sku_padre',
            'internal_code',
            'nombre',
            'categoria',
            'marca',
            'proveedor',
            'proveedor_cuit',
            'iva_rate',
            'moneda',
            'costo',
            'margen_pct',
            'precio',
            'precio_lista',
            'precio_mayorista1',
            'precio_mayorista2',
            'stock',
            'umbral',
            'publicado',
            'descripcion',
            'descripcion_larga',
            'attr_1',
            'valor_1',
            'attr_2',
            'valor_2',
            'attr_3',
            'valor_3',
            'peso_envio_g',
            'alto_envio_cm',
            'ancho_envio_cm',
            'largo_envio_cm'
        ];

        $filename = 'plantilla_importacion_oficial.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // BOM UTF-8 (mejor compatibilidad con Excel en Windows para Ñ/acentos)
        echo "\xEF\xBB\xBF";
        echo implode($delimiter, $headers) . "\n";
        exit;
    }


public function export(): void
{
    // Exporta el catálogo actual en el formato CSV oficial para edición en Excel y re-importación.
    $db = db();
    // FIX535c: guard si products.supplier_id no existe aún (evita 500)
    $hasSupplierId = false;
    try {
        $stCol = $db->query("SHOW COLUMNS FROM products LIKE 'supplier_id'");
        $hasSupplierId = $stCol && $stCol->fetch(\PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {
        $hasSupplierId = false;
    }
    $delimiter = ';';

    // Debe matchear EXACTO el orden de template() para que el export sea re-importable sin reordenar columnas.
    $headers = [
        'tipo_fila','sku','sku_padre','internal_code',
        'nombre','categoria','marca',
        'proveedor','proveedor_cuit',
        'iva_rate','moneda',
        'costo','margen_pct','precio','precio_lista',
        'precio_mayorista1','precio_mayorista2',
        'stock','umbral','publicado',
        'descripcion','descripcion_larga',
        'attr_1','valor_1','attr_2','valor_2','attr_3','valor_3',
        'peso_envio_g','alto_envio_cm','ancho_envio_cm','largo_envio_cm'
    ];

    $filename = 'export_productos_' . date('Ymd_His') . '.csv';

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // BOM UTF-8 para Excel Windows (Ñ/acentos)
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');
    if (!$out) { exit; }

    fputcsv($out, $headers, $delimiter);

    $sql = "
        SELECT
            p.id, p.sale_mode, p.sku, p.internal_code, p.name,
            p.iva_rate, p.cost, p.price, p.list_price,
            p.short_desc, p.long_desc, p.is_active,
            curp.code AS cost_currency_code,
            b.name AS brand_name,
            c.name AS category_name,";
    if ($hasSupplierId) {
        $sql .= "\n            s.nombre AS supplier_name,
            s.cuit AS supplier_cuit,";
    } else {
        $sql .= "\n            '' AS supplier_name,";
    }
    $sql .= "
            i.stock AS stock_simple,
            i.low_stock_threshold AS thr_simple,
            p.ship_weight, p.ship_height, p.ship_width, p.ship_length
        FROM products p
        LEFT JOIN brands b ON b.id = p.brand_id
        LEFT JOIN categories c ON c.id = p.category_id";
    if ($hasSupplierId) {
        $sql .= "\n        LEFT JOIN suppliers s ON s.id = p.supplier_id";
    }
    $sql .= "
        LEFT JOIN currencies curp ON curp.id = p.cost_currency_id
        LEFT JOIN inventory i ON i.product_id = p.id
        ORDER BY p.id ASC
    ";

    $stmt = $db->query($sql);
    $products = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];

    $stmtVars = $db->prepare("
        SELECT
            v.id, v.product_id, v.sku, v.internal_code,
            v.cost, v.price, v.list_price, v.is_active,
            v.ship_weight, v.ship_height, v.ship_width, v.ship_length,
            curv.code AS cost_currency_code,
            iv.stock, iv.low_stock_threshold
        FROM product_variants v
        LEFT JOIN currencies curv ON curv.id = v.cost_currency_id
        LEFT JOIN inventory_variants iv ON iv.variant_id = v.id
        WHERE v.product_id = ?
        ORDER BY v.id ASC
    ");

    $stmtAttrs = $db->prepare("
        SELECT a.name AS attr_name, av.value AS attr_value
        FROM product_variant_values pvv
        JOIN attributes a ON a.id = pvv.attribute_id
        JOIN attribute_values av ON av.id = pvv.value_id
        WHERE pvv.variant_id = ?
        ORDER BY pvv.attribute_id ASC
    ");

    foreach ($products as $p) {
        $sku_padre = (string)($p['sku'] ?? '');
        $pub = ((int)($p['is_active'] ?? 0) === 1) ? 'si' : 'no';

        $row = [
            'tipo_fila' => 'producto',
            'sku' => (string)($p['sku'] ?? ''),
            'sku_padre' => '',
            'internal_code' => (string)($p['internal_code'] ?? ''),
            'nombre' => (string)($p['name'] ?? ''),
            'categoria' => (string)($p['category_name'] ?? ''),
            'marca' => (string)($p['brand_name'] ?? ''),
            'proveedor' => (string)($p['supplier_name'] ?? ''),
            'proveedor_cuit' => (string)($p['supplier_cuit'] ?? ''),
            'iva_rate' => ($p['iva_rate'] !== null) ? (string)$p['iva_rate'] : '',
            'costo' => ($p['cost'] !== null) ? (string)$p['cost'] : '',
            'moneda' => (string)($p['cost_currency_code'] ?? 'ARS'),
            'margen_pct' => '',
            'precio' => ($p['price'] !== null) ? (string)$p['price'] : '',
            'precio_lista' => ($p['list_price'] !== null) ? (string)$p['list_price'] : '',
            'stock' => ($p['stock_simple'] !== null) ? (string)$p['stock_simple'] : '',
            'umbral' => ($p['thr_simple'] !== null) ? (string)$p['thr_simple'] : '',
            'publicado' => $pub,
            'descripcion' => (string)($p['short_desc'] ?? ''),
            'descripcion_larga' => (string)($p['long_desc'] ?? ''),
            'attr_1' => '', 'valor_1' => '',
            'attr_2' => '', 'valor_2' => '',
            'attr_3' => '', 'valor_3' => '',
            'peso_envio_g' => (($p['ship_weight'] ?? null) !== null) ? (string)$p['ship_weight'] : '',
            'alto_envio_cm' => (($p['ship_height'] ?? null) !== null) ? (string)$p['ship_height'] : '',
            'ancho_envio_cm'  => (($p['ship_width'] ?? null) !== null) ? (string)$p['ship_width'] : '',
            'largo_envio_cm' => (($p['ship_length'] ?? null) !== null) ? (string)$p['ship_length'] : '',
        ];
        $ordered = [];
        foreach ($headers as $h) { $ordered[] = $row[$h] ?? ''; }
        fputcsv($out, $ordered, $delimiter);

        if (($p['sale_mode'] ?? '') === 'variants') {
            $parent_key = $sku_padre !== '' ? $sku_padre : (string)($p['internal_code'] ?? '');
            $stmtVars->execute([$p['id']]);
            $vars = $stmtVars->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($vars as $v) {
                $vpub = ((int)($v['is_active'] ?? 0) === 1) ? 'si' : 'no';

                $stmtAttrs->execute([$v['id']]);
                $pairs = $stmtAttrs->fetchAll(\PDO::FETCH_ASSOC);

                $a = [['',''],['',''],['','']];
                $i = 0;
                foreach ($pairs as $pair) {
                    if ($i >= 3) break;
                    $a[$i] = [(string)($pair['attr_name'] ?? ''), (string)($pair['attr_value'] ?? '')];
                    $i++;
                }

                $rowv = [
                    'tipo_fila' => 'variante',
                    'sku' => (string)($v['sku'] ?? ''),
                    'sku_padre' => $parent_key,
                    'internal_code' => (string)($v['internal_code'] ?? ''),
                    'nombre' => (string)($p['name'] ?? ''),
                    'categoria' => (string)($p['category_name'] ?? ''),
                    'marca' => (string)($p['brand_name'] ?? ''),
            'proveedor' => (string)($p['supplier_name'] ?? ''),
            'proveedor_cuit' => (string)($p['supplier_cuit'] ?? ''),
                    'iva_rate' => ($p['iva_rate'] !== null) ? (string)$p['iva_rate'] : '',
                    'costo' => ($v['cost'] !== null) ? (string)$v['cost'] : '',
                    'moneda' => (string)($v['cost_currency_code'] ?? ($p['cost_currency_code'] ?? 'ARS')),
                    'margen_pct' => '',
                    'precio' => ($v['price'] !== null) ? (string)$v['price'] : '',
                    'precio_lista' => ($v['list_price'] !== null) ? (string)$v['list_price'] : '',
                    'stock' => ($v['stock'] !== null) ? (string)$v['stock'] : '',
                    'umbral' => ($v['low_stock_threshold'] !== null) ? (string)$v['low_stock_threshold'] : '',
                    'publicado' => $vpub,
                    'descripcion' => (string)($p['short_desc'] ?? ''),
                    'descripcion_larga' => (string)($p['long_desc'] ?? ''),
                    'attr_1' => $a[0][0], 'valor_1' => $a[0][1],
                    'attr_2' => $a[1][0], 'valor_2' => $a[1][1],
                    'attr_3' => $a[2][0], 'valor_3' => $a[2][1],
                    'peso_envio_g' => ($v['ship_weight'] !== null) ? (string)$v['ship_weight'] : '',
                    'alto_envio_cm' => ($v['ship_height'] !== null) ? (string)$v['ship_height'] : '',
                    'ancho_envio_cm'  => ($v['ship_width'] !== null) ? (string)$v['ship_width'] : '',
                    'largo_envio_cm' => ($v['ship_length'] !== null) ? (string)$v['ship_length'] : '',
                ];
                $ordered = [];
                foreach ($headers as $h) { $ordered[] = $rowv[$h] ?? ''; }
                fputcsv($out, $ordered, $delimiter);
            }
        }
    }

    fclose($out);
    exit;
}



    public function process(): void
    {
        // Por ahora, el flujo es: crear => dry-run => aplicar.
        // Esta ruta se mantiene solo para compatibilidad con posibles enlaces viejos.
        redirect('/admin/imports');
    }


public function rollback(): void
{
    require_admin();

    $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    if ($id <= 0) { flash('error','Importación inválida.'); redirect('/admin/imports'); return; }

    if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();

    $db = db();
    $svc = \App\Services\Import\ImportRevertService::fromDb($db);

    $userId = (int)($_SESSION['user_id'] ?? 0);
    $r = $svc->revertJob($id, $userId);

    if (!$r['ok']) {
        flash('error', $r['error'] ?? 'No se pudo revertir la importación.');
        redirect('/admin/imports/show?id=' . $id);
        return;
    }

    $summary = $r['summary'] ?? null;
    if (is_array($summary) && !empty($summary['already_reverted'])) {
        flash('ok','Esta importación ya fue revertida.');
    } else {
        $hasWarnings = is_array($summary) && !empty($summary['warnings']);
        flash('ok', $hasWarnings ? 'Importación revertida (con advertencias).' : 'Importación revertida.');
    }

    redirect('/admin/imports/show?id=' . $id);
}

}
