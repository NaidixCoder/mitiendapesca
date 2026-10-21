<?php
namespace App\Controllers\Admin;

use App\Services\Audit\Audit;
use App\Services\Admin\Categories\ViewModels\CategoriesFormViewModel;
use App\Services\Catalog\CategoryAdminService;
use App\Services\Seo\CategorySeoBlocksService;
use App\Services\Seo\SeoAuditService;
use App\Services\Seo\SeoManagerService;
use App\Support\Catalog\CategoryLabel;
use PDO;
use Throwable;

class CategoriesController extends BaseController {
  public function index(): void {
    $db = db();
    $q = trim((string)($_GET['q'] ?? ''));
    $showInactive = (int)($_GET['show_inactive'] ?? 0) === 1;

    $where = [];
    $args = [];
    if (!$showInactive) {
      $where[] = 'c.is_active = 1';
    }
    if ($q !== '') {
      $where[] = '(c.name LIKE ? OR c.slug LIKE ?)';
      $like = '%' . $q . '%';
      $args[] = $like;
      $args[] = $like;
    }
    $sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $st = $db->prepare("
      SELECT c.id,c.name,c.slug,c.parent_id,p.name AS parent_name,c.sort,c.is_active,c.cover_image
      FROM categories c
      LEFT JOIN categories p ON p.id=c.parent_id
      $sqlWhere
      ORDER BY COALESCE(p.name,''), c.sort, c.name
    ");
    $st->execute($args);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    render_page('admin/categories/index', [
      'rows' => $rows,
      'q' => $q,
      'showInactive' => $showInactive,
    ]);
  }

  public function form(): void {
    $db=db();
    $id=(int)($_GET['id']??0);
    $row=['id'=>0,'name'=>'','slug'=>'','parent_id'=>null,'meta_title'=>null,'meta_description'=>null,'seo_text'=>null,'cover_image'=>'','sort'=>0,'is_active'=>1];
    if($id>0){ $st=$db->prepare("SELECT * FROM categories WHERE id=?"); $st->execute([$id]); $row=$st->fetch(PDO::FETCH_ASSOC)?:$row; }
    $parents=$db->query("SELECT id,name FROM categories WHERE id<>$id ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $row['name'] = CategoryLabel::sanitize((string)($row['name'] ?? ''), (string)($row['slug'] ?? ''));
    $parents = array_map(static function (array $parent): array {
      $parent['name'] = CategoryLabel::sanitize((string)($parent['name'] ?? ''), (string)($parent['slug'] ?? ''), 'Categoria');
      return $parent;
    }, is_array($parents) ? $parents : []);

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
    header('Content-Type: application/json; charset=utf-8');
    require_once base_path('app/Support/str.php');

    $db=db();
    $id=(int)($_POST['id']??0);
    $name=trim($_POST['name']??'');
    $slug=trim($_POST['slug']??'');
    $parentRaw = trim((string)($_POST['parent_id'] ?? ''));
    $parent = ($parentRaw === '' || $parentRaw === '0') ? null : (int)$parentRaw;
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

    $errs=[]; if($name==='')$errs[]='Nombre requerido'; if($slug==='')$errs[]='Slug requerido';
    if ($name !== '' && CategoryLabel::isSuspicious($name)) {
      $errs[] = 'Nombre de categoria invalido.';
    }
    if($id>0 && $parent!==null && $parent===$id){ $errs[]='La categoria no puede ser su propio padre.'; }
    if(!$errs){ $st=$db->prepare("SELECT COUNT(*) FROM categories WHERE slug=? AND id<>?"); $st->execute([$slug,$id]); if((int)$st->fetchColumn()>0)$errs[]='Slug ya existe'; }

    if (is_array($coverFile) && (int)($coverFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
      $upload = $this->storeCoverImage($coverFile, 'categories');
      if (!($upload['ok'] ?? false)) {
        $errs[] = (string)($upload['error'] ?? 'No se pudo subir la imagen de portada.');
      } else {
        $coverImage = (string)$upload['path'];
      }
    }

    if($errs){ flash('error',implode(' · ',$errs)); redirect($id?'/admin/categories/edit?id='.$id:'/admin/categories/new'); }

    $prevSlug = '';
    if ($id > 0) {
      try {
        $prevSlug = (string)$db->query("SELECT slug FROM categories WHERE id={$id}")->fetchColumn();
      } catch (Throwable $e) {
        $prevSlug = '';
      }
    }

    if($id>0){
      $ok=$db->prepare("UPDATE categories SET name=?,slug=?,parent_id=?,meta_title=?,meta_description=?,seo_text=?,cover_image=?,sort=?,is_active=? WHERE id=?")
        ->execute([$name,$slug,$parent,$metaTitle,$metaDesc,$seoText,$coverImage,$sort,$active,$id]);
      $ok? (Audit::log('category.save','category',$id,['op'=>'update']) || true) : flash('error','No se pudo actualizar.');
      if($ok) flash('ok','Categoria actualizada.');
    } else {
      $ok=$db->prepare("INSERT INTO categories (name,slug,parent_id,meta_title,meta_description,seo_text,cover_image,sort,is_active) VALUES (?,?,?,?,?,?,?,?,?)")
        ->execute([$name,$slug,$parent,$metaTitle,$metaDesc,$seoText,$coverImage,$sort,$active]);
      if($ok){ $id=(int)$db->lastInsertId(); Audit::log('category.save','category',$id,['op'=>'insert']); flash('ok','Categoria creada.'); }
      else { flash('error','No se pudo crear.'); }
    }

    if ($ok && $id > 0) {
      try {
        $seoMgr = new SeoManagerService($db);
        if ($slug !== '') {
          $seoMgr->registerSlug('category', (int)$id, $slug);
        }

        if ($prevSlug !== '' && $prevSlug !== $slug) {
          $seoMgr->recordCategorySlugChange((int)$id, $prevSlug, $slug);
        }

        $index = (($_POST['seo_index'] ?? '') === 'noindex') ? 'noindex' : 'index';
        $follow = (($_POST['seo_follow'] ?? '') === 'nofollow') ? 'nofollow' : 'follow';
        $robots = $index . ',' . $follow;

        $twitter = trim((string)($_POST['seo_twitter_card'] ?? ''));
        if (!in_array($twitter, ['', 'summary', 'summary_large_image'], true)) {
          $twitter = '';
        }

        $seoMgr->saveSettings('category', (int)$id, [
          'robots' => $robots,
          'canonical_override' => trim((string)($_POST['seo_canonical_override'] ?? '')),
          'og_title' => trim((string)($_POST['seo_og_title'] ?? '')),
          'og_description' => trim((string)($_POST['seo_og_description'] ?? '')),
          'og_image' => trim((string)($_POST['seo_og_image'] ?? '')),
          'twitter_card' => $twitter,
          'schema_override_jsonld' => trim((string)($_POST['seo_schema_override_jsonld'] ?? '')),
        ]);

        $bullets = $_POST['seo_bullets'] ?? [];
        if (!is_array($bullets)) $bullets = [];

        $specLabels = $_POST['seo_specs_label'] ?? [];
        $specValues = $_POST['seo_specs_value'] ?? [];
        if (!is_array($specLabels)) $specLabels = [];
        if (!is_array($specValues)) $specValues = [];
        $specs = [];
        $specCount = max(count($specLabels), count($specValues));
        for ($i = 0; $i < $specCount; $i++) {
          $specs[] = ['label' => ($specLabels[$i] ?? ''), 'value' => ($specValues[$i] ?? '')];
        }

        $faqQ = $_POST['seo_faq_q'] ?? [];
        $faqA = $_POST['seo_faq_a'] ?? [];
        if (!is_array($faqQ)) $faqQ = [];
        if (!is_array($faqA)) $faqA = [];
        $faq = [];
        $faqCount = max(count($faqQ), count($faqA));
        for ($i = 0; $i < $faqCount; $i++) {
          $faq[] = ['q' => ($faqQ[$i] ?? ''), 'a' => ($faqA[$i] ?? '')];
        }

        (new CategorySeoBlocksService($db))->saveForCategory((int)$id, $bullets, $specs, $faq);
      } catch (Throwable $e) {
        if (function_exists('log_error')) log_error('Admin\\CategoriesController@save SEO: '.$e->getMessage());
      }
    }
    redirect('/admin/categories');
  }

  /**
   * @param array<string,mixed> $file
   * @return array{ok:bool,path?:string,error?:string}
   */
  private function storeCoverImage(array $file, string $folder): array {
    $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err !== UPLOAD_ERR_OK) {
      return ['ok' => false, 'error' => 'Error de subida de imagen.'];
    }

    $tmp = (string)($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
      return ['ok' => false, 'error' => 'Archivo temporal invalido.'];
    }

    $imgInfo = @getimagesize($tmp);
    if (!is_array($imgInfo) || !isset($imgInfo[2])) {
      return ['ok' => false, 'error' => 'El archivo debe ser una imagen valida.'];
    }

    $type = (int)$imgInfo[2];
    $ext = match ($type) {
      IMAGETYPE_JPEG => 'jpg',
      IMAGETYPE_PNG => 'png',
      IMAGETYPE_WEBP => 'webp',
      default => '',
    };

    if ($ext === '') {
      return ['ok' => false, 'error' => 'Formato no soportado. Usa JPG, PNG o WEBP.'];
    }

    $dir = base_path('public_html/uploads/'.$folder);
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
      return ['ok' => false, 'error' => 'No se pudo crear el directorio de subida.'];
    }

    $name = bin2hex(random_bytes(16)).'.'.$ext;
    $dest = rtrim($dir, '/\\').DIRECTORY_SEPARATOR.$name;

    if (!@move_uploaded_file($tmp, $dest)) {
      return ['ok' => false, 'error' => 'No se pudo guardar la imagen subida.'];
    }

    return ['ok' => true, 'path' => '/uploads/'.$folder.'/'.$name];
  }

  public function toggle(): void {
    rate_limit_or_fail('admin:'.client_ip().':'.($_SESSION['uid']??0).':cat_toggle', 20, 60);
    if (!verify_csrf()) { http_response_code(403); exit('CSRF'); }
    $id=(int)($_POST['id']??0); if($id<=0){ redirect('/admin/categories'); }

    $next = trim((string)($_POST['next'] ?? ''));
    if ($next === '') {
      $next = '/admin/categories';
    } else {
      $base = rtrim(base_url(), '/');
      $isRelative = str_starts_with($next, '/');
      $isSameOrigin = str_starts_with($next, $base);
      if (!$isRelative && !$isSameOrigin) {
        $next = '/admin/categories';
      }
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

    flash('ok','Estado actualizado.');
    if ((int)($res['to'] ?? 0) === 0) {
      $parts = [];
      if (!empty($res['children_count'])) $parts[] = 'Se desactivaron tambien '.$res['children_count'].' subcategorias.';
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

    $name = trim((string)($_POST['name'] ?? ''));
    if ($name === '') { http_response_code(422); echo json_encode(['ok'=>false,'error'=>'Nombre requerido']); return; }
    if (CategoryLabel::isSuspicious($name)) {
      http_response_code(422);
      echo json_encode(['ok'=>false,'error'=>'Nombre de categoria invalido']);
      return;
    }

    $parentRaw = trim((string)($_POST['parent_id'] ?? ''));
    $parentId = null;
    if ($parentRaw !== '' && $parentRaw !== '0') {
      if (!ctype_digit($parentRaw)) { http_response_code(422); echo json_encode(['ok'=>false,'error'=>'Categoria padre invalida']); return; }
      $parentId = (int)$parentRaw;
    }

    $slug = str_slug($name);
    $db = db();

    if ($parentId !== null) {
      $stp = $db->prepare("SELECT id FROM categories WHERE id=?");
      $stp->execute([$parentId]);
      if (!$stp->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(422);
        echo json_encode(['ok'=>false,'error'=>'La categoria padre no existe']);
        return;
      }
    }

    $st = $db->prepare("SELECT id, name FROM categories WHERE slug=?");
    $st->execute([$slug]);
    if ($ex = $st->fetch(PDO::FETCH_ASSOC)) {
      echo json_encode(['ok'=>true,'id'=>(int)$ex['id'],'name'=>$ex['name'] ?? $name,'slug'=>$slug]);
      return;
    }

    $ok = $db->prepare("INSERT INTO categories (name,slug,parent_id,is_active) VALUES (?,?,?,1)")
             ->execute([$name, $slug, $parentId]);

    if (!$ok) {
      http_response_code(500);
      echo json_encode(['ok'=>false,'error'=>'DB error']);
      return;
    }

    echo json_encode(['ok'=>true,'id'=>(int)$db->lastInsertId(),'name'=>$name,'slug'=>$slug,'parent_id'=>$parentId]);
  }
}
