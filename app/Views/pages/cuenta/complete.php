<?php
/** @var array $steps */
/** @var array $nextStep */

$pageId = 'account-complete';
$pageTitle = 'Completar cuenta - Tienda Pesca';
$pageDesc = 'Checklist rápido para dejar tu cuenta lista para comprar.';

$steps = $steps ?? [];
$nextStep = $nextStep ?? null;

$nextHref = (string)($nextStep['href'] ?? base_url('/cuenta'));
$nextLabel = (string)($nextStep['label'] ?? 'Continuar');
$uname = $_SESSION['uname'] ?? 'Usuario';
$email = $_SESSION['email'] ?? '';
$avatarUrl = $_SESSION['uavatar'] ?? '';
if ($avatarUrl && (parse_url($avatarUrl, PHP_URL_HOST) ?? '') === 'lh3.googleusercontent.com'
    && !preg_match('/=s\d+-c$/', $avatarUrl)) {
    $avatarUrl .= '=s96-c';
}
$isAdmin = function_exists('is_admin') && is_admin();

$pendingCount = 0;
foreach ($steps as $step) {
    if (empty($step['done'])) {
        $pendingCount++;
    }
}

$pendingText = $pendingCount > 0 ? ('Pendientes: ' . $pendingCount) : 'Todo listo';
$pendingTone = $pendingCount > 0 ? 'warn' : 'ok';
?>

<section class="acct-page account-complete-page">
  <div class="acct-page__inner">
    <div class="acct-page__stack">
      <?php
      section('cuenta/hero', [
          'uname' => $uname,
          'email' => $email,
          'avatarUrl' => $avatarUrl,
          'isAdmin' => $isAdmin,
          'desc' => 'Gestioná tu cuenta y seguí tus compras.',
          'showActions' => true,
          'actionsClass' => 'account-orders-page__actions',
          'actions' => [
              ['href' => url('/cuenta'), 'label' => 'Volver a mi cuenta', 'class' => 'v2-btn v2-btn--ghost'],
              ['href' => $nextHref, 'label' => $nextLabel, 'class' => 'v2-btn v2-btn--primary'],
          ],
      ]);
      ?>

      <section class="account-complete-card">
        <header class="account-complete-card__head">
          <div class="account-complete-card__head-copy">
            <h2 class="account-complete-card__title">Pasos recomendados</h2>
            <p class="account-complete-card__intro">Podés hacerlos en cualquier orden. Si falta algo clave, te lo marcamos acá.</p>
          </div>
        </header>

        <div class="account-complete-card__body">
          <div class="account-complete-steps">
            <?php foreach ($steps as $step): ?>
              <?php
                $done = !empty($step['done']);
                $href = (string)($step['href'] ?? base_url('/cuenta'));
                $label = (string)($step['label'] ?? ($done ? 'Listo' : 'Completar'));
                $stepTone = $done ? 'ok' : 'warn';
                $stepText = $done ? 'Completo' : 'Pendiente';
              ?>
              <article class="account-complete-step">
                <div class="account-complete-step__main">
                  <div class="account-complete-step__top">
                    <h3 class="account-complete-step__title"><?= e((string)($step['title'] ?? '')) ?></h3>
                    <span class="account-complete-badge account-complete-badge--<?= e($stepTone) ?>"><?= e($stepText) ?></span>
                  </div>

                  <?php if (!empty($step['desc'])): ?>
                    <p class="account-complete-step__desc"><?= e((string)$step['desc']) ?></p>
                  <?php endif; ?>
                </div>

                <div class="account-complete-step__actions">
                  <a href="<?= e($href) ?>" class="v2-btn v2-btn--ghost"><?= e($label) ?></a>
                </div>
              </article>
            <?php endforeach; ?>
          </div>

          <p class="account-complete-note">
            Tip: La dirección y el teléfono ayudan a que el checkout sea más rápido. Los puntos del programa de lealtad se acreditan cuando el pago queda confirmado.
          </p>
        </div>
      </section>

    </div>
  </div>
</section>




