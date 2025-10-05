<?php
$pageTitle = 'Mi cuenta — Tienda Pesca';
$pageDesc  = 'Resumen de tu cuenta, pedidos y datos.';

// Gate mínimo
if (empty($_SESSION['uid'])) {
  $_SESSION['back_to'] = '/cuenta';
  flash('ok', 'Iniciá sesión para ver tu cuenta.');
  redirect('/login');
}

$uid       = (int)($_SESSION['uid'] ?? 0);
$uname     = $_SESSION['uname'] ?? 'Usuario';
$email     = $_SESSION['email'] ?? '';
$avatarUrl = $_SESSION['uavatar'] ?? '';
$role      = $_SESSION['urole'] ?? 'customer';

// Normalizar avatar Google (por si llega sin tamaño)
if ($avatarUrl && (parse_url($avatarUrl, PHP_URL_HOST) ?? '') === 'lh3.googleusercontent.com' && !preg_match('/=s\d+-c$/', $avatarUrl)) {
  $avatarUrl .= '=s96-c';
}

// Cargar metadata fresca del usuario (verificado/fechas)
$emailVerified = 1;
$createdAt     = null;
$lastLoginAt   = null;
try {
  $stmt = db()->prepare('SELECT email_verified, created_at, last_login_at, avatar_url FROM users WHERE id=? LIMIT 1');
  $stmt->execute([$uid]);
  if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $emailVerified = (int)($row['email_verified'] ?? 1);
    $createdAt     = $row['created_at'] ?? null;
    $lastLoginAt   = $row['last_login_at'] ?? null;
    if (!$avatarUrl && !empty($row['avatar_url'])) $avatarUrl = $row['avatar_url'];
  }
} catch (Throwable $e) {
  // silencioso en UI
}

// Helpers visuales
function fmt_dt($dt) {
  if (!$dt) return '—';
  $ts = strtotime($dt);
  if (!$ts) return '—';
  return date('d/m/Y H:i', $ts);
}
$verifiedBadge = $emailVerified ? '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] bg-emerald-500/15 text-emerald-300 border border-emerald-400/30">Verificado</span>'
                               : '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] bg-amber-500/15 text-amber-300 border border-amber-400/30">No verificado</span>';
$isAdmin = (function_exists('is_admin') && is_admin());
?>
<section class="min-h-[70vh] py-10">
  <div class="max-w-7xl mx-auto px-4 md:px-8">

    <!-- Encabezado de Perfil -->
    <div class="card p-6 md:p-8 mb-6">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div class="flex items-center gap-4">
          <?php if ($avatarUrl): ?>
            <img src="<?= e($avatarUrl) ?>" alt="Avatar" width="72" height="72" referrerpolicy="no-referrer"
                 class="w-16 h-16 md:w-18 md:h-18 rounded-full object-cover"
                 style="background: var(--surface-weak); border: 1px solid var(--border);" />
          <?php else: ?>
            <div class="w-16 h-16 md:w-18 md:h-18 rounded-full grid place-items-center text-xl font-semibold"
                 style="background: var(--surface-weak); border: 1px solid var(--border);">
              <?= e(mb_strtoupper(mb_substr($uname, 0, 1, 'UTF-8'))) ?>
            </div>
          <?php endif; ?>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-2xl md:text-3xl font-semibold leading-tight"><?= e($uname) ?></h1>
              <?php if ($isAdmin): ?>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] bg-indigo-500/15 text-indigo-200 border border-indigo-400/30">Admin</span>
              <?php endif; ?>
            </div>
            <div class="flex items-center gap-2 mt-1 text-sm text-[color:var(--muted)]">
              <span><?= e($email) ?></span>
              <span>•</span>
              <?= $verifiedBadge ?>
            </div>
            <div class="mt-1 text-xs text-[color:var(--muted)]/80">
              <span>Miembro desde <?= fmt_dt($createdAt) ?></span>
              <span class="mx-1">·</span>
              <span>Último acceso <?= fmt_dt($lastLoginAt) ?></span>
            </div>
          </div>
        </div>

        <!-- Acciones rápidas -->
        <div class="flex flex-wrap gap-2">
          <?php if ($isAdmin): ?>
            <a href="<?= url('/admin') ?>" class="btn-secondary">Ir al panel Admin</a>
          <?php endif; ?>
          <a href="<?= url('/productos') ?>" class="btn-secondary">Seguir comprando</a>
          <form method="post" action="<?= url('/logout') ?>">
            <?= csrf_field() ?>
            <button class="btn-danger">Cerrar sesión</button>
          </form>
        </div>
      </div>

      <?php if (!$emailVerified): ?>
        <div class="mt-6 rounded-[var(--radius-sm)] border border-amber-400/30 bg-amber-500/10 p-4 text-sm">
          <strong class="block mb-1">Verificá tu email</strong>
          <p class="opacity-80">Tu correo aún no está verificado. Pronto podrás reenviar el email de verificación desde acá.</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Contenido en tarjetas -->
    <div class="grid lg:grid-cols-3 gap-6">

      <!-- Columna 1: Pedidos -->
      <div class="card p-6 space-y-4">
        <h2 class="font-semibold text-lg">Mis pedidos</h2>
        <ul class="divide-y divide-[color:var(--border)]/50 text-sm">
          <li class="py-3 flex items-center justify-between">
            <span class="opacity-80">Último pedido</span><span class="font-medium">pronto</span>
          </li>
          <li class="py-3 flex items-center justify-between">
            <span class="opacity-80">Pedidos totales</span><span class="font-medium">pronto</span>
          </li>
          <li class="py-3 flex items-center justify-between">
            <span class="opacity-80">En curso</span><span class="font-medium">pronto</span>
          </li>
        </ul>
        <a href="<?= url('/pedidos') ?>" class="underline text-sm opacity-90 hover:opacity-100">Ver historial (pronto)</a>
      </div>

      <!-- Columna 2: Lealtad + Direcciones -->
      <div class="space-y-6">
        <div class="card p-6">
          <h2 class="font-semibold text-lg">Programa de lealtad</h2>
          <div class="mt-2 text-sm">
            <div class="flex items-center justify-between">
              <span class="opacity-80">Nivel actual</span>
              <span class="font-medium">Bronze</span>
            </div>
            <div class="mt-3 h-2 rounded-full bg-white/10 overflow-hidden">
              <div class="h-full" style="width: 25%; background: linear-gradient(90deg, var(--brand), #ffffff66)"></div>
            </div>
            <p class="mt-2 text-xs text-[color:var(--muted)]">Faltan <strong>pronto</strong> puntos para Silver.</p>
          </div>
        </div>

        <div class="card p-6">
          <h2 class="font-semibold text-lg">Direcciones</h2>
          <p class="text-sm opacity-80">Aún no tenés direcciones cargadas.</p>
          <div class="mt-3">
            <button class="btn-secondary opacity-80 pointer-events-none">Agregar dirección (pronto)</button>
          </div>
        </div>
      </div>

      <!-- Columna 3: Seguridad -->
      <div class="card p-6 space-y-4">
        <h2 class="font-semibold text-lg">Seguridad</h2>
        <div class="space-y-3 text-sm">
          <div class="flex items-center justify-between">
            <span class="opacity-80">Método de acceso</span>
            <span class="font-medium">
              <?php
              // Heurística suave: si avatar Google, probablemente OAuth; sino, email/clave
              echo (strpos($avatarUrl, 'lh3.googleusercontent.com') !== false) ? 'Google' : 'Email y clave';
              ?>
            </span>
          </div>
          <div class="flex items-center justify-between">
            <span class="opacity-80">Verificación de email</span>
            <span class="font-medium"><?= $emailVerified ? 'Sí' : 'No' ?></span>
          </div>
          <div class="flex items-center justify-between">
            <span class="opacity-80">Sesiones activas</span>
            <span class="font-medium">pronto</span>
          </div>
        </div>
        <div class="pt-2 grid gap-2">
          <button class="btn-secondary w-full opacity-80 pointer-events-none">Cambiar clave (pronto)</button>
          <form method="post" action="<?= url('/cuenta/sessions/clear') ?>" class="mt-2">
            <?= csrf_field() ?>
            <button class="btn-secondary w-full">Cerrar todas las sesiones</button>
          </form>

        </div>
      </div>

    </div>

    <!-- Actividad reciente -->
    <div class="card p-6 mt-6">
      <h2 class="font-semibold text-lg">Actividad reciente</h2>
      <div class="mt-3 text-sm text-[color:var(--muted)]">
        <p>Verás acá tus últimos movimientos (ingresos, cambios de datos, pedidos). <em>Pronto</em>.</p>
      </div>
    </div>

  </div>
</section>
