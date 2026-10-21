<?php
/** @var string $uname */
$uname = (string)($uname ?? 'Usuario');
/** @var string $email */
$email = (string)($email ?? '');
/** @var string $avatarUrl */
$avatarUrl = (string)($avatarUrl ?? '');
$avatarFallbackUrl = (string)asset('img/account/avatar-profile.jpg');
/** @var bool $isAdmin */
$isAdmin = !empty($isAdmin);
/** @var string $desc */
$desc = (string)($desc ?? 'Gestioná tu cuenta y seguí tus compras.');
/** @var bool $showHint */
$showHint = !empty($showHint);
/** @var string $hintHref */
$hintHref = (string)($hintHref ?? '');
/** @var bool $hintShowSuccess */
$hintShowSuccess = !empty($hintShowSuccess);
/** @var string $bannerStatus */
$bannerStatus = (string)($bannerStatus ?? '');
/** @var bool $showQuick */
$showQuick = !empty($showQuick);
/** @var array<int,array{href:string,icon:string,label:string,copy?:string,cta?:string}> $quickItems */
$quickItems = is_array($quickItems ?? null) ? $quickItems : [];
/** @var bool $showActions */
$showActions = !empty($showActions);
/** @var array<int,array{href:string,label:string,class?:string}> $actions */
$actions = is_array($actions ?? null) ? $actions : [];
/** @var string $actionsClass */
$actionsClass = (string)($actionsClass ?? '');
/** @var bool $showVerifyBanner */
$showVerifyBanner = !empty($showVerifyBanner);
/** @var string $verifyTitle */
$verifyTitle = (string)($verifyTitle ?? 'Tu email no está verificado');
/** @var string $verifyText */
$verifyText = (string)($verifyText ?? 'Revisa tu casilla para confirmar tu cuenta y acceder a todos los beneficios.');
/** @var string $verifyAction */
$verifyAction = (string)($verifyAction ?? base_url('/verificar-email/reenviar'));
/** @var string $verifyCta */
$verifyCta = (string)($verifyCta ?? 'Reenviar email');
?>

<article class="acct-hero">
  <div class="acct-hero__media" style="--acct-hero-banner: url('<?= e(base_url('/assets/img/account/bannerAccount.png')) ?>');">
    <div class="acct-hero__cover" aria-hidden="true"></div>
    <div class="acct-hero__overlay"></div>
    <div class="acct-hero__content">
      <div class="acct-hero__profile">
        <div class="acct-hero__avatar">
          <?php if ($avatarUrl): ?>
            <img src="<?= e($avatarUrl) ?>" alt="Avatar de <?= e($uname) ?>">
          <?php else: ?>
            <img src="<?= e($avatarFallbackUrl) ?>" alt="Avatar por defecto" loading="lazy" decoding="async">
          <?php endif; ?>
        </div>
        <div class="acct-hero__copy">
          <h1 class="acct-hero__name"><?= e($uname) ?></h1>
          <p class="acct-hero__email"><?= e($email) ?></p>
          <div class="acct-hero__chips">
            <span class="acct-chip acct-chip--tier"><?= ui_icon('loyalty-bronze-solid') ?><span>Bronce</span></span>
            <?php if ($isAdmin): ?><span class="acct-chip acct-chip--admin">Admin</span><?php endif; ?>
          </div>
        </div>
      </div>
      <p class="acct-hero__desc"><?= e($desc) ?></p>
    </div>
    <div class="acct-hero__wave" aria-hidden="true">
      <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
        <path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" opacity=".25"></path>
        <path d="M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z" opacity=".5"></path>
        <path d="M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z"></path>
      </svg>
    </div>
  </div>

  <?php if ($showVerifyBanner): ?>
    <div class="acct-verify-wrap" data-account-email-verification>
      <div class="acct-verify-alert" role="alert">
        <div class="acct-verify-header">
          <div class="acct-verify-icon" aria-hidden="true">
            <?= ui_icon('email-1573-svgrepo-com') ?>
          </div>
          <p class="acct-verify-title"><?= e($verifyTitle) ?></p>
        </div>

        <div class="acct-verify-text">
          <p class="acct-verify-subtext"><?= e($verifyText) ?></p>

          <form method="POST" action="<?= e($verifyAction) ?>" class="acct-verify-form">
            <?= csrf_field() ?>
            <button type="submit" class="acct-verify-btn">
              <span><?= e($verifyCta) ?></span>
              <span class="acct-verify-btn-icon" aria-hidden="true"><?= ui_icon('chevron-right-solid') ?></span>
            </button>
          </form>
        </div>


      </div>
    </div>
  <?php endif; ?>

  <?php if ($showHint && !$showVerifyBanner): ?>
    <a href="<?= e($hintHref) ?>" class="acct-hero__hint">
      <?php if ($hintShowSuccess): ?><span class="acct-hero__hint-icon" aria-hidden="true"><?= ui_icon('circle-check-solid') ?></span><?php endif; ?>
      <span><?= e($bannerStatus) ?></span>
    </a>
  <?php endif; ?>

  <?php if ($showQuick): ?>
    <div class="acct-hero__quick-wrap">
      <nav class="acct-quick" aria-label="Accesos rápidos">
        <?php foreach ($quickItems as $quick): ?>
          <?php
          $desktopMeta = is_array($quick['desktop_meta'] ?? null) ? $quick['desktop_meta'] : [];
          $desktopType = (string)($desktopMeta['type'] ?? '');
          ?>
          <a href="<?= e((string)($quick['href'] ?? '#')) ?>" class="acct-quick__item">
            <span class="acct-quick__head">
              <span class="acct-quick__icon" aria-hidden="true">
                <?= ui_icon((string)($quick['icon'] ?? 'box')) ?>
              </span>
              <span class="acct-quick__intro">
                <span class="acct-quick__label"><?= e((string)($quick['label'] ?? '')) ?></span>
                <?php if (!empty($quick['copy'])): ?>
                  <span class="acct-quick__copy"><?= e((string)$quick['copy']) ?></span>
                <?php endif; ?>
              </span>
            </span>

            <span class="acct-quick__divider" aria-hidden="true"></span>

            <?php if ($desktopType === 'summary'): ?>
              <span class="acct-quick__meta acct-quick__meta--summary">
                <span class="acct-quick__meta-title"><?= e((string)($desktopMeta['title'] ?? '')) ?></span>
                <span class="acct-quick__meta-text"><?= e((string)($desktopMeta['text'] ?? '')) ?></span>
              </span>
            <?php elseif ($desktopType === 'pair'): ?>
              <?php
              $hasRightPair = trim((string)($desktopMeta['right_label'] ?? '')) !== ''
                || trim((string)($desktopMeta['right_value'] ?? '')) !== '';
              ?>
              <span class="acct-quick__meta acct-quick__meta--pair<?= $hasRightPair ?'' : ' acct-quick__meta--pair-single' ?>">
                <span class="acct-quick__pair-col">
                  <span class="acct-quick__pair-label"><?= e((string)($desktopMeta['left_label'] ?? '')) ?></span>
                  <?php
                  $leftTone = (string)($desktopMeta['left_badge_tone'] ?? 'neutral');
                  $leftValue = (string)($desktopMeta['left_value'] ?? '');
                  ?>
                  <?php if (!empty($desktopMeta['left_badge'])): ?>
                    <span class="acct-quick__badge acct-quick__badge--<?= e($leftTone) ?>"><?= e($leftValue) ?></span>
                  <?php else: ?>
                    <span class="acct-quick__pair-value"><?= e($leftValue) ?></span>
                  <?php endif; ?>
                </span>
                <?php if ($hasRightPair): ?>
                  <span class="acct-quick__pair-sep" aria-hidden="true"></span>
                  <span class="acct-quick__pair-col">
                    <span class="acct-quick__pair-label"><?= e((string)($desktopMeta['right_label'] ?? '')) ?></span>
                    <?php
                    $rightTone = (string)($desktopMeta['right_badge_tone'] ?? 'neutral');
                    $rightValue = (string)($desktopMeta['right_value'] ?? '');
                    ?>
                    <?php if (!empty($desktopMeta['right_badge'])): ?>
                      <span class="acct-quick__badge acct-quick__badge--<?= e($rightTone) ?>"><?= e($rightValue) ?></span>
                    <?php else: ?>
                      <span class="acct-quick__pair-value"><?= e($rightValue) ?></span>
                    <?php endif; ?>
                  </span>
                <?php endif; ?>
              </span>
            <?php elseif ($desktopType === 'progress'): ?>
              <?php
              $points = (int)($desktopMeta['points'] ?? 0);
              $nextTier = (string)($desktopMeta['next_tier'] ?? 'Plata');
              $toNext = (int)($desktopMeta['to_next_tier'] ?? 0);
              $pct = max(0, min(100, (int)($desktopMeta['progress_pct'] ?? 0)));
              ?>
              <span class="acct-quick__meta acct-quick__meta--progress">
                <span class="acct-quick__progress-head">
                  <span class="acct-quick__progress-col">
                    <span class="acct-quick__meta-title"><?= e((string)$points) ?> puntos</span>
                    <span class="acct-quick__meta-text">Acumulados</span>
                  </span>
                  <span class="acct-quick__progress-col acct-quick__progress-col--end">
                    <span class="acct-quick__pair-label">Próximo nivel</span>
                    <span class="acct-quick__pair-value"><?= e($nextTier) ?></span>
                  </span>
                </span>
                <span class="acct-quick__progress-track" aria-hidden="true">
                  <span class="acct-quick__progress-fill" style="width: <?= e((string)$pct) ?>%;"></span>
                </span>
                <span class="acct-quick__progress-note"><?= e((string)$toNext) ?> puntos para alcanzar <?= e($nextTier) ?></span>
              </span>
            <?php endif; ?>

            <?php if (!empty($quick['cta'])): ?>
              <span class="acct-quick__cta">
                <span><?= e((string)$quick['cta']) ?></span>
                <span class="acct-quick__cta-icon" aria-hidden="true"><?= ui_icon('chevron-right-solid') ?></span>
              </span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </nav>
    </div>
  <?php endif; ?>

  <?php if ($showActions): ?>
    <div class="<?= e($actionsClass) ?>">
      <?php foreach ($actions as $action): ?>
        <a class="<?= e((string)($action['class'] ?? 'v2-btn v2-btn--ghost')) ?>" href="<?= e((string)($action['href'] ?? '#')) ?>">
          <?= e((string)($action['label'] ?? '')) ?>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</article>
