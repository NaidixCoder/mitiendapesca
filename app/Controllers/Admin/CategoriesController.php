<?php
namespace App\Controllers\Admin;

use App\Services\Audit;
use PDO;

class CategoriesController extends BaseController {
  public function index(): void {
    $db=db();
    $rows=$db->query("
      SELECT c.id,c.name,c.slug,c.parent_id,p.name AS parent_name,c.sort,c.is_active
      FROM categories c
      LEFT JOIN categories p ON p.id=c.parent_id
      ORDER BY COALESCE(p.name,''), c.sort, c.name
    ")->fetchAll(PDO::FETCH_ASSOC);
    render_page('admin/categories/index', compact('rows'));
  }

  public function form(): void {
    $db=db();
    $id=(int)($_GET['id']??0);
    $row=['id'=>0,'name'=>'','slug'=>'','parent_id'=>null,'sort'=>0,'is_active'=>1];
    if($id>0){ $st=$db->prepare("SELECT * FROM categories WHERE id=?"); $st->execute([$id]); $row=$st->fetch(PDO::FETCH_ASSOC)?:$row; }
    $parents=$db->query("SELECT id,name FROM categories WHERE id<>$id ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    render_page('admin/categories/form', ['cat'=>$row,'parents'=>$parents]);
  }

  public function save(): void {
    rate_limit_or_fail('admin:'.client_ip().':'.($_SESSION['uid']??0).':cat_save', 20, 60);
    if (!verify_csrf()) { http_response_code(403); exit('CSRF'); }
    require_once base_path('app/Support/str.php');

    $db=db();
    $id=(int)($_POST['id']??0);
    $name=trim($_POST['name']??'');
    $slug=trim($_POST['slug']??'');
    $parent=$_POST['parent_id']!==''?(int)$_POST['parent_id']:null;
    $sort=(int)($_POST['sort']??0);
    $active=isset($_POST['is_active'])?1:0;

    if($slug==='' && $name!=='') $slug=str_slug($name);

    $errs=[]; if($name==='')$errs[]='Nombre requerido'; if($slug==='')$errs[]='Slug requerido';
    if($parent && $parent===$id) $errs[]='La categoría no puede ser su propio padre';
    if(!$errs){ $st=$db->prepare("SELECT COUNT(*) FROM categories WHERE slug=? AND id<>?"); $st->execute([$slug,$id]); if((int)$st->fetchColumn()>0)$errs[]='Slug ya existe'; }
    if($errs){ flash('error',implode(' · ',$errs)); redirect($id?'/admin/categories/edit?id='.$id:'/admin/categories/new'); }

    if($id>0){
      $ok=$db->prepare("UPDATE categories SET name=?,slug=?,parent_id=?,sort=?,is_active=? WHERE id=?")->execute([$name,$slug,$parent,$sort,$active,$id]);
      $ok? (Audit::log('category.save','category',$id,['op'=>'update']) || true) : flash('error','No se pudo actualizar.');
      if($ok) flash('ok','Categoría actualizada.');
    } else {
      $ok=$db->prepare("INSERT INTO categories (name,slug,parent_id,sort,is_active) VALUES (?,?,?,?,?)")->execute([$name,$slug,$parent,$sort,$active]);
      if($ok){ $id=(int)$db->lastInsertId(); Audit::log('category.save','category',$id,['op'=>'insert']); flash('ok','Categoría creada.'); }
      else { flash('error','No se pudo crear.'); }
    }
    redirect('/admin/categories');
  }

  public function toggle(): void {
    rate_limit_or_fail('admin:'.client_ip().':'.($_SESSION['uid']??0).':cat_toggle', 20, 60);
    if (!verify_csrf()) { http_response_code(403); exit('CSRF'); }
    $id=(int)($_POST['id']??0); if($id<=0){ redirect('/admin/categories'); }
    $db=db();
    $cur=(int)$db->query("SELECT is_active FROM categories WHERE id={$id}")->fetchColumn();
    $new=$cur?0:1;
    $ok=$db->prepare("UPDATE categories SET is_active=? WHERE id=?")->execute([$new,$id]);
    if($ok){ Audit::log('category.toggle','category',$id,['from'=>$cur,'to'=>$new]); flash('ok','Estado actualizado.'); }
    else { flash('error','No se pudo actualizar.'); }
    redirect('/admin/categories');
  }

  public function quick(): void {
    rate_limit_or_fail('admin:'.client_ip().':'.($_SESSION['uid']??0).':cat_quick', 15, 60);
    if (!verify_csrf()) { http_response_code(403); exit('CSRF'); }
    require_once base_path('app/Support/str.php');
    $name=trim($_POST['name']??''); if($name===''){ http_response_code(422); exit('Nombre requerido'); }
    $slug=str_slug($name);
    $db=db();
    $st=$db->prepare("SELECT id FROM categories WHERE slug=?"); $st->execute([$slug]);
    if($ex=$st->fetch(PDO::FETCH_ASSOC)){ echo json_encode(['ok'=>true,'id'=>$ex['id'],'name'=>$name]); return; }
    $ok=$db->prepare("INSERT INTO categories (name,slug,is_active) VALUES (?,?,1)")->execute([$name,$slug]);
    $ok? print json_encode(['ok'=>true,'id'=>(int)$db->lastInsertId(),'name'=>$name]) : (http_response_code(500) || print 'DB error');
  }
}
