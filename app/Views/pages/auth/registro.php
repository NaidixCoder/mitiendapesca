<?php
// SEO
$pageTitle = 'Crear cuenta — Tienda Pesca';
$pageDesc  = 'Registrate para comprar más rápido y ver tu historial.';

// --- Lógica POST ---
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verify_csrf()) $errors[] = 'Token inválido. Refrescá la página.';

  $name  = trim($_POST['name']  ?? '');
  $email = trim($_POST['email'] ?? '');
  $pass  = $_POST['password']   ?? '';
  $pass2 = $_POST['password2']  ?? '';

  if ($name === '')                                   $errors[]='Ingresá tu nombre.';
  if (!filter_var($email, FILTER_VALIDATE_EMAIL))     $errors[]='Email inválido.';
  if (strlen($pass) < 8)                               $errors[]='La clave debe tener al menos 8 caracteres.';
  if ($pass !== $pass2)                                $errors[]='Las claves no coinciden.';

  if (!$errors) {
    try {
      $pdo = db();
      // ¿email ya usado?
      $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
      $stmt->execute([$email]);
      if ($stmt->fetch()) {
        $errors[] = 'Ese email ya está registrado.';
      } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $ins  = $pdo->prepare('INSERT INTO users (name, email, password_hash, email_verified, created_at) VALUES (?,?,?,?,NOW())');
        $ins->execute([$name, $email, $hash, 0]); // email_verified=0 (podemos habilitar verificación después)
        session_regenerate_id(true);
        $_SESSION['uid']   = (int)$pdo->lastInsertId();
        $_SESSION['uname'] = $name;
        $_SESSION['email'] = $email;
        flash('ok', '¡Cuenta creada! Bienvenido/a.');
        redirect('/cuenta');
      }
    } catch (Throwable $e) {
      error_log('[registro] '.$e->getMessage());
      $errors[] = 'Error de servidor. Intentá más tarde.';
    }
  }
}
?>

<section class="min-h-[78vh] flex items-center justify-center py-10">
  <div class="w-full max-w-5xl mx-auto px-4">
    <div class="grid md:grid-cols-5 gap-6 items-stretch">

      <aside class="md:col-span-2 hidden md:flex">
        <div class="card p-8 w-full relative overflow-hidden">
          <div class="absolute -top-24 -right-24 w-72 h-72 rounded-full opacity-20"
               style="background: radial-gradient(closest-side, var(--brand), transparent 70%);"></div>
          <header class="mb-6">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand/10 border border-brand/30 text-sm">
              Cuenta gratuita
            </span>
            <h2 class="text-2xl font-semibold mt-4 leading-tight">Registrate y comprá más rápido</h2>
            <p class="text-sm text-[color:var(--muted)] mt-2">Guardá direcciones y seguí tus pedidos.</p>
          </header>
          <ul class="space-y-3 text-sm">
            <li class="flex items-start gap-3"><svg class="mt-1" width="18" height="18" viewBox="0 0 24 24"><path fill="currentColor" d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>Checkout más rápido.</li>
            <li class="flex items-start gap-3"><svg class="mt-1" width="18" height="18" viewBox="0 0 24 24"><path fill="currentColor" d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>Historial y seguimiento de pedidos.</li>
          </ul>
        </div>
      </aside>

      <div class="md:col-span-3">
        <div class="card p-6 sm:p-8">
          <header class="mb-6">
            <h1 class="text-3xl font-semibold">Crear cuenta</h1>
            <p class="text-sm text-[color:var(--muted)] mt-1">Completá tus datos para empezar.</p>
          </header>

          <?php if ($msg = flash('ok')): ?>
            <div class="mb-4 rounded-[var(--radius-xs)] border border-green-500/40 bg-green-500/10 px-4 py-3"><?= e($msg) ?></div>
          <?php endif; ?>

          <?php if ($errors): ?>
            <div class="mb-4 rounded-[var(--radius-xs)] border border-[color:var(--danger)]/40 bg-[color:var(--danger)]/10 px-4 py-3">
              <ul class="list-disc pl-6 space-y-1"><?php foreach($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
            </div>
          <?php endif; ?>

          <form method="post" class="space-y-5" novalidate>
            <?= csrf_field() ?>
            <div>
              <label class="block text-sm mb-1">Nombre y apellido</label>
              <input class="input w-full" type="text" name="name" value="<?= old('name') ?>" autocomplete="name" required autofocus>
            </div>
            <div>
              <label class="block text-sm mb-1">Email</label>
              <input class="input w-full" type="email" name="email" value="<?= old('email') ?>" autocomplete="email" required>
            </div>
            <div class="grid md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm mb-1">Clave</label>
                <input class="input w-full" type="password" name="password" required minlength="8" autocomplete="new-password" placeholder="Mín. 8 caracteres">
              </div>
              <div>
                <label class="block text-sm mb-1">Repetir clave</label>
                <input class="input w-full" type="password" name="password2" required minlength="8" autocomplete="new-password">
              </div>
            </div>
            <div class="flex items-start gap-3 text-sm">
              <input id="terms" type="checkbox" name="terms" required class="mt-1 accent-[var(--brand)]">
              <label for="terms">Acepto los <a class="underline" href="<?= url('/terminos') ?>">Términos</a> y la <a class="underline" href="<?= url('/privacidad') ?>">Privacidad</a>.</label>
            </div>
            <button class="btn-brand w-full h-11 text-base">Crear cuenta</button>
            <p class="text-xs text-center text-[color:var(--muted)] mt-3">¿Ya tenés cuenta? <a class="underline" href="<?= url('/login') ?>">Iniciá sesión</a></p>
          </form>
        </div>
      </div>

    </div>
  </div>
</section>
