<?php
namespace App\Controllers\Admin;

use App\Services\Audit\Audit;
use App\Services\Seo\BrandSeoBlocksService;
use App\Services\Seo\SeoAuditService;
use App\Services\Seo\SeoManagerService;
use PDO;
use Throwable;

class BrandsController extends BaseController {
  public function index(): void {
    $db = db();
    $q  = trim($_GET['q'] ?? '');
    $where = ''; $params=[];
    if ($q!==''){ $where='WHERE (name LIKE :q OR slug LIKE :q)'; $params[':q']="%{$q}%"; }
    $st = $db->prepare("SELECT id,name,slug,sort,cover_image,created_at FROM brands $where ORDER BY sort ASC, name ASC");
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    render_page('admin/brands/index', compact('rows','q'));
  }

  public function form(): void {
    $db = db();
    $id = (int)($_GET['id'] ?? 0);
    $row = ['id'=>0,'name'=>'','slug'=>'','meta_title'=>null,'meta_description'=>null,'seo_text'=>null,'sort'=>0,'cover_image'=>''];
    if ($id>0){ $st=$db->prepare("SELECT * FROM brands WHERE id=?"); $st->execute([$id]); $row=$st->fetch(PDO::FETCH_ASSOC) ?: $row; }

    $seoAudit = (new SeoAuditService($db))->auditBrandForAdmin($row);
    $seoSettings = ($id > 0) ? (new SeoManagerService($db))->getSettings('brand', (int)$id) : [];
    $seoBlocks = ['bullets'=>[], 'specs'=>[], 'faq'=>[]];
    if ($id > 0) {
      try {
        $seoBlocks = (new BrandSeoBlocksService($db))->getForBrand((int)$id);
      } catch (Throwable $e) {
        if (function_exists('log_error')) log_error('Admin\\BrandsController seoBlocks: '.$e->getMessage());
      }
    }

    render_page('admin/brands/form', [
      'pageId' => 'admin-brand-form',
      'brand' => $row,
      'seoAudit' => $seoAudit,
      'seoSettings' => $seoSettings,
      'seoBlocks' => $seoBlocks,
    ]);
  }

  public function save(): void {
    rate_limit_or_fail('admin:'.client_ip().':'.($_SESSION['uid']??0).':brand_save', 20, 60);
    if (!verify_csrf()) { http_response_code(403); exit('CSRF'); }
    header('Content-Type: application/json; charset=utf-8');
    require_once base_path('app/Support/str.php');

    $db=db();
    $id=(int)($_POST['id']??0);
    $name=trim($_POST['name']??'');
    $slug=trim($_POST['slug']??'');
    $sort=(int)($_POST['sort'] ?? 0);
    $coverImage=trim((string)($_POST['cover_image'] ?? ''));
    $coverFile = $_FILES['cover_image_file'] ?? null;

    $prevSlug = '';
    if ($id > 0) {
      try {
        $stPrev = $db->prepare("SELECT slug FROM brands WHERE id=?");
        $stPrev->execute([$id]);
        $prevSlug = (string)($stPrev->fetchColumn() ?: '');
      } catch (Throwable $e) {
        $prevSlug = '';
      }
    }

    $metaTitle = trim((string)($_POST['meta_title'] ?? ''));
    $metaDesc  = trim((string)($_POST['meta_description'] ?? ''));
    $seoText   = trim((string)($_POST['seo_text'] ?? ''));
    $metaTitle = ($metaTitle === '') ? null : $metaTitle;
    $metaDesc  = ($metaDesc === '') ? null : $metaDesc;
    $seoText   = ($seoText === '') ? null : $seoText;
    if ($slug==='' && $name!=='') $slug=str_slug($name);

    $errs=[]; if($name==='')$errs[]='Nombre requerido'; if($slug==='')$errs[]='Slug requerido';
    if(!$errs){ $st=$db->prepare("SELECT COUNT(*) FROM brands WHERE slug=? AND id<>?"); $st->execute([$slug,$id]); if((int)$st->fetchColumn()>0)$errs[]='Slug ya existe'; }

    if (is_array($coverFile) && (int)($coverFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
      $upload = $this->storeCoverImage($coverFile, 'brands');
      if (!($upload['ok'] ?? false)) {
        $errs[] = (string)($upload['error'] ?? 'No se pudo subir la imagen de portada.');
      } else {
        $coverImage = (string)$upload['path'];
      }
    }

    if($errs){ flash('error',implode(' · ',$errs)); redirect($id?'/admin/brands/edit?id='.$id:'/admin/brands/new'); }

    if($id>0){
      $ok=$db->prepare("UPDATE brands SET name=?,slug=?,meta_title=?,meta_description=?,seo_text=?,sort=?,cover_image=? WHERE id=?")
        ->execute([$name,$slug,$metaTitle,$metaDesc,$seoText,$sort,$coverImage,$id]);
      $ok? (Audit::log('brand.save','brand',$id,['op'=>'update']) || true) : flash('error','No se pudo actualizar.');
      if($ok) flash('ok','Marca actualizada.');
    } else {
      $ok=$db->prepare("INSERT INTO brands (name,slug,meta_title,meta_description,seo_text,sort,cover_image) VALUES (?,?,?,?,?,?,?)")
        ->execute([$name,$slug,$metaTitle,$metaDesc,$seoText,$sort,$coverImage]);
      if($ok){ $id=(int)$db->lastInsertId(); Audit::log('brand.save','brand',$id,['op'=>'insert']); flash('ok','Marca creada.'); }
      else { flash('error','No se pudo crear.'); }
    }

    if ($ok && $id > 0) {
      try {
        $seoMgr = new SeoManagerService($db);
        if ($slug !== '') {
          $seoMgr->registerSlug('brand', (int)$id, $slug);
        }

        if ($prevSlug !== '' && $prevSlug !== $slug) {
          $seoMgr->recordBrandSlugChange((int)$id, $prevSlug, $slug);
        }

        $index = (($_POST['seo_index'] ?? '') === 'noindex') ? 'noindex' : 'index';
        $follow = (($_POST['seo_follow'] ?? '') === 'nofollow') ? 'nofollow' : 'follow';
        $robots = $index . ',' . $follow;

        $twitter = trim((string)($_POST['seo_twitter_card'] ?? ''));
        if (!in_array($twitter, ['', 'summary', 'summary_large_image'], true)) {
          $twitter = '';
        }

        $seoMgr->saveSettings('brand', (int)$id, [
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

        (new BrandSeoBlocksService($db))->saveForBrand((int)$id, $bullets, $specs, $faq);
      } catch (Throwable $e) {
        if (function_exists('log_error')) log_error('Admin\\BrandsController@save SEO: '.$e->getMessage());
      }
    }
    redirect('/admin/brands');
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

  public function toggle(): void { http_response_code(204); }

  public function quick(): void {
    rate_limit_or_fail('admin:'.client_ip().':'.($_SESSION['uid']??0).':brand_quick', 15, 60);
    if (!verify_csrf()) { http_response_code(403); exit('CSRF'); }
    header('Content-Type: application/json; charset=utf-8');
    require_once base_path('app/Support/str.php');
    $name=trim($_POST['name']??''); if($name===''){ http_response_code(422); exit('Nombre requerido'); }
    $slug=str_slug($name);
    $db=db();
    $st=$db->prepare("SELECT id FROM brands WHERE slug=?"); $st->execute([$slug]);
    if($ex=$st->fetch(PDO::FETCH_ASSOC)){ echo json_encode(['ok'=>true,'id'=>$ex['id'],'name'=>$name]); return; }
    $ok=$db->prepare("INSERT INTO brands (name,slug) VALUES (?,?)")->execute([$name,$slug]);
    $ok? print json_encode(['ok'=>true,'id'=>(int)$db->lastInsertId(),'name'=>$name]) : (http_response_code(500) || print 'DB error');
  }
}
