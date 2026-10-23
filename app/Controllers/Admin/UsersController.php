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
    $rawQuery = $_GET['q'] ?? '';
    $q = mb_substr(trim(is_string($rawQuery) ? $rawQuery : ''), 0, 120);
    $page = min(100000, max(1, (int)($_GET['page'] ?? 1)));
    $per  = 20;
    $off  = ($page - 1) * $per;

    $where  = '';
    $params = [];
    if ($q !== '') {
      $where = "WHERE (email LIKE :q1 OR name LIKE :q2)";
      $params[':q1'] = "%{$q}%";
      $params[':q2'] = "%{$q}%";
    }

    // total
    $stc = $db->prepare("SELECT COUNT(*) FROM users {$where}");
    $stc->execute($params);
    $total = (int)$stc->fetchColumn();
    $pages = max(1, (int)ceil($total / $per));
    $page = min($page, $pages);
    $off = ($page - 1) * $per;

    // data
    $st = $db->prepare("
      SELECT id, name, email, role, email_verified, created_at, last_login_at, last_activity_at
      FROM users
      {$where}
      ORDER BY created_at DESC, id DESC
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

    try {
      $cs = new CustomerStatsService($db);

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
    $page = min(100000, max(1, (int)($_GET['p'] ?? 1)));
    $perPage = 20;
    $offset = ($page - 1) * $perPage;

    $cnt = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id=:id");
    $cnt->execute([':id'=>$id]);
    $totalOrders = (int)$cnt->fetchColumn();

    $pages = (int)max(1, ceil($totalOrders / $perPage));
    $page = min($page, $pages);
    $offset = ($page - 1) * $perPage;

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

  /** Recalcular exclusivamente por POST autenticado y protegido. */
  public function rebuildStats(): void {
    rate_limit_or_fail('admin:'.client_ip().':'.($_SESSION['uid'] ?? 0).':user_rebuild_stats', 10, 60);
    if (function_exists('verify_csrf_or_abort')) {
      verify_csrf_or_abort();
    } elseif (!verify_csrf()) {
      http_response_code(419);
      exit('CSRF inválido');
    }
    if (($_SESSION['urole'] ?? '') !== 'admin') {
      http_response_code(403);
      exit('Forbidden');
    }
    $value = $_POST['user_id'] ?? null;
    $userId = is_scalar($value) && preg_match('/^[1-9][0-9]*$/D', (string)$value) ? (int)$value : 0;
    if ($userId <= 0) {
      flash('error', 'Usuario inválido.');
      redirect('/admin/users');
    }
    $db = db();
    $next = '/admin/users/show?id=' . $userId;
    try {
      $check = $db->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
      $check->execute([$userId]);
      if (!$check->fetchColumn()) {
        flash('error', 'El usuario no existe.');
        redirect('/admin/users');
      }
      $result = (new CustomerStatsService($db))->rebuildForUser($userId);
      if (empty($result['ok'])) {
        throw new \RuntimeException('Recálculo no confirmado.');
      }
      Audit::log('user.stats_rebuild', 'user', $userId, ['by' => (int)($_SESSION['uid'] ?? 0)]);
      cache_forget('feed:audit_recent8');
      flash('ok', 'Estadísticas recalculadas.');
    } catch (\Throwable $e) {
      error_log('Users rebuild stats failed: ' . $e->getMessage());
      flash('error', 'No se pudieron recalcular las estadísticas.');
    }
    redirect($next);
  }

  /** Cambio de rol con auditoría */
  public function role(): void {
    rate_limit_or_fail('admin:'.client_ip().':'.($_SESSION['uid']??0).':user_role', 20, 60);
    if (!verify_csrf()) { flash('error','CSRF inválido'); redirect('/admin/users'); }

    $db = db();

    $targetId = (int)($_POST['user_id'] ?? $_POST['id'] ?? 0);
    $settings = new AppSettingsRepository($db);
    $whEnabled = (string)($settings->get('wh_enabled', '0') ?? '0');

    $rawRole = $_POST['role'] ?? null;
    $requested = is_string($rawRole) ? strtolower(trim($rawRole)) : '';
    $allowedRoles = ['customer','admin'];
    if ($whEnabled === '1') {
      $allowedRoles[] = 'mayorista1';
      $allowedRoles[] = 'mayorista2';
    }
    if (!in_array($requested, $allowedRoles, true)) {
      flash('error', 'Rol no permitido.');
      redirect('/admin/users');
    }
    $newRole = $requested;

    // NEXT seguro (default a /admin/users; sólo rutas internas)
    $next = $_POST['next'] ?? '/admin/users';
    if (!is_string($next) || !preg_match('~^/admin/users(?:/show\?id=[1-9][0-9]*)?$~D', $next)) $next = '/admin/users';

    if ($targetId <= 0 || $targetId === (int)($_SESSION['uid'] ?? 0)) { flash('error','Operación no permitida para esta cuenta.'); redirect($next); }
    if (($_SESSION['urole'] ?? 'customer') !== 'admin') { http_response_code(403); exit('Forbidden'); }

    try {
      $result = (new \App\Services\Admin\Users\UserAccessService($db))
        ->changeRole((int)($_SESSION['uid'] ?? 0), $targetId, $newRole);
      if ($result['changed']) {
        Audit::log('user.role_change', 'user', $targetId, [
          'old_role' => $result['old_role'], 'new_role' => $newRole,
          'by' => (int)($_SESSION['uid'] ?? 0),
        ]);
        cache_forget('feed:audit_recent8');
      }
      flash('ok', $result['changed'] ? 'Rol actualizado.' : 'El usuario ya tiene ese rol.');
    } catch (\Throwable $e) {
      error_log('Users role change failed: ' . $e->getMessage());
      flash('error', 'No se pudo cambiar el rol: ' . $e->getMessage());
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
    if (!is_string($next) || !preg_match('~^/admin/users(?:/show\?id=[1-9][0-9]*)?$~D', $next)) $next = '/admin/users';

    if ($targetId <= 0 || $targetId === (int)($_SESSION['uid'] ?? 0)) { flash('error','Operación no permitida para esta cuenta.'); redirect($next); }

    $targetId = (int)($_POST['user_id'] ?? $_POST['id'] ?? 0);
    $next = $_POST['next'] ?? '/admin/users';
    if (!is_string($next) || !preg_match('~^/admin/users(?:/show\?id=[1-9][0-9]*)?$~D', $next)) $next = '/admin/users';

    try {
      (new \App\Services\Admin\Users\UserAccessService(db()))
        ->invalidateSessions((int)($_SESSION['uid'] ?? 0), $targetId);
      Audit::log('user.force_logout', 'user', $targetId, ['by' => (int)($_SESSION['uid'] ?? 0)]);
      cache_forget('feed:audit_recent8');
      flash('ok', 'Las sesiones del usuario fueron invalidadas.');
    } catch (\Throwable $e) {
      error_log('Users force logout failed: ' . $e->getMessage());
      flash('error', 'No se pudo invalidar la sesión.');
    }

    redirect($next);
  }

}
