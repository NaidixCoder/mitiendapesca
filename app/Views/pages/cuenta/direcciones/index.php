<?php
$pageTitle = 'Direcciones - Tienda Pesca';
$pageDesc  = 'Gestiona tus direcciones para un checkout mas rapido.';

$addresses = $addresses ?? [];
$hasAddresses = count($addresses) > 0;
$addressCount = count($addresses);

$uname = $_SESSION['uname'] ?? 'Usuario';
$email = $_SESSION['email'] ?? '';
$avatarUrl = $_SESSION['uavatar'] ?? '';
if ($avatarUrl && (parse_url($avatarUrl, PHP_URL_HOST) ?? '') === 'lh3.googleusercontent.com'
    && !preg_match('/=s\d+-c$/', $avatarUrl)) {
    $avatarUrl .= '=s96-c';
}
$isAdmin = function_exists('is_admin') && is_admin();
$whatsUrl = trim((string)app_setting('social_whatsapp_url', env('CONTACT_WHATSAPP_URL', '')));
?>

<section class="acct-page account-addresses-page">
  <div class="acct-page__inner">
    <div class="acct-page__stack">

      <?php
      section('cuenta/hero', [
          'uname' => $uname,
          'email' => $email,
          'avatarUrl' => $avatarUrl,
          'isAdmin' => $isAdmin,
          'desc' => 'Gestiona tu cuenta y segui tus compras.',
          'showActions' => true,
          'actionsClass' => 'account-orders-page__actions',
          'actions' => [
              ['href' => url('/cuenta'), 'label' => 'Volver a mi cuenta', 'class' => 'v2-btn v2-btn--ghost'],
              ['href' => url('/productos'), 'label' => 'Explorar productos', 'class' => 'v2-btn v2-btn--primary'],
          ],
      ]);
      ?>

      <div class="account-orders-layout">
        <div class="account-orders-layout__aside">
          <?php
          section('cuenta/aside', [
              'active' => 'direcciones',
              'uname' => $uname,
              'email' => $email,
              'avatarUrl' => $avatarUrl,
              'isAdmin' => $isAdmin,
              'whatsUrl' => $whatsUrl,
          ]);
          ?>
        </div>

        <div class="account-layout-main">
          <section class="account-addresses-block">
            <header class="account-addresses-block__head">
              <h2 class="account-addresses-block__title">Mis direcciones</h2>
              <p class="account-addresses-block__intro">Tus direcciones siempre listas para tus compras</p>
            </header>

            <?php if (!$hasAddresses): ?>
              <div class="account-addresses-empty-state">
                <div class="account-addresses-empty-state__illustration" aria-hidden="true">
                  <svg viewBox="0 0 240 180" class="account-addresses-empty-state__art">
                    <defs>
                      <linearGradient id="accountAddressHouseEmpty" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#8BE1C8" />
                        <stop offset="100%" stop-color="#49C0A7" />
                      </linearGradient>
                      <linearGradient id="accountAddressRoofEmpty" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#0AA889" />
                        <stop offset="100%" stop-color="#097C71" />
                      </linearGradient>
                    </defs>
                    <ellipse cx="120" cy="146" rx="82" ry="18" fill="#E7F5F2" />
                    <path d="M56 128c-14-12-23-30-23-50 0-38 31-69 69-69 27 0 50 14 62 36 5-2 11-3 17-3 24 0 43 19 43 43 0 12-5 23-13 31 6 7 10 16 10 27H56c0-6 0-10 0-15z" fill="#F6FBFA" />
                    <circle cx="133" cy="44" r="15" fill="#FDB84F" />
                    <path d="M133 24c-10 0-19 8-19 18 0 13 19 30 19 30s19-17 19-30c0-10-9-18-19-18zm0 25a7 7 0 1 1 0-14 7 7 0 0 1 0 14z" fill="#FDB84F" />
                    <path d="M70 103l50-39 50 39c3 2 7 2 10-1 2-3 2-7-1-10L124 49c-2-2-6-2-8 0L61 92c-3 3-3 7-1 10 3 3 7 3 10 1z" fill="url(#accountAddressRoofEmpty)" />
                    <rect x="82" y="98" width="76" height="48" rx="4" fill="url(#accountAddressHouseEmpty)" />
                    <rect x="112" y="111" width="17" height="35" rx="6" fill="#16586A" />
                    <rect x="118" y="112" width="5" height="12" rx="2.5" fill="#0E7A83" />
                    <rect x="114" y="91" width="11" height="10" rx="4" fill="#F0FCF8" />
                    <path d="M56 144c0-12 10-22 22-22 8 0 15 4 19 11 3-2 7-4 12-4 10 0 18 8 18 18H56v-3z" fill="#BCEBE3" />
                    <path d="M146 146c0-11 9-20 20-20 7 0 13 3 17 9 3-2 6-3 10-3 9 0 16 7 16 16h-63v-2z" fill="#A7DED4" />
                  </svg>
                </div>

                <div class="account-addresses-empty-state__content">
                  <h3 class="account-addresses-empty-state__title">Agrega tu primera direccion de entrega</h3>
                  <p class="account-addresses-empty-state__text">Necesitamos una direccion para enviar tu pedido.</p>
                </div>

                <div class="account-addresses-empty-state__actions">
                  <a href="<?= e(base_url('/cuenta/direcciones/nueva')) ?>" class="v2-btn v2-btn--primary account-addresses-empty-state__cta">
                    + Agregar direccion
                  </a>
                </div>
              </div>
            <?php elseif ($addressCount === 1): ?>
              <?php $a = $addresses[0]; ?>
              <div class="account-addresses-single-grid">
                <article class="account-addresses-single-card">
                  <header class="account-addresses-single-card__head<?= !empty($a['is_default']) ? ' account-addresses-single-card__head--default' : '' ?>">
                    <div class="account-addresses-single-card__head-main">
                      <span class="account-addresses-single-card__home" aria-hidden="true"><?= ui_icon('house') ?></span>
                      <h3 class="account-addresses-single-card__title"><?= !empty($a['is_default']) ? 'Direccion principal' : (($a['label'] ?? '') ?: 'Direccion') ?></h3>
                      <?php if (!empty($a['is_default'])): ?>
                      <?php endif; ?>
                    </div>
                    <a href="<?= e(base_url('/cuenta/direcciones/editar?id=' . (int)$a['id'])) ?>" class="account-addresses-single-card__edit">
                      <span aria-hidden="true"><?= ui_icon('note-edit') ?></span>
                      <span>Editar</span>
                    </a>
                  </header>

                  <div class="account-addresses-single-card__body">
                    <div class="account-addresses-single-card__row">
                      <span class="account-addresses-single-card__icon" aria-hidden="true"><?= ui_icon('user') ?></span>
                      <div class="account-addresses-single-card__copy">
                        <?php if (!empty($a['full_name'])): ?>
                          <div class="account-addresses-single-card__primary"><?= e((string)$a['full_name']) ?></div>
                        <?php endif; ?>
                        <div class="account-addresses-single-card__secondary"><?= e(((string)($a['street'] ?? '')) . ' ' . ((string)($a['number'] ?? ''))) ?></div>
                      </div>
                    </div>

                    <div class="account-addresses-single-card__row">
                      <span class="account-addresses-single-card__icon" aria-hidden="true"><?= ui_icon('pin') ?></span>
                      <div class="account-addresses-single-card__copy">
                        <div class="account-addresses-single-card__primary"><?= e(((string)($a['city'] ?? '')) . ', ' . ((string)($a['state'] ?? '')) . ' (' . ((string)($a['zip'] ?? '')) . ')') ?></div>
                        <div class="account-addresses-single-card__secondary">Argentina</div>
                      </div>
                    </div>

                    <?php if (!empty($a['phone'])): ?>
                      <div class="account-addresses-single-card__row">
                        <span class="account-addresses-single-card__icon" aria-hidden="true"><?= ui_icon('phone') ?></span>
                        <div class="account-addresses-single-card__copy">
                          <div class="account-addresses-single-card__primary"><?= e((string)$a['phone']) ?></div>
                        </div>
                      </div>
                    <?php endif; ?>

                    <?php if (!empty($a['notes'])): ?>
                      <div class="account-addresses-single-card__row">
                        <span class="account-addresses-single-card__icon" aria-hidden="true"><?= ui_icon('document') ?></span>
                        <div class="account-addresses-single-card__copy">
                          <div class="account-addresses-single-card__primary">Nota de entrega</div>
                          <div class="account-addresses-single-card__secondary"><?= e((string)$a['notes']) ?></div>
                        </div>
                      </div>
                    <?php endif; ?>
                  </div>
                </article>

                <div class="account-addresses-empty-state">
                  <div class="account-addresses-empty-state__illustration" aria-hidden="true">
                    <svg viewBox="0 0 240 180" class="account-addresses-empty-state__art">
                      <defs>
                        <linearGradient id="accountAddressHouseEmptySingle" x1="0%" y1="0%" x2="100%" y2="100%">
                          <stop offset="0%" stop-color="#8BE1C8" />
                          <stop offset="100%" stop-color="#49C0A7" />
                        </linearGradient>
                        <linearGradient id="accountAddressRoofEmptySingle" x1="0%" y1="0%" x2="100%" y2="100%">
                          <stop offset="0%" stop-color="#0AA889" />
                          <stop offset="100%" stop-color="#097C71" />
                        </linearGradient>
                      </defs>
                      <ellipse cx="120" cy="146" rx="82" ry="18" fill="#E7F5F2" />
                      <path d="M56 128c-14-12-23-30-23-50 0-38 31-69 69-69 27 0 50 14 62 36 5-2 11-3 17-3 24 0 43 19 43 43 0 12-5 23-13 31 6 7 10 16 10 27H56c0-6 0-10 0-15z" fill="#F6FBFA" />
                      <circle cx="133" cy="44" r="15" fill="#FDB84F" />
                      <path d="M133 24c-10 0-19 8-19 18 0 13 19 30 19 30s19-17 19-30c0-10-9-18-19-18zm0 25a7 7 0 1 1 0-14 7 7 0 0 1 0 14z" fill="#FDB84F" />
                      <path d="M70 103l50-39 50 39c3 2 7 2 10-1 2-3 2-7-1-10L124 49c-2-2-6-2-8 0L61 92c-3 3-3 7-1 10 3 3 7 3 10 1z" fill="url(#accountAddressRoofEmptySingle)" />
                      <rect x="82" y="98" width="76" height="48" rx="4" fill="url(#accountAddressHouseEmptySingle)" />
                      <rect x="112" y="111" width="17" height="35" rx="6" fill="#16586A" />
                      <rect x="118" y="112" width="5" height="12" rx="2.5" fill="#0E7A83" />
                      <rect x="114" y="91" width="11" height="10" rx="4" fill="#F0FCF8" />
                      <path d="M56 144c0-12 10-22 22-22 8 0 15 4 19 11 3-2 7-4 12-4 10 0 18 8 18 18H56v-3z" fill="#BCEBE3" />
                      <path d="M146 146c0-11 9-20 20-20 7 0 13 3 17 9 3-2 6-3 10-3 9 0 16 7 16 16h-63v-2z" fill="#A7DED4" />
                    </svg>
                  </div>

                  <div class="account-addresses-empty-state__content">
                    <h3 class="account-addresses-empty-state__title">Agrega otra direccion de entrega</h3>
                    <p class="account-addresses-empty-state__text">Guarda mas lugares de entrega y elegi rapido al finalizar tu compra.</p>
                  </div>

                  <div class="account-addresses-empty-state__actions">
                    <a href="<?= e(base_url('/cuenta/direcciones/nueva')) ?>" class="v2-btn v2-btn--primary account-addresses-empty-state__cta">
                      + Agregar direccion
                    </a>
                  </div>
                </div>
              </div>
            <?php else: ?>
              <div class="account-addresses-single-grid">
                <?php foreach ($addresses as $a): ?>
                  <article class="account-addresses-single-card">
                    <header class="account-addresses-single-card__head<?= !empty($a['is_default']) ? ' account-addresses-single-card__head--default' : '' ?>">
                      <div class="account-addresses-single-card__head-main">
                        <span class="account-addresses-single-card__home" aria-hidden="true"><?= ui_icon('house') ?></span>
                        <h3 class="account-addresses-single-card__title"><?= !empty($a['is_default']) ? 'Direccion principal' : (($a['label'] ?? '') ?: 'Direccion') ?></h3>
                      </div>
                      <a href="<?= e(base_url('/cuenta/direcciones/editar?id=' . (int)$a['id'])) ?>" class="account-addresses-single-card__edit">
                        <span aria-hidden="true"><?= ui_icon('note-edit') ?></span>
                        <span>Editar</span>
                      </a>
                    </header>

                    <div class="account-addresses-single-card__body">
                      <div class="account-addresses-single-card__row">
                        <span class="account-addresses-single-card__icon" aria-hidden="true"><?= ui_icon('user') ?></span>
                        <div class="account-addresses-single-card__copy">
                          <?php if (!empty($a['full_name'])): ?>
                            <div class="account-addresses-single-card__primary"><?= e((string)$a['full_name']) ?></div>
                          <?php endif; ?>
                          <div class="account-addresses-single-card__secondary">
                            <?= e((string)($a['street'] ?? '')) ?> <?= e((string)($a['number'] ?? '')) ?>
                            <?php if (!empty($a['floor'])): ?>, Piso <?= e((string)$a['floor']) ?><?php endif; ?>
                            <?php if (!empty($a['apartment'])): ?>, Depto <?= e((string)$a['apartment']) ?><?php endif; ?>
                          </div>
                        </div>
                      </div>

                      <div class="account-addresses-single-card__row">
                        <span class="account-addresses-single-card__icon" aria-hidden="true"><?= ui_icon('pin') ?></span>
                        <div class="account-addresses-single-card__copy">
                          <div class="account-addresses-single-card__primary"><?= e(((string)($a['city'] ?? '')) . ', ' . ((string)($a['state'] ?? '')) . ' (' . ((string)($a['zip'] ?? '')) . ')') ?></div>
                          <div class="account-addresses-single-card__secondary">Argentina</div>
                        </div>
                      </div>

                      <?php if (!empty($a['phone'])): ?>
                        <div class="account-addresses-single-card__row">
                          <span class="account-addresses-single-card__icon" aria-hidden="true"><?= ui_icon('phone') ?></span>
                          <div class="account-addresses-single-card__copy">
                            <div class="account-addresses-single-card__primary"><?= e((string)$a['phone']) ?></div>
                          </div>
                        </div>
                      <?php endif; ?>

                      <?php if (!empty($a['notes'])): ?>
                        <div class="account-addresses-single-card__row">
                          <span class="account-addresses-single-card__icon" aria-hidden="true"><?= ui_icon('document') ?></span>
                          <div class="account-addresses-single-card__copy">
                            <div class="account-addresses-single-card__primary">Nota de entrega</div>
                            <div class="account-addresses-single-card__secondary"><?= e((string)$a['notes']) ?></div>
                          </div>
                        </div>
                      <?php endif; ?>
                    </div>

                    <div class="account-addresses-single-card__actions">
                      <?php if (empty($a['is_default'])): ?>
                        <form method="post" action="<?= e(base_url('/cuenta/direcciones/principal')) ?>">
                          <?= csrf_field() ?>
                          <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                          <button class="v2-btn v2-btn--ghost account-addresses-single-card__btn" type="submit">
                            <span aria-hidden="true"><?= ui_icon('pin') ?></span>
                            <span>Hacer principal</span>
                          </button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </article>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </section>

          <?php if ($addressCount > 1): ?>
            <article class="account-addresses-cta-wrap">
              <a href="<?= e(base_url('/cuenta/direcciones/nueva')) ?>" class="v2-btn v2-btn--primary account-addresses-cta">
                <span aria-hidden="true"><?= ui_icon('plus-solid') ?></span>
                <span>Agregar direccion</span>
              </a>
            </article>
          <?php endif; ?>

          <article class="acct-support">
            <div class="acct-support__icon"><?= ui_icon('circle-check-solid') ?></div>
            <div class="acct-support__copy">
              <h2 class="acct-support__title">Compra mas rapido y recibi mejor</h2>
              <p class="acct-support__text">Tus direcciones te ayudan a completar compras en segundos.</p>
            </div>

          </article>
        </div>
      </div>

    </div>
  </div>
</section>
