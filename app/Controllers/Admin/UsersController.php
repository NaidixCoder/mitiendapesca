<?php
namespace App\Controllers\Admin;

use App\Services\Audit;
use PDO;

class UsersController extends BaseController {

  /** Listado con búsqueda y paginación simple */
  public function index(): void {
    $db   = db();
    $q    = trim($_GET['q'] ?? '');
    $page = max(1, (int)($_GET['page'] ?? 1));
    $per  = 20;
    $off  = ($page - 1) * $per;

    $where  = '';
    $params = [];
    if ($q !== '') {
      $where = "WHERE (email LIKE :q OR name LIKE :q)";
      $params[':q'] = "%{$q}%";
    }

    // total
    $stc = $db->prepare("SELECT COUNT(*) FROM users {$where}");
    $stc->execute($params);
    $total = (int)$stc->fetchColumn();
    $pages = max(1, (int)ceil($total / $per));

    // data
    $st = $db->prepare("
      SELECT id, name, email, role, email_verified, created_at, last_login_at
      FROM users
      {$where}
      ORDER BY created_at DESC
      LIMIT :lim OFFSET :off
    ");
    foreach ($params as $k=>$v) $st->bindValue($k,$v);
    $st->bindValue(':lim', $per, PDO::PARAM_INT);
    $st->bindValue(':off', $off, PDO::PARAM_INT);
    $st->execute();
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    render_page('admin/users/index', compact('rows','total','pages','page','q','per'));
  }

  /** Detalle de usuario + últimos eventos de auditoría */
  public function show(): void {
    $db = db();
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) { http_response_code(404); echo 'User not found'; return; }

    $st = $db->prepare("SELECT id, name, email, role, created_at FROM users WHERE id=:id");
    $st->execute([':id'=>$id]);
    $user = $st->fetch(PDO::FETCH_ASSOC);
    if (!$user) { http_response_code(404); echo 'User not found'; return; }

    $log = $db->prepare("
      SELECT action, metadata, created_at
      FROM admin_audit_log
      WHERE entity_type='user' AND entity_id=:id
      ORDER BY created_at DESC
      LIMIT 20
    ");
    $log->execute([':id'=>$id]);
    $events = $log->fetchAll(PDO::FETCH_ASSOC);

    $pageTitle = 'Admin — Usuario';
    render_page('admin/users/show', compact('user','events','pageTitle'));
  }

  /** Cambio de rol con auditoría */
  public function role(): void {
    rate_limit_or_fail('admin:'.client_ip().':'.($_SESSION['uid']??0).':user_role', 20, 60);
    if (!verify_csrf()) { flash('error','CSRF inválido'); redirect('/admin/users'); }

    $db = db();

    $targetId = (int)($_POST['user_id'] ?? 0);
    $newRole  = (($_POST['role'] ?? 'customer') === 'admin') ? 'admin' : 'customer';

    // NEXT seguro (default a /admin/users; sólo rutas internas)
    $next = $_POST['next'] ?? '/admin/users';
    if (!is_string($next) || !str_starts_with($next, '/')) $next = '/admin/users';

    if ($targetId <= 0) { flash('error','Usuario inválido'); redirect($next); }
    if (($_SESSION['urole'] ?? 'customer') !== 'admin') { http_response_code(403); exit('Forbidden'); }

    // No permitir dejar el sistema sin administradores
    if ($newRole !== 'admin') {
      $countAdmins = (int)$db->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
      if ($countAdmins <= 1) {
        flash('warn','No puedes dejar el sistema sin administradores.');
        redirect($next);
      }
    }

    $ok = $db->prepare("UPDATE users SET role=:r WHERE id=:id")
             ->execute([':r'=>$newRole, ':id'=>$targetId]);

    if ($ok) {
      Audit::log('user.role_change','user',$targetId,[
        'new_role'=>$newRole,
        'by'=>($_SESSION['uid'] ?? null),
      ]);
      cache_forget('feed:audit_recent8'); // para ver el evento en Actividad reciente
      flash('ok','Rol actualizado.');
    } else {
      flash('error','No se pudo actualizar el rol.');
    }

    redirect($next);
  }
}
