<?php
namespace App\Controllers\Admin;

use App\Services\Audit;
use PDO;

class BrandsController extends BaseController {
  public function index(): void {
    $db = db();
    $q  = trim($_GET['q'] ?? '');
    $where = ''; $params=[];
    if ($q!==''){ $where='WHERE (name LIKE :q OR slug LIKE :q)'; $params[':q']="%{$q}%"; }
    $st = $db->prepare("SELECT id,name,slug,created_at FROM brands $where ORDER BY name");
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    render_page('admin/brands/index', compact('rows','q'));
  }

  public function form(): void {
    $db = db();
    $id = (int)($_GET['id'] ?? 0);
    $row = ['id'=>0,'name'=>'','slug'=>''];
    if ($id>0){ $st=$db->prepare("SELECT * FROM brands WHERE id=?"); $st->execute([$id]); $row=$st->fetch(PDO::FETCH_ASSOC) ?: $row; }
    render_page('admin/brands/form', ['brand'=>$row]);
  }

  public function save(): void {
    rate_limit_or_fail('admin:'.client_ip().':'.($_SESSION['uid']??0).':brand_save', 20, 60);
    if (!verify_csrf()) { http_response_code(403); exit('CSRF'); }
    require_once base_path('app/Support/str.php');

    $db=db();
    $id=(int)($_POST['id']??0);
    $name=trim($_POST['name']??'');
    $slug=trim($_POST['slug']??'');
    if ($slug==='' && $name!=='') $slug=str_slug($name);

    $errs=[]; if($name==='')$errs[]='Nombre requerido'; if($slug==='')$errs[]='Slug requerido';
    if(!$errs){ $st=$db->prepare("SELECT COUNT(*) FROM brands WHERE slug=? AND id<>?"); $st->execute([$slug,$id]); if((int)$st->fetchColumn()>0)$errs[]='Slug ya existe'; }
    if($errs){ flash('error',implode(' · ',$errs)); redirect($id?'/admin/brands/edit?id='.$id:'/admin/brands/new'); }

    if($id>0){
      $ok=$db->prepare("UPDATE brands SET name=?,slug=? WHERE id=?")->execute([$name,$slug,$id]);
      $ok? (Audit::log('brand.save','brand',$id,['op'=>'update']) || true) : flash('error','No se pudo actualizar.');
      if($ok) flash('ok','Marca actualizada.');
    } else {
      $ok=$db->prepare("INSERT INTO brands (name,slug) VALUES (?,?)")->execute([$name,$slug]);
      if($ok){ $id=(int)$db->lastInsertId(); Audit::log('brand.save','brand',$id,['op'=>'insert']); flash('ok','Marca creada.'); }
      else { flash('error','No se pudo crear.'); }
    }
    redirect('/admin/brands');
  }

  public function toggle(): void { http_response_code(204); } // placeholder
  public function quick(): void {
    rate_limit_or_fail('admin:'.client_ip().':'.($_SESSION['uid']??0).':brand_quick', 15, 60);
    if (!verify_csrf()) { http_response_code(403); exit('CSRF'); }
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
