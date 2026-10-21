<?php
/** @var string $active */
$active = (string)($active ?? '');
/** @var string $uname */
$uname = (string)($uname ?? 'Usuario');
/** @var string $email */
$email = (string)($email ?? '');
/** @var string $avatarUrl */
$avatarUrl = (string)($avatarUrl ?? '');
$avatarFallbackUrl = (string)asset('img/account/avatar-profile.jpg');
/** @var string $whatsUrl */
$whatsUrl = (string)($whatsUrl ?? '');
/** @var bool $isAdmin */
$isAdmin = !empty($isAdmin);

$items = [
    ['key' => 'pedidos', 'href' => url('/cuenta/pedidos'), 'icon' => 'box', 'label' => 'Mis pedidos'],
    ['key' => 'direcciones', 'href' => url('/cuenta/direcciones'), 'icon' => 'house', 'label' => 'Direcciones'],
    ['key' => 'favoritos', 'href' => url('/cuenta/favoritos'), 'icon' => 'heart', 'label' => 'Favoritos'],
    ['key' => 'perfil', 'href' => url('/cuenta/perfil'), 'icon' => 'user', 'label' => 'Datos personales'],
    ['key' => 'lealtad', 'href' => url('/cuenta/lealtad'), 'icon' => 'star-solid', 'label' => 'Programa lealtad'],
    ['key' => 'seguridad', 'href' => url('/cuenta/seguridad'), 'icon' => 'shield-solid', 'label' => 'Seguridad'],
];
?>

<aside class="acct-side" aria-label="Navegacion de cuenta">
  <section class="acct-side__card">
    <header class="acct-side__profile">
      <a class="acct-side__avatar" href="../cuenta">
        <?php if ($avatarUrl !== ''): ?>
          <img src="<?= e($avatarUrl) ?>" alt="Avatar de <?= e($uname) ?>">
        <?php else: ?>
          <img src="<?= e($avatarFallbackUrl) ?>" alt="Avatar por defecto" loading="lazy" decoding="async">
        <?php endif; ?>
      </a>
      <div class="acct-side__copy">
        <h2 class="acct-side__name"><?= e($uname) ?></h2>
        <p class="acct-side__email"><?= e($email) ?></p>
        <div class="acct-side__chips">
          <span class="acct-chip acct-chip--tier"><?= ui_icon('loyalty-bronze-solid') ?><span>Bronce</span></span>
          <?php if ($isAdmin): ?><span class="acct-chip acct-chip--admin">Admin</span><?php endif; ?>
        </div>
      </div>
    </header>

    <nav class="acct-side__nav">
      <?php foreach ($items as $item): ?>
        <a
          href="<?= e((string)$item['href']) ?>"
          class="acct-side__nav-item<?= $active === (string)$item['key'] ? ' is-active' : '' ?>"
          <?= $active === (string)$item['key'] ? 'aria-current="page"' : '' ?>
        >
          <span class="acct-side__nav-icon" aria-hidden="true"><?= ui_icon((string)$item['icon']) ?></span>
          <span><?= e((string)$item['label']) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>

    <article class="acct-side__help">
      <div class="acct-side__help-icon" aria-hidden="true"><?= ui_icon('phone') ?></div>
      <div class="acct-side__help-copy">
        <h3>¿Necesitás ayuda?</h3>
        <p>Nuestro equipo esta para ayudarte</p>
      </div>
      <a
        href="<?= e($whatsUrl !== '' ? $whatsUrl : url('/ayuda')) ?>"
        class="acct-side__help-cta"
        <?= $whatsUrl !== '' ? 'target="_blank" rel="noopener noreferrer"' : '' ?>
      >
        <span aria-hidden="true"><?= ui_icon('whatsapp-brand') ?></span>
        <span>Contactar soporte</span>
      </a>
    </article>
  </section>
</aside>

