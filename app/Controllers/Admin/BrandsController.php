<?php
namespace App\Controllers\Admin;

use App\Repositories\Admin\BrandAdminRepository;
use App\Services\Admin\Brands\ViewModels\BrandsIndexViewModel;
use App\Services\Admin\Brands\BrandFormDraft;
use App\Services\Admin\Brands\BrandSaveService;
use App\Services\Admin\Brands\BrandSeoInput;
use App\Services\Audit\Audit;
use App\Services\Seo\BrandSeoBlocksService;
use App\Services\Seo\SeoAuditService;
use App\Services\Seo\SeoManagerService;
use PDO;
use Throwable;

class BrandsController extends BaseController {
  public function index(): void {
    $q = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 120);
    $per = in_array((int)($_GET['per'] ?? 25), [25, 50], true) ? (int)($_GET['per'] ?? 25) : 25;
    $page = max(1, (int)($_GET['page'] ?? 1));
    $result = (new BrandAdminRepository(db()))->listPage($q, $page, $per);
    $vm = (new BrandsIndexViewModel())->build($result['rows'], $result['total'], $q, $page, $per);
    render_page('admin/brands/index', array_merge(['pageId' => 'admin-brands-index'], $vm));
  }

  public function form(): void {
    $db = db();
    $id = (int)($_GET['id'] ?? 0);
    $row = ['id'=>0,'name'=>'','slug'=>'','meta_title'=>null,'meta_description'=>null,'seo_text'=>null,'sort'=>0,'cover_image'=>''];
    if ($id > 0) {
      $existing = (new BrandAdminRepository($db))->find($id);
      if ($existing === null) {
        flash('error', 'La marca solicitada no existe.');
        redirect('/admin/brands');
        return;
      }
      $row = $existing;
    }

    $seoSettings = ($id > 0) ? (new SeoManagerService($db))->getSettings('brand', (int)$id) : [];
    $seoBlocks = ['bullets'=>[], 'specs'=>[], 'faq'=>[]];
    if ($id > 0) {
      try {
        $seoBlocks = (new BrandSeoBlocksService($db))->getForBrand((int)$id);
      } catch (Throwable $e) {
        if (function_exists('log_error')) log_error('Admin\\BrandsController seoBlocks: '.$e->getMessage());
        // No mostrar bloques vacíos si falló leer los existentes: guardar
        // desde ese formulario borraría contenido legítimo accidentalmente.
        flash('error', 'No se pudo cargar la información SEO de la marca. No se realizaron cambios.');
        redirect('/admin/brands');
        return;
      }
    }

    $oldValues = BrandFormDraft::consume($id);
    BrandFormDraft::overlay($row, $seoSettings, $seoBlocks, $oldValues);
    $seoAudit = (new SeoAuditService($db))->auditBrandForAdmin($row);

    render_page('admin/brands/form', [
      'pageId' => 'admin-brand-form',
      'brand' => $row,
      'seoAudit' => $seoAudit,
      'seoSettings' => $seoSettings,
      'seoBlocks' => $seoBlocks,
      'hadValidationError' => $oldValues !== [],
    ]);
  }

  public function save(): void {
    rate_limit_or_fail('admin:'.client_ip().':'.($_SESSION['uid']??0).':brand_save', 20, 60);
    if (!verify_csrf()) { http_response_code(403); exit('CSRF'); }
    require_once base_path('app/Support/str.php');

    $db=db();
    $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
    if ($id === false || $id < 0) {
      flash('error', 'Identificador de marca inválido.');
      redirect('/admin/brands');
      return;
    }
    $name = trim(is_string($_POST['name'] ?? null) ? $_POST['name'] : '');
    $slug = trim(is_string($_POST['slug'] ?? null) ? $_POST['slug'] : '');
    $sortRaw = $_POST['sort'] ?? '0';
    $sort = filter_var($sortRaw, FILTER_VALIDATE_INT);
    $coverImage = trim(is_string($_POST['cover_image'] ?? null) ? $_POST['cover_image'] : '');
    $coverFile = $_FILES['cover_image_file'] ?? null;

    if ($id > 0) {
      $previous = (new BrandAdminRepository($db))->find($id);
      if ($previous === null) {
        flash('error', 'La marca que intentás modificar ya no existe.');
        redirect('/admin/brands');
        return;
      }
    }

    $metaTitle = trim(is_string($_POST['meta_title'] ?? null) ? $_POST['meta_title'] : '');
    $metaDesc  = trim(is_string($_POST['meta_description'] ?? null) ? $_POST['meta_description'] : '');
    $seoText   = trim(is_string($_POST['seo_text'] ?? null) ? $_POST['seo_text'] : '');
    $metaTitle = ($metaTitle === '') ? null : $metaTitle;
    $metaDesc  = ($metaDesc === '') ? null : $metaDesc;
    $seoText   = ($seoText === '') ? null : $seoText;
    if ($slug==='' && $name!=='') $slug=str_slug($name);

    $errs = [];
    if ($name === '') $errs[] = 'El nombre es obligatorio.';
    if (mb_strlen($name) > 120) $errs[] = 'El nombre no puede superar 120 caracteres.';
    if ($slug === '') $errs[] = 'El slug es obligatorio.';
    if (strlen($slug) > 140) $errs[] = 'El slug no puede superar 140 caracteres.';
    if ($slug !== '' && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug)) {
      $errs[] = 'El slug debe usar minúsculas, números y guiones.';
    }
    if ($sort === false || $sort < -2147483648 || $sort > 2147483647) $errs[] = 'El orden debe ser un número entero válido.';
    if (!is_string($_POST['meta_title'] ?? '') || !is_string($_POST['meta_description'] ?? '') || !is_string($_POST['seo_text'] ?? '')) $errs[] = 'Los campos SEO deben contener texto.';
    if (mb_strlen((string)$metaTitle) > 70) $errs[] = 'El título SEO no puede superar 70 caracteres.';
    if (mb_strlen((string)$metaDesc) > 160) $errs[] = 'La descripción SEO no puede superar 160 caracteres.';
    if (strlen($coverImage) > 255 || ($coverImage !== '' && !preg_match('#^(https?://|/|[a-z0-9_-]+/)#i', $coverImage))) {
      $errs[] = 'La URL de portada debe ser una ruta pública o un enlace HTTP/HTTPS válido.';
    }
    if (!$errs && (new BrandAdminRepository($db))->slugExists($slug, $id)) $errs[] = 'Ya existe otra marca con ese slug.';
    $seoInput = BrandSeoInput::parse($_POST);
    $errs = array_merge($errs, $seoInput['errors']);
    if ($errs) {
      BrandFormDraft::remember($id, $_POST);
      flash('error', implode(' · ', array_unique($errs)));
      redirect($id ? '/admin/brands/edit?id=' . $id : '/admin/brands/new');
      return;
    }

    $uploadedPath = '';
    if (is_array($coverFile) && (int)($coverFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
      try {
        $upload = $this->storeCoverImage($coverFile, 'brands');
        if (!($upload['ok'] ?? false)) {
          $errs[] = (string)($upload['error'] ?? 'No se pudo subir la imagen de portada.');
        } else {
          $coverImage = $uploadedPath = (string)$upload['path'];
        }
      } catch (Throwable $e) {
        if (function_exists('log_error')) log_error('Admin\\BrandsController upload: '.$e->getMessage());
        $errs[] = 'No se pudo procesar la portada.';
      }
    }
    if ($errs) {
      BrandFormDraft::remember($id, $_POST);
      flash('error', implode(' · ', $errs));
      redirect($id ? '/admin/brands/edit?id=' . $id : '/admin/brands/new');
      return;
    }

    $originalId = $id;
    $previousCover = ($id > 0) ? (string)($previous['cover_image'] ?? '') : '';
    try {
      // Un solo COMMIT para brands + seo_settings + slug_history +
      // seo_redirects + brand_seo_blocks. El servicio comparte este PDO.
      $id = (new BrandSaveService($db))->save(
        $id,
        [
          'name' => $name, 'slug' => $slug,
          'sort' => (int)$sort, 'cover_image' => $coverImage,
          'meta_title' => $metaTitle, 'meta_description' => $metaDesc,
          'seo_text' => $seoText,
        ],
        $seoInput['settings'], $seoInput['bullets'],
        $seoInput['specs'], $seoInput['faq']
      );
    } catch (Throwable $e) {
      // Nunca borrar la portada anterior si falla DB/SEO; solo el nuevo
      // archivo de este POST, cuyo nombre es aleatorio y controlado.
      if ($uploadedPath !== '') $this->removeManagedBrandCover($uploadedPath);
      if (function_exists('log_error')) log_error('Admin\\BrandsController atomic save: '.$e->getMessage());
      BrandFormDraft::remember($originalId, $_POST);
      flash('error', 'No se pudo guardar la marca y sus datos SEO. No se aplicó ningún cambio; revisá los datos e intentá nuevamente.');
      redirect($originalId ? '/admin/brands/edit?id=' . $originalId : '/admin/brands/new');
      return;
    }

    // SQL confirmado: recién ahora se puede liberar una imagen anterior
    // generada por este módulo, siempre que ninguna marca la referencie.
    if ($previousCover !== '' && $previousCover !== $coverImage) {
      $this->removeManagedBrandCover($previousCover, $db);
    }
    try {
      Audit::log('brand.save', 'brand', $id, ['op' => $originalId > 0 ? 'update' : 'insert']);
    } catch (Throwable $e) {
      if (function_exists('log_error')) log_error('Admin\\BrandsController audit: '.$e->getMessage());
    }
    flash('ok', $originalId ? 'Marca actualizada.' : 'Marca creada.');
    redirect('/admin/brands');
  }

  /**
   * Solo archivos generados por storeCoverImage(), nunca URLs remotas,
   * archivos heredados con nombres arbitrarios ni rutas de otros módulos.
   * Puede ejecutarse tras COMMIT o como compensación de una subida nueva.
   */
  private function removeManagedBrandCover(string $path, ?PDO $db = null): void {
    if (!preg_match('#^/uploads/brands/[0-9a-f]{32}\.(?:png|jpg|webp)$#D', $path)) return;
    try {
      if ($db !== null) {
        $st = $db->prepare('SELECT COUNT(*) FROM brands WHERE cover_image = ?');
        $st->execute([$path]);
        if ((int)$st->fetchColumn() > 0) return;
      }
      $dir = base_path('public_html/uploads/brands');
      $target = $dir . '/' . basename($path);
      if (is_link($target) || !is_file($target)) return;
      if (!@unlink($target) && function_exists('log_error')) {
        log_error('Admin\\BrandsController cover cleanup failed: '.$path);
      }
    } catch (Throwable $e) {
      if (function_exists('log_error')) log_error('Admin\\BrandsController cover cleanup: '.$e->getMessage());
    }
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

    $maxBytes = 5 * 1024 * 1024;
    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size > $maxBytes) {
      return ['ok' => false, 'error' => 'La imagen debe pesar entre 1 byte y 5 MB.'];
    }
    $tmp = (string)($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
      return ['ok' => false, 'error' => 'Archivo temporal invalido.'];
    }

    $imgInfo = @getimagesize($tmp);
    if (!is_array($imgInfo) || !isset($imgInfo[2])) {
      return ['ok' => false, 'error' => 'El archivo debe ser una imagen válida.'];
    }
    if ((int)($imgInfo[0] ?? 0) < 1 || (int)($imgInfo[1] ?? 0) < 1 ||
        (int)$imgInfo[0] > 6000 || (int)$imgInfo[1] > 6000) {
      return ['ok' => false, 'error' => 'Las dimensiones de la imagen deben ser menores a 6000 px.'];
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
    $name = trim(is_string($_POST['name'] ?? null) ? $_POST['name'] : '');
    if ($name === '' || mb_strlen($name) > 120) {
      http_response_code(422);
      echo json_encode(['ok'=>false, 'error'=>'Ingresá un nombre de hasta 120 caracteres.']);
      return;
    }
    $slug = str_slug($name);
    if ($slug === '' || strlen($slug) > 140) {
      http_response_code(422);
      echo json_encode(['ok'=>false, 'error'=>'El nombre no permite generar un slug válido.']);
      return;
    }
    $db = db();
    try {
      $st = $db->prepare("SELECT id, name FROM brands WHERE slug=? LIMIT 1");
      $st->execute([$slug]);
      if ($existing = $st->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode(['ok'=>true, 'id'=>(int)$existing['id'], 'name'=>(string)$existing['name']]);
        return;
      }
      $ok = $db->prepare("INSERT INTO brands (name,slug) VALUES (?,?)")->execute([$name,$slug]);
      if (!$ok) throw new \RuntimeException('No se pudo insertar la marca.');
      echo json_encode(['ok'=>true,'id'=>(int)$db->lastInsertId(),'name'=>$name]);
    } catch (Throwable $e) {
      // Entre el SELECT y el INSERT otro operador puede crear la misma marca.
      $st = $db->prepare("SELECT id, name FROM brands WHERE slug=? LIMIT 1");
      $st->execute([$slug]);
      if ($existing = $st->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode(['ok'=>true, 'id'=>(int)$existing['id'], 'name'=>(string)$existing['name']]);
        return;
      }
      if (function_exists('log_error')) log_error('Admin\BrandsController quick: '.$e->getMessage());
      http_response_code(500);
      echo json_encode(['ok'=>false, 'error'=>'No se pudo crear la marca.']);
    }
  }
}
