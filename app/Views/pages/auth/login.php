<?php
use App\Services\Auth;

$pageTitle = 'Iniciar sesión — Tienda Pesca';
$pageDesc  = 'Accedé a tu cuenta para ver pedidos y comprar más rápido.';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limit
    rate_limit_or_fail('login:ip:'.client_ip(), 5, 60); // 5/min por IP
    $email = trim($_POST['email'] ?? '');
    if ($email !== '') {
      rate_limit_or_fail('login:email:'.mb_strtolower($email), 2, 300); // 2/5min por email (ajustá si querés)
    }

    // Intento de login
    [$ok, $errors] = Auth::attemptFromPost();

    if ($ok) {
      // "Recordarme" (30 días)
      if (!empty($_POST['remember'])) {
        set_remember_me((int)$_SESSION['uid'], 30);
      }
      redirect(Auth::intended('/cuenta'));
    }
}
?>

<section class="min-h-[78vh] flex items-center justify-center py-10">
  <div class="w-full max-w-5xl mx-auto px-4">
    <div class="grid md:grid-cols-5 gap-6 items-stretch">
      <!-- Lateral -->
      <aside class="md:col-span-2 hidden md:flex">
        <div class="card p-8 w-full relative overflow-hidden">
          <div class="absolute -top-24 -right-24 w-72 h-72 rounded-full opacity-20"
               style="background: radial-gradient(closest-side, var(--brand), transparent 70%);"></div>
          <header class="mb-6">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand/10 border border-brand/30 text-sm">
              Acceso seguro
            </span>
            <h2 class="text-2xl font-semibold mt-4 leading-tight">Iniciá sesión</h2>
            <p class="text-sm text-[color:var(--muted)] mt-2">Compras y seguimiento en un solo lugar.</p>
          </header>
          <p class="text-xs text-[color:var(--muted)]">¿No tenés cuenta?
            <a class="underline" href="<?= url('/registro') ?>">Crear una</a>.
          </p>
        </div>
      </aside>

      <!-- Form -->
      <div class="md:col-span-3">
        <div class="card p-6 sm:p-8">
          <header class="mb-6">
            <h1 class="text-3xl font-semibold">Iniciar sesión</h1>
            <p class="text-sm text-[color:var(--muted)] mt-1">Bienvenido/a de vuelta.</p>
          </header>

          <?php if ($errors): ?>
            <div class="mb-4 rounded-[var(--radius-xs)] border border-[color:var(--danger)]/40 bg-[color:var(--danger)]/10 px-4 py-3">
              <ul class="list-disc pl-6 space-y-1">
                <?php foreach($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <form method="post" class="space-y-5" novalidate>
            <?= csrf_field() ?>
            <div>
              <label class="block text-sm mb-1">Email</label>
              <input class="input w-full" type="email" name="email" value="<?= old('email') ?>" autocomplete="email" required autofocus>
            </div>
            <div>
              <label class="block text-sm mb-1">Clave</label>
              <input class="input w-full" type="password" name="password" autocomplete="current-password" required>
              <div class="flex justify-between items-center mt-1 text-xs">
                <label class="inline-flex items-center gap-2">
                  <input class="accent-[var(--brand)]" type="checkbox" name="remember" value="1">
                  Recordarme
                </label>
                <a class="underline opacity-70 pointer-events-none">¿Olvidaste tu clave? (pronto)</a>
              </div>
            </div>

            <button class="btn-brand w-full h-11 text-base">Ingresar</button>

            <!-- Botón Google -->
            <div class="mt-6">
              <div id="google-btn-container" style="min-height:48px"></div>
              <p class="text-xs text-center text-[color:var(--muted)] mt-2">o</p>
            </div>
          </form>

          <p class="text-xs text-center text-[color:var(--muted)] mt-4">
            ¿No tenés cuenta? <a class="underline" href="<?= url('/registro') ?>">Crear una</a>
          </p>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- 1) SDK de Google (GIS) -->
<script src="https://accounts.google.com/gsi/client" async defer></script>

<!-- 2) Variables globales inicializador -->
<script>
  window.GOOGLE_CLIENT_ID = "<?= e(env('GOOGLE_CLIENT_ID','')) ?>";
  window.APP_BASE_URL     = "<?= rtrim(base_url(''), '/') ?>";
</script>

<!-- 3) inicializador -->
<script src="<?= asset('js/auth-google.js') ?>" defer></script>
