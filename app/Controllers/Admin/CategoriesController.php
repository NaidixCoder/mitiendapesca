<?php
namespace App\Controllers\Admin;

use App\Services\Audit\Audit;
use App\Services\Admin\Categories\ViewModels\CategoriesFormViewModel;
use App\Services\Admin\Categories\CategoriesIndexService;
use App\Services\Admin\Categories\CategoryHierarchyGuard;
use App\Services\Catalog\CategoryAdminService;
use App\Services\Seo\CategorySeoBlocksService;
use App\Services\Seo\SeoAuditService;
use App\Services\Seo\SeoManagerService;
use App\Support\Catalog\CategoryLabel;
use PDO;
use Throwable;

class CategoriesController extends BaseController {
  public function index(): void {
    $data = (new CategoriesIndexService(db()))->search($_GET);
    render_page('admin/categories/index', $data);
  }

  public function form(): void {
    $db=db();
    $id=(int)($_GET['id']??0);
    $row=['id'=>0,'name'=>'','slug'=>'','parent_id'=>null,'meta_title'=>null,'meta_description'=>null,'seo_text'=>null,'cover_image'=>'','sort'=>0,'is_active'=>1];
    if ($id > 0) {
      $st = $db->prepare('SELECT * FROM categories WHERE id=?');
      $st->execute([$id]);
      $found = $st->fetch(PDO::FETCH_ASSOC);
      if (!$found) {
        flash('error', 'La categoría solicitada no existe.');
        redirect('/admin/categories');
        return;
      }
      $row = $found;
    }
    $parents = $db->query('SELECT id,name,slug,parent_id,is_active FROM categories ORDER BY name,id')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $unavailable = $id > 0 ? array_fill_keys((new CategoryHierarchyGuard($db))->descendantIds($id), true) : [];
    if ($id > 0) {
      $row['name'] = CategoryLabel::sanitize((string)($row['name'] ?? ''), (string)($row['slug'] ?? ''));
    }
    $parents = array_values(array_map(static function (array $parent) use ($id, $unavailable): array {
      $parent['name'] = CategoryLabel::sanitize((string)($parent['name'] ?? ''), (string)($parent['slug'] ?? ''), 'Categoría');
      $parent['selectable'] = (int)$parent['id'] !== $id && !isset($unavailable[(int)$parent['id']]);
      return $parent;
    }, $parents));
    $parentLookup = [];
    foreach ($parents as $item) $parentLookup[(int)$item['id']] = $item;
    foreach ($parents as &$item) {
      $parts = [$item['name']];
      $seen = [(int)$item['id'] => true];
      $ancestor = $item['parent_id'] === null ? 0 : (int)$item['parent_id'];
      while ($ancestor > 0 && isset($parentLookup[$ancestor]) && !isset($seen[$ancestor]) && count($parts) < 12) {
        $seen[$ancestor] = true;
        array_unshift($parts, $parentLookup[$ancestor]['name']);
        $ancestor = (int)($parentLookup[$ancestor]['parent_id'] ?? 0);
      }
      $item['label'] = implode(' › ', $parts);
    }
    unset($item);
    usort($parents, static fn(array $a, array $b): int => strcmp((string)$a['label'], (string)$b['label']));

    $seoAudit = (new SeoAuditService($db))->auditCategoryForAdmin($row);
    $seoSettings = ($id > 0) ? (new SeoManagerService($db))->getSettings('category', (int)$id) : [];
    $seoBlocks = ['bullets'=>[], 'specs'=>[], 'faq'=>[]];
    if ($id > 0) {
      try {
        $seoBlocks = (new CategorySeoBlocksService($db))->getForCategory((int)$id);
      } catch (Throwable $e) {
        if (function_exists('log_error')) log_error('Admin\\CategoriesController seoBlocks: '.$e->getMessage());
      }
    }

    $vm = (new CategoriesFormViewModel())->build(
      $row,
      is_array($parents) ? $parents : [],
      is_array($seoAudit ?? null) ? $seoAudit : [],
      is_array($seoSettings) ? $seoSettings : [],
      is_array($seoBlocks) ? $seoBlocks : ['bullets'=>[], 'specs'=>[], 'faq'=>[]]
    );

    render_page('admin/categories/form', array_merge([
      'pageId' => 'admin-category-form',
      'cat' => $row,
      'parents' => $parents,
      'seoAudit' => $seoAudit,
      'seoSettings' => $seoSettings,
      'seoBlocks' => $seoBlocks,
    ], $vm));
  }

  public function save(): void {
    rate_limit_or_fail('admin:'.client_ip().':'.($_SESSION['uid']??0).':cat_save', 20, 60);
    if (!verify_csrf()) { http_response_code(403); exit('CSRF'); }
    require_once base_path('app/Support/str.php');

    // Una petición manipulada no debe lanzar TypeError ni reinterpretar
    // arrays como IDs, slugs, rutas de portada o metadatos SEO.
    foreach ([
      'id','name','slug','parent_id','sort','sort_order','cover_image',
      'is_active','meta_title','meta_description','seo_text','seo_index',
      'seo_follow','seo_twitter_card','seo_canonical_override',
      'seo_og_title','seo_og_description','seo_og_image',
      'seo_schema_override_jsonld',
    ] as $field) {
      if (isset($_POST[$field]) && !is_scalar($_POST[$field])) {
        http_response_code(422);
        exit('Datos de categoría inválidos.');
      }
    }

    $db=db();
    $id=(int)($_POST['id']??0);
    $name=trim($_POST['name']??'');
    $slug=trim($_POST['slug']??'');
    $parentRaw = trim((string)($_POST['parent_id'] ?? ''));
    $parent = ($parentRaw === '' || $parentRaw === '0') ? null : (ctype_digit($parentRaw) ? (int)$parentRaw : -1);
    $sort=(int)($_POST['sort'] ?? ($_POST['sort_order'] ?? 0));
    $coverImage=trim((string)($_POST['cover_image'] ?? ''));
    $coverFile = $_FILES['cover_image_file'] ?? null;
    $active=(int)($_POST['is_active'] ?? 1);
    $active = $active ? 1 : 0;

    $metaTitle = trim((string)($_POST['meta_title'] ?? ''));
    $metaDesc  = trim((string)($_POST['meta_description'] ?? ''));
    $seoText   = trim((string)($_POST['seo_text'] ?? ''));
    $metaTitle = ($metaTitle === '') ? null : $metaTitle;
    $metaDesc  = ($metaDesc === '') ? null : $metaDesc;
    $seoText   = ($seoText === '') ? null : $seoText;

    if($slug==='' && $name!=='') $slug=str_slug($name);

    $errs = [];
    if ($name === '') $errs[] = 'Nombre requerido.';
    if ($slug === '') $errs[] = 'Slug requerido.';
    if ($name !== '' && CategoryLabel::isSuspicious($name)) $errs[] = 'Nombre de categoría inválido.';
    if (mb_strlen($name) > 120) $errs[] = 'El nombre admite hasta 120 caracteres.';
    if (mb_strlen($slug) > 140 || ($slug !== '' && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug))) {
      $errs[] = 'El slug debe usar letras minúsculas, números y guiones (máximo 140 caracteres).';
    }
    if (mb_strlen((string)$metaTitle) > 70) $errs[] = 'El título SEO supera los 70 caracteres.';
    if (mb_strlen((string)$metaDesc) > 160) $errs[] = 'La descripción SEO supera los 160 caracteres.';
    if (mb_strlen($coverImage) > 255) $errs[] = 'La ruta de imagen supera los 255 caracteres.';
    $hierarchyError = (new CategoryHierarchyGuard($db))->validateParent($id, $parent);
    if ($hierarchyError !== null) $errs[] = $hierarchyError;
    if ($id > 0) {
      $exists = $db->prepare('SELECT id FROM categories WHERE id=?');
      $exists->execute([$id]);
      if (!$exists->fetchColumn()) $errs[] = 'La categoría que intentás editar ya no existe.';
    }
    if (!$errs) {
      $st = $db->prepare('SELECT COUNT(*) FROM categories WHERE slug=? AND id<>?');
      $st->execute([$slug, $id]);
      if ((int)$st->fetchColumn() > 0) $errs[] = 'Ya existe una categoría con ese slug.';
    }

    if (!$errs && is_array($coverFile) && (int)($coverFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
      $upload = $this->storeCoverImage($coverFile, 'categories');
      if (!($upload['ok'] ?? false)) {
        $errs[] = (string)($upload['error'] ?? 'No se pudo subir la imagen de portada.');
      } else {
        $coverImage = (string)$upload['path'];
      }
    }

    if ($errs) {
      flash('error', implode(' · ', $errs));
      redirect($id ? '/admin/categories/edit?id='.$id : '/admin/categories/new');
      return;
    }


    // CategorySaveService controla una única transacción SQL, incluida SEO.
    // La portada permanece en staging privado hasta antes del commit.
    $operation = $id > 0 ? 'update' : 'insert';
    $staged = isset($upload) && ($upload['ok'] ?? false) ? $upload : null;
    $index = (($_POST['seo_index'] ?? '') === 'noindex') ? 'noindex' : 'index';
    $follow = (($_POST['seo_follow'] ?? '') === 'nofollow') ? 'nofollow' : 'follow';
    $twitter = trim((string)($_POST['seo_twitter_card'] ?? ''));
    if (!in_array($twitter, ['', 'summary', 'summary_large_image'], true)) $twitter = '';

    $bullets = $_POST['seo_bullets'] ?? [];
    if (!is_array($bullets)) $bullets = [];
    $labels = $_POST['seo_specs_label'] ?? [];
    $values = $_POST['seo_specs_value'] ?? [];
    if (!is_array($labels)) $labels = [];
    if (!is_array($values)) $values = [];
    $specs = [];
    for ($i = 0, $total = max(count($labels), count($values)); $i < $total; $i++) {
      $specs[] = ['label'=>$labels[$i] ?? '', 'value'=>$values[$i] ?? ''];
    }
    $questions = $_POST['seo_faq_q'] ?? [];
    $answers = $_POST['seo_faq_a'] ?? [];
    if (!is_array($questions)) $questions = [];
    if (!is_array($answers)) $answers = [];
    $faq = [];
    for ($i = 0, $total = max(count($questions), count($answers)); $i < $total; $i++) {
      $faq[] = ['q'=>$questions[$i] ?? '', 'a'=>$answers[$i] ?? ''];
    }
    try {
      $saved = (new \App\Services\Admin\Categories\CategorySaveService($db))->save(
        [
          'id'=>$id, 'name'=>$name, 'slug'=>$slug, 'parent_id'=>$parent,
          'meta_title'=>$metaTitle, 'meta_description'=>$metaDesc,
          'seo_text'=>$seoText, 'cover_image'=>$coverImage,
          'sort'=>$sort, 'is_active'=>$active,
        ],
        [
          'robots'=>$index.','.$follow,
          'canonical_override'=>trim((string)($_POST['seo_canonical_override'] ?? '')),
          'og_title'=>trim((string)($_POST['seo_og_title'] ?? '')),
          'og_description'=>trim((string)($_POST['seo_og_description'] ?? '')),
          'og_image'=>trim((string)($_POST['seo_og_image'] ?? '')),
          'twitter_card'=>$twitter,
          'schema_override_jsonld'=>trim((string)($_POST['seo_schema_override_jsonld'] ?? '')),
        ],
        ['bullets'=>$bullets, 'specs'=>$specs, 'faq'=>$faq],
        $staged
      );
    } catch (Throwable $e) {
      if ($staged !== null && is_string($staged['staged_path'] ?? null)
        && is_file($staged['staged_path'])) @unlink($staged['staged_path']);
      if (function_exists('log_error')) log_error('Admin\\CategoriesController@save atomic: '.$e->getMessage());
      flash('error', $e instanceof \DomainException
        ? $e->getMessage()
        : 'No se pudo completar el guardado de categoría y SEO. Se revirtieron los cambios.');
      redirect($operation === 'update'
        ? '/admin/categories/edit?id='.(int)($_POST['id'] ?? 0) : '/admin/categories/new');
      return;
    }

    $id = (int)$saved['id'];
    $visibilityResult = $saved['visibility'];
    \App\Services\Audit\Audit::log('category.save', 'category', $id, [
      'op'=>$saved['operation'], 'from'=>$saved['from'], 'to'=>$saved['to'],
      'cascade_ids'=>$visibilityResult['cascade_ids'] ?? [],
      'children_count'=>(int)($visibilityResult['children_count'] ?? 0),
      'products_count'=>(int)($visibilityResult['products_count'] ?? 0),
    ]);
    $success = $saved['operation'] === 'insert' ? 'Categoría creada.' : 'Categoría actualizada.';
    if (($visibilityResult['changed'] ?? false) && $active === 0
      && (int)($visibilityResult['children_count'] ?? 0) > 0) {
      $success .= ' También se desactivaron '.(int)$visibilityResult['children_count'].' subcategorías.';
    }
    flash('ok', $success);
    redirect('/admin/categories');
  }

  /**
   * Valida imagen y la mueve a staging PRIVADO fuera de public_html.
   * Retorna path público FUTURO y staged_path privado de este upload.
   * @param array<string,mixed> $file
   * @return array{ok:bool,path?:string,staged_path?:string,error?:string}
   */
  private function storeCoverImage(array $file, string $folder): array {
    $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err !== UPLOAD_ERR_OK) return ['ok'=>false,'error'=>'Error de subida de imagen.'];
    $bytes = (int)($file['size'] ?? 0);
    if ($bytes < 1 || $bytes > 4 * 1024 * 1024) return ['ok'=>false,'error'=>'La imagen debe pesar como máximo 4 MB.'];
    $tmp = (string)($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) return ['ok'=>false,'error'=>'Archivo temporal inválido.'];
    $info = @getimagesize($tmp);
    if (!is_array($info) || !isset($info[2])) return ['ok'=>false,'error'=>'El archivo debe ser una imagen válida.'];
    if ((int)$info[0] > 6000 || (int)$info[1] > 6000) {
      return ['ok'=>false,'error'=>'La imagen no debe superar los 6000 píxeles por lado.'];
    }
    $ext = match ((int)$info[2]) {
      IMAGETYPE_JPEG=>'jpg', IMAGETYPE_PNG=>'png', IMAGETYPE_WEBP=>'webp', default=>'',
    };
    if ($ext === '') return ['ok'=>false,'error'=>'Formato no soportado. Usa JPG, PNG o WEBP.'];

    // Carpeta fuera del webroot; mantener la identidad aleatoria del archivo.
    $stageDir = base_path('storage/category-cover-staging');
    if (!is_dir($stageDir) && !@mkdir($stageDir, 0700, true) && !is_dir($stageDir)) {
      return ['ok'=>false,'error'=>'No se pudo preparar la subida temporal.'];
    }
    $filename = bin2hex(random_bytes(16)).'.'.$ext;
    $stagePath = rtrim($stageDir, '/\\').DIRECTORY_SEPARATOR.$filename;
    if (!@move_uploaded_file($tmp, $stagePath)) {
      return ['ok'=>false,'error'=>'No se pudo preparar la portada.'];
    }
    return [
      'ok'=>true, 'path'=>'/uploads/'.$folder.'/'.$filename,
      'staged_path'=>$stagePath,
    ];
  }

  public function toggle(): void {
    rate_limit_or_fail('admin:'.client_ip().':'.($_SESSION['uid']??0).':cat_toggle', 20, 60);
    if (!verify_csrf()) { http_response_code(403); exit('CSRF'); }
    $id=(int)($_POST['id']??0); if($id<=0){ redirect('/admin/categories'); }

    $next = trim((string)($_POST['next'] ?? ''));
    if ($next === '') {
      $next = '/admin/categories';
    } else {
      $allowedAbsolute = rtrim(base_url(), '/') . '/admin/categories';
      $isRelative = preg_match('~^/admin/categories(?:\\?.*)?$~', $next) === 1;
      $isAbsolute = ($next === $allowedAbsolute || str_starts_with($next, $allowedAbsolute . '?'));
      if (!$isRelative && !$isAbsolute) $next = '/admin/categories';
    }
    $db = db();
    $svc = new CategoryAdminService($db);
    $res = $svc->toggleActiveCascade($id);

    if (!($res['ok'] ?? false)) {
      flash('error', $res['error'] ?? 'No se pudo actualizar.');
      redirect($next);
    }

    Audit::log('category.toggle','category',$id,[
      'from' => (int)$res['from'],
      'to' => (int)$res['to'],
      'cascade_ids' => $res['cascade_ids'] ?? [],
      'children_count' => (int)($res['children_count'] ?? 0),
      'products_count' => (int)($res['products_count'] ?? 0),
    ]);

    $toggleMessage = ((int)$res['to'] === 1) ? 'Categoría activada.' : 'Categoría desactivada.';
    if ((int)$res['to'] === 0 && (int)($res['children_count'] ?? 0) > 0) {
      $toggleMessage .= ' También se desactivaron '.(int)$res['children_count'].' subcategorías.';
    }
    flash('ok', $toggleMessage);
    if ((int)($res['to'] ?? 0) === 0) {
      $parts = [];
      if (!empty($res['products_count'])) $parts[] = 'Atencion: hay '.$res['products_count'].' productos asociados a esta categoria.';
      if ($parts) flash('warn', implode(' ', $parts));
    }
    redirect($next);
  }

  public function quick(): void {
    rate_limit_or_fail('admin:'.client_ip().':'.($_SESSION['uid']??0).':cat_quick', 15, 60);
    if (!verify_csrf()) { http_response_code(403); exit('CSRF'); }
    header('Content-Type: application/json; charset=utf-8');
    require_once base_path('app/Support/str.php');

    $reply = static function (int $status, array $payload): void {
      http_response_code($status);
      echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    };
    $name = trim((string)($_POST['name'] ?? ''));
    if ($name === '' || mb_strlen($name) > 120 || CategoryLabel::isSuspicious($name)) {
      $reply(422, ['ok'=>false,'code'=>'category_invalid_name','error'=>'Ingresá un nombre válido de hasta 120 caracteres.']);
      return;
    }
    $parentRaw = trim((string)($_POST['parent_id'] ?? ''));
    $parentId = null;
    if ($parentRaw !== '' && $parentRaw !== '0' && strtolower($parentRaw) !== 'null') {
      if (!ctype_digit($parentRaw) || (int)$parentRaw <= 0) {
        $reply(422, ['ok'=>false,'code'=>'category_invalid_parent','error'=>'Seleccioná una categoría superior válida.']);
        return;
      }
      $parentId = (int)$parentRaw;
    }
    $slug = str_slug($name);
    if ($slug === '' || mb_strlen($slug) > 140) {
      $reply(422, ['ok'=>false,'code'=>'category_invalid_slug','error'=>'El nombre genera una URL inválida o demasiado larga.']);
      return;
    }

    $db = db();
    $guard = new CategoryHierarchyGuard($db);
    $respondExisting = static function (array $existing) use ($parentId, $reply): void {
      $existingParent = $existing['parent_id'] === null ? null : (int)$existing['parent_id'];
      $details = [
        'id'=>(int)$existing['id'], 'name'=>(string)$existing['name'],
        'slug'=>(string)$existing['slug'], 'parent_id'=>$existingParent,
        'is_active'=>(int)$existing['is_active'],
      ];
      if ($existingParent !== $parentId) {
        $reply(409, [
          'ok'=>false, 'code'=>'category_slug_parent_conflict',
          'error'=>'Ya existe una categoría con ese nombre en otra ubicación. Elegí otro nombre o seleccioná la categoría existente.',
          'existing'=>$details,
        ]);
        return;
      }
      if ((int)$existing['is_active'] !== 1) {
        $reply(409, [
          'ok'=>false, 'code'=>'category_inactive_conflict',
          'error'=>'Esta categoría existe, pero está inactiva. Reactivala desde Categorías antes de utilizarla.',
          'existing'=>$details,
        ]);
        return;
      }
      $reply(200, [
        'ok'=>true, 'created'=>false, 'id'=>(int)$existing['id'],
        'name'=>(string)$existing['name'], 'slug'=>(string)$existing['slug'],
        'parent_id'=>$existingParent,
      ]);
    };

    // UNIQUE(slug) sigue siendo la última defensa. Si un INSERT compite con
    // otra solicitud, reintentar con snapshot nuevo y devolver 200/409/422.
    for ($attempt = 0; $attempt < 4; $attempt++) {
      try {
        $db->beginTransaction();
        // Mismo orden de locks del guard de Ventanas 1/2.
        $tree = $guard->snapshot(true);
        $parentError = $guard->validatePlacement(0, $parentId, 1, $tree);
        if ($parentError !== null) {
          $db->rollBack();
          $reply(422, ['ok'=>false,'code'=>'category_invalid_parent','error'=>$parentError]);
          return;
        }

        $check = $db->prepare('SELECT id,name,slug,parent_id,is_active FROM categories WHERE slug=? LIMIT 1');
        $check->execute([$slug]);
        $existing = $check->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
          $db->commit();
          $respondExisting($existing);
          return;
        }
        $insert = $db->prepare('INSERT INTO categories (name,slug,parent_id,is_active) VALUES (?,?,?,1)');
        $insert->execute([$name,$slug,$parentId]);
        $id = (int)$db->lastInsertId();
        $db->commit();
        $reply(200, [
          'ok'=>true, 'created'=>true, 'id'=>$id,
          'name'=>$name, 'slug'=>$slug, 'parent_id'=>$parentId,
        ]);
        return;
      } catch (\PDOException $e) {
        if ($db->inTransaction()) $db->rollBack();
        $mysqlCode = (int)($e->errorInfo[1] ?? 0);
        $sqlState = (string)$e->getCode();
        if (($mysqlCode === 1062 || $mysqlCode === 1213 || $mysqlCode === 1205 || $sqlState === '40001') && $attempt < 3) {
          usleep(30000 * ($attempt + 1));
          continue;
        }
        // Al agotar reintentos por UNIQUE, consultar al ganador y mantener
        // el contrato estable 200/409/422 en lugar de un error genérico.
        if ($mysqlCode === 1062) {
          try {
            $db->beginTransaction();
            $latestTree = $guard->snapshot(true);
            $latestError = $guard->validatePlacement(0, $parentId, 1, $latestTree);
            if ($latestError !== null) {
              $db->rollBack();
              $reply(422, ['ok'=>false,'code'=>'category_invalid_parent','error'=>$latestError]);
              return;
            }
            $latestStmt = $db->prepare('SELECT id,name,slug,parent_id,is_active FROM categories WHERE slug=? LIMIT 1');
            $latestStmt->execute([$slug]);
            $latest = $latestStmt->fetch(PDO::FETCH_ASSOC);
            if ($latest) {
              $db->commit();
              $respondExisting($latest);
              return;
            }
            $db->rollBack();
          } catch (Throwable $collisionError) {
            if ($db->inTransaction()) $db->rollBack();
            if (function_exists('log_error')) log_error('CategoriesController@quick collision: '.$collisionError->getMessage());
          }
        }
        if (function_exists('log_error')) log_error('CategoriesController@quick: '.$e->getMessage());
        $reply(503, ['ok'=>false,'code'=>'category_quick_retry','error'=>'La categoría se está actualizando. Volvé a intentar en unos segundos.']);
        return;
      } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        if (function_exists('log_error')) log_error('CategoriesController@quick: '.$e->getMessage());
        $reply(500, ['ok'=>false,'code'=>'category_quick_error','error'=>'No se pudo completar la operación. Volvé a intentar.']);
        return;
      }
    }
  }

}
