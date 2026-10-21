<?php
namespace App\Controllers\Admin;

use App\Services\Audit\Audit;
use PDO;
use App\Services\Admin\CustomerStatsService;
use App\Repositories\Settings\AppSettingsRepository;
use App\Services\Admin\Users\ViewModels\UsersShowViewModel;

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
      SELECT id, name, email, role, email_verified, created_at, last_login_at, last_activity_at
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

    // Opciones de roles: mayoristas visibles solo si está habilitado en Ajustes
    $settings = new AppSettingsRepository($db);
    $whEnabled = (string)($settings->get('wh_enabled', '0') ?? '0');
    $roleOptions = ['customer','admin'];
    if ($whEnabled === '1') {
      $roleOptions[] = 'mayorista1';
      $roleOptions[] = 'mayorista2';
    }

    render_page('admin/users/index', compact('rows','total','pages','page','q','per','roleOptions'));
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

    // Stats (agregados por usuario) - Opción B
    $stats = [];
    $rebuild = (int)($_GET['rebuild'] ?? 0) === 1;

    try {
      $cs = new CustomerStatsService($db);

      if ($rebuild) {
        $cs->rebuildForUser($id);
      } else {
        $chk = $db->prepare("SELECT user_id FROM customer_stats WHERE user_id=:id");
        $chk->execute([':id'=>$id]);
        $exists = $chk->fetch(PDO::FETCH_ASSOC);
        if (!$exists) {
          $cs->rebuildForUser($id);
        }
      }

      $stStats = $db->prepare("
        SELECT user_id, paid_orders_count, paid_total, first_paid_at, last_paid_at,
               avg_paid_total, loyalty_points, loyalty_tier, updated_at
        FROM customer_stats
        WHERE user_id=:id
      ");
      $stStats->execute([':id'=>$id]);
      $stats = $stStats->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (\Throwable $e) {
      // Si la tabla no existe aún (o falla la migración), no rompemos la vista.
      $stats = [];
    }

    // Historial completo de pedidos (paginado)
    $page = max(1, (int)($_GET['p'] ?? 1));
    $perPage = 20;
    $offset = ($page - 1) * $perPage;

    $cnt = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id=:id");
    $cnt->execute([':id'=>$id]);
    $totalOrders = (int)$cnt->fetchColumn();

    $pages = (int)max(1, ceil($totalOrders / $perPage));

    $ord = $db->prepare("
      SELECT id, status, payment_method, total, created_at
      FROM orders
      WHERE user_id=:id
      ORDER BY created_at DESC, id DESC
      LIMIT :lim OFFSET :off
    ");
    $ord->bindValue(':id', $id, PDO::PARAM_INT);
    $ord->bindValue(':lim', $perPage, PDO::PARAM_INT);
    $ord->bindValue(':off', $offset, PDO::PARAM_INT);
    $ord->execute();
    $orders = $ord->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $pageTitle = 'Admin — Usuario';
    
    // Datos opcionales del cliente (user_profiles / direcciones)
    $userProfile = null;
    try {
      $st = $db->prepare("SELECT phone, birth_date, marketing_email_opt_in, marketing_whatsapp_opt_in FROM user_profiles WHERE user_id = :id LIMIT 1");
      $st->execute([':id' => $id]);
      $userProfile = $st->fetch(\PDO::FETCH_ASSOC) ?: null;
    } catch (\Throwable $e) {
      // tabla ausente o columnas distintas: no rompemos la vista
      $userProfile = null;
    }

    $addresses = [];
    try {
      // Importante: adaptado al schema real (user_addresses) para no fallar silenciosamente.
      // Mapeamos columnas reales a los nombres que la vista ya espera.
      $st = $db->prepare("SELECT
                            id,
                            label,
                            full_name AS recipient_name,
                            phone,
                            city,
                            state AS province,
                            zip AS postal_code,
                            TRIM(CONCAT(street, ' ', number)) AS address_line1,
                            NULLIF(TRIM(CONCAT(
                              CASE WHEN floor IS NOT NULL AND floor <> '' THEN CONCAT('Piso ', floor) ELSE '' END,
                              CASE WHEN apartment IS NOT NULL AND apartment <> '' THEN CONCAT(
                                CASE WHEN floor IS NOT NULL AND floor <> '' THEN ' · ' ELSE '' END,
                                'Dpto ', apartment
                              ) ELSE '' END
                            )), '') AS address_line2,
                            notes,
                            is_default
                          FROM user_addresses
                          WHERE user_id = :id
                          ORDER BY is_default DESC, id DESC
                          LIMIT 5");
      $st->execute([':id' => $id]);
      $addresses = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    } catch (\Throwable $e) {
      $addresses = [];
    }

    $vm = (new UsersShowViewModel())->build([
      'user' => $user,
      'stats' => $stats,
      'orders' => $orders,
      'page' => $page,
      'pages' => $pages,
      'totalOrders' => $totalOrders,
      'userProfile' => $userProfile,
      'addresses' => $addresses,
    ]);

    render_page('admin/users/show', array_merge(compact('user','stats','orders','page','pages','totalOrders','pageTitle','userProfile','addresses'), $vm));
  }

  /** Cambio de rol con auditoría */
  public function role(): void {
    rate_limit_or_fail('admin:'.client_ip().':'.($_SESSION['uid']??0).':user_role', 20, 60);
    if (!verify_csrf()) { flash('error','CSRF inválido'); redirect('/admin/users'); }

    $db = db();

    $targetId = (int)($_POST['user_id'] ?? $_POST['id'] ?? 0);
    $settings = new AppSettingsRepository($db);
    $whEnabled = (string)($settings->get('wh_enabled', '0') ?? '0');

    $requested = strtolower(trim((string)($_POST['role'] ?? 'customer')));
    $allowedRoles = ['customer','admin'];
    if ($whEnabled === '1') {
      $allowedRoles[] = 'mayorista1';
      $allowedRoles[] = 'mayorista2';
    }
    if (!in_array($requested, $allowedRoles, true)) {
      $requested = 'customer';
    }
    $newRole = $requested;

    // NEXT seguro (default a /admin/users; sólo rutas internas)
    $next = $_POST['next'] ?? '/admin/users';
    if (!is_string($next) || !str_starts_with($next, '/')) $next = '/admin/users';

    if ($targetId <= 0) { flash('error','Usuario inválido'); redirect($next); }
    if (($_SESSION['urole'] ?? 'customer') !== 'admin') { http_response_code(403); exit('Forbidden'); }

    // Rol actual del usuario objetivo (para no bloquear cambios que no afectan admins)
    $stCur = $db->prepare("SELECT role FROM users WHERE id=:id LIMIT 1");
    $stCur->execute([':id' => $targetId]);
    $currentRole = (string)($stCur->fetchColumn() ?: '');

    // No permitir dejar el sistema sin administradores (solo si estás degradando un admin)
    if ($currentRole === 'admin' && $newRole !== 'admin') {
      $countAdmins = (int)$db->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
      if ($countAdmins <= 1) {
        flash('warn','No puedes dejar el sistema sin administradores.');
        redirect($next);
      }
    }

    // Debug pre (rol/sv)
    $pre = null;
    try {
      $stPre = $db->prepare("SELECT role, session_version FROM users WHERE id=:id LIMIT 1");
      $stPre->execute([':id' => $targetId]);
      $pre = $stPre->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (\Throwable $e) { /* noop */ }

    $sql = "UPDATE users SET role=:r";
    $hasSv = (function_exists('db_has_column') && db_has_column('users','session_version'));
    if ($hasSv) {
      // COALESCE: si session_version es NULL en alguna fila vieja, NULL+1 queda NULL.
      $sql .= ", session_version = COALESCE(session_version, 0) + 1";
    }
    $sql .= " WHERE id=:id";

    $stUp = $db->prepare($sql);
    $ok = $stUp->execute([':r'=>$newRole, ':id'=>$targetId]);
    $aff = (int)$stUp->rowCount();

    // Debug post
    $post = null;
    try {
      $stPost = $db->prepare("SELECT role, session_version FROM users WHERE id=:id LIMIT 1");
      $stPost->execute([':id' => $targetId]);
      $post = $stPost->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (\Throwable $e) { /* noop */ }

    debug_log('admin.users.role', [
      'target_id' => $targetId,
      'new_role' => $newRole,
      'has_session_version' => $hasSv,
      'ok' => $ok,
      'row_count' => $aff,
      'pre' => $pre,
      'post' => $post,
      'by' => ($_SESSION['uid'] ?? null),
    ]);

    if ($ok && $aff === 1) {
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


  /** Forzar cierre de sesión de un usuario (incrementa session_version). */
  public function forceLogout(): void {
    rate_limit_or_fail('admin:'.client_ip().':'.($_SESSION['uid']??0).':user_force_logout', 20, 60);
    // CSRF: abort (419) en vez de fail silencioso.
    if (function_exists('verify_csrf_or_abort')) {
      verify_csrf_or_abort();
    } else {
      if (!verify_csrf()) { flash('error','CSRF inválido'); redirect('/admin/users'); }
    }

    if (($_SESSION['urole'] ?? 'customer') !== 'admin') { http_response_code(403); exit('Forbidden'); }

    $targetId = (int)($_POST['user_id'] ?? $_POST['id'] ?? 0);
    $next = $_POST['next'] ?? '/admin/users';
    if (!is_string($next) || !str_starts_with($next, '/')) $next = '/admin/users';

    if ($targetId <= 0) { flash('error','Usuario inválido'); redirect($next); }

    $db = db();

    // Leemos pre/post para tener certeza (y para feedback en pantalla).
    $preSv = null;
    $postSv = null;
    $ok = false;
    $aff = 0;

    try {
      $stPre = $db->prepare('SELECT session_version FROM users WHERE id = ? LIMIT 1');
      $stPre->execute([$targetId]);
      $preSv = $stPre->fetchColumn();
      $preSv = ($preSv === false) ? null : (int)$preSv;

      // Incremento simple (misma lógica que /cuenta/sessions/clear)
      $stUp = $db->prepare('UPDATE users SET session_version = session_version + 1 WHERE id = ?');
      $ok = (bool)$stUp->execute([$targetId]);
      $aff = (int)$stUp->rowCount();

      $stPost = $db->prepare('SELECT session_version FROM users WHERE id = ? LIMIT 1');
      $stPost->execute([$targetId]);
      $postSv = $stPost->fetchColumn();
      $postSv = ($postSv === false) ? null : (int)$postSv;
    } catch (\Throwable $e) {
      $ok = false;
    }

    // Debug log best-effort
    if (function_exists('debug_log')) {
      debug_log('admin.users.force_logout', [
        'target_id' => $targetId,
        'ok' => $ok,
        'row_count' => $aff,
        'pre_sv' => $preSv,
        'post_sv' => $postSv,
        'by' => ($_SESSION['uid'] ?? null),
        'server' => $_SERVER['SERVER_NAME'] ?? null,
        'https' => (!empty($_SERVER['HTTPS']) ? (string)$_SERVER['HTTPS'] : ''),
      ]);
    }

    if ($ok && $aff === 1 && $preSv !== null && $postSv !== null && $postSv !== $preSv) {
      Audit::log('user.force_logout','user',$targetId,[
        'by'=>($_SESSION['uid'] ?? null),
      ]);
      cache_forget('feed:audit_recent8');
      flash('ok','Sesión cerrada. Versión de sesión: ' . (string)$preSv . ' → ' . (string)$postSv . '.');
    } else {
      flash('error','No se pudo cerrar la sesión del usuario.');
    }

    redirect($next);
  }

}
