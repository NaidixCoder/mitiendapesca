<?php
$pageId = 'account-loyalty';
$pageTitle = 'Programa de lealtad - Mi Cuenta';
$pageDesc = 'Consultá tus puntos y tu progreso.';

$uname = $_SESSION['uname'] ?? 'Usuario';
$email = $_SESSION['email'] ?? '';
$avatarUrl = $_SESSION['uavatar'] ?? '';
if ($avatarUrl && (parse_url($avatarUrl, PHP_URL_HOST) ?? '') === 'lh3.googleusercontent.com'
    && !preg_match('/=s\d+-c$/', $avatarUrl)) {
    $avatarUrl .= '=s96-c';
}
$isAdmin = function_exists('is_admin') && is_admin();
$whatsUrl = trim((string)app_setting('social_whatsapp_url', env('CONTACT_WHATSAPP_URL', '')));

$loy = is_array($loyalty ?? null) ? $loyalty : [];
$tier = (string)($loy['tier'] ?? 'Bronce');
$points = (int)($loy['points'] ?? 0);
$paidTotal = (int)($loy['paid_total'] ?? 0);
$paidOrdersCount = (int)($loy['paid_orders_count'] ?? 0);
$pct = (int)($loy['progress_pct'] ?? 0);
$nextTier = $loy['next_tier'] ?? null;
$toNext = $loy['to_next_tier'] ?? null;
$rateLabel = (string)($loy['rate_label'] ?? '1 punto cada $ 1.000');

$pendingTotal = (int)($loy['pending_total'] ?? 0);
$pendingPoints = (int)($loy['pending_points'] ?? 0);

$benefits = is_array($loy['benefits'] ?? null) ? $loy['benefits'] : [];
$nextBenefits = is_array($loy['next_benefits'] ?? null) ? $loy['next_benefits'] : [];
$benefitsNote = (string)($loy['benefits_note'] ?? '');

$paidRows = is_array($loy['orders_paid_rows'] ?? null) ? $loy['orders_paid_rows'] : [];
$pendingRows = is_array($loy['orders_pending_rows'] ?? null) ? $loy['orders_pending_rows'] : [];

$ledgerEnabled = (bool)($loy['ledger_enabled'] ?? false);
$ledgerRows = is_array($loy['ledger_rows'] ?? null) ? $loy['ledger_rows'] : [];
$ledgerMismatch = (bool)($loy['ledger_mismatch'] ?? false);
$ledgerNote = (string)($loy['ledger_note'] ?? '');

$tierTone = match (strtolower($tier)) {
  'platino' => 'info',
  'oro' => 'warn',
  'plata' => 'neutral',
  default => 'ok',
};

$progressPct = (int) max(0, min(100, $pct));

$statusTone = static function (string $status): string {
  return match ($status) {
    'paid', 'delivered' => 'ok',
    'processing', 'packed', 'shipped' => 'info',
    'pending_payment', 'pending', 'payment_failed', 'payment_expired' => 'warn',
    'cancelled', 'refunded', 'chargeback' => 'danger',
    default => 'neutral',
  };
};
?>

<section class="acct-page account-loyalty-page">
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
              ['href' => url('/productos'), 'label' => 'Comprar', 'class' => 'v2-btn v2-btn--primary'],
          ],
      ]);
      ?>

      <div class="account-orders-layout">
        <div class="account-orders-layout__aside">
          <?php
          section('cuenta/aside', [
              'active' => 'lealtad',
              'uname' => $uname,
              'email' => $email,
              'avatarUrl' => $avatarUrl,
              'isAdmin' => $isAdmin,
              'whatsUrl' => $whatsUrl,
          ]);
          ?>
        </div>

        <div class="account-layout-main">
      <div class="account-loyalty-grid">
        <section class="account-loyalty-card account-loyalty-card--summary">
          <header class="account-loyalty-card__head">
            <div class="account-loyalty-card__head-copy">
              <h2 class="account-loyalty-card__title">Tu progreso</h2>
              <p class="account-loyalty-card__intro"><?= e($rateLabel) ?></p>
            </div>
            <span class="account-loyalty-badge account-loyalty-badge--<?= e($tierTone) ?>"><?= e($tier) ?></span>
          </header>

          <div class="account-loyalty-card__body">
            <div class="account-loyalty-stats">
              <article class="account-loyalty-stat">
                <div class="account-loyalty-stat__label">Puntos confirmados</div>
                <div class="account-loyalty-stat__value account-loyalty-stat__value--xl"><?= number_format($points, 0, ',', '.') ?></div>
                <p class="account-loyalty-stat__hint">De compras pagadas.</p>
              </article>

              <article class="account-loyalty-stat">
                <div class="account-loyalty-stat__label">Total pagado</div>
                <div class="account-loyalty-stat__value"><?= money($paidTotal) ?></div>
                <p class="account-loyalty-stat__hint"><?= (int)$paidOrdersCount ?> pedido(s) que sumaron.</p>
              </article>

              <article class="account-loyalty-stat">
                <div class="account-loyalty-stat__label">En proceso</div>
                <div class="account-loyalty-stat__value"><?= money($pendingTotal) ?></div>
                <p class="account-loyalty-stat__hint">Potencial: <strong><?= number_format($pendingPoints, 0, ',', '.') ?></strong> punto(s).</p>
              </article>
            </div>

            <div class="account-loyalty-progress" aria-hidden="true">
              <div class="account-loyalty-progress__fill" style="width: <?= $progressPct ?>%"></div>
            </div>

            <div class="account-loyalty-progress__scale">
              <span><?= e($tier) ?></span>
              <span><?= $nextTier ? ('Próximo: ' . e((string)$nextTier)) : 'Nivel máximo' ?></span>
            </div>

            <?php if ($nextTier && is_numeric($toNext)) : ?>
              <p class="account-loyalty-message">
                Te faltan <strong><?= money((int)$toNext) ?></strong> para llegar a <strong><?= e((string)$nextTier) ?></strong>.
              </p>
            <?php else: ?>
              <p class="account-loyalty-message">Sos <strong>Platino</strong> y ya estás en el nivel más alto del programa.</p>
            <?php endif; ?>
          </div>
        </section>

        <section class="account-loyalty-card">
          <header class="account-loyalty-card__head">
            <div class="account-loyalty-card__head-copy">
              <h2 class="account-loyalty-card__title">Beneficios</h2>
              <p class="account-loyalty-card__intro">Lo que tenés hoy y lo que sigue.</p>
            </div>
          </header>

          <div class="account-loyalty-card__body">
            <?php if (!empty($benefits)) : ?>
              <section class="account-loyalty-benefit-box">
                <h3 class="account-loyalty-benefit-box__title"><?= e($tier) ?></h3>
                <ul class="account-loyalty-benefit-list">
                  <?php foreach ($benefits as $benefit) : ?>
                    <li><?= e((string)$benefit) ?></li>
                  <?php endforeach; ?>
                </ul>
              </section>
            <?php endif; ?>

            <?php if ($nextTier && !empty($nextBenefits)) : ?>
              <section class="account-loyalty-benefit-box account-loyalty-benefit-box--next">
                <h3 class="account-loyalty-benefit-box__title">Próximo: <?= e((string)$nextTier) ?></h3>
                <ul class="account-loyalty-benefit-list">
                  <?php foreach ($nextBenefits as $benefit) : ?>
                    <li><?= e((string)$benefit) ?></li>
                  <?php endforeach; ?>
                </ul>
              </section>
            <?php endif; ?>

            <?php if ($benefitsNote !== '') : ?>
              <p class="account-loyalty-note"><?= e($benefitsNote) ?></p>
            <?php endif; ?>
          </div>
        </section>
      </div>

      <div class="account-loyalty-grid">
        <section class="account-loyalty-card">
          <header class="account-loyalty-card__head">
            <div class="account-loyalty-card__head-copy">
              <h2 class="account-loyalty-card__title">Niveles y requisitos</h2>
              <p class="account-loyalty-card__intro">Se calculan según tu total pagado histórico.</p>
            </div>
          </header>

          <div class="account-loyalty-card__body">
            <dl class="account-loyalty-rules">
              <div class="account-loyalty-rules__row"><dt>Bronce</dt><dd>&lt; <?= money(100000) ?></dd></div>
              <div class="account-loyalty-rules__row"><dt>Plata</dt><dd>≥ <?= money(100000) ?></dd></div>
              <div class="account-loyalty-rules__row"><dt>Oro</dt><dd>≥ <?= money(250000) ?></dd></div>
              <div class="account-loyalty-rules__row"><dt>Platino</dt><dd>≥ <?= money(500000) ?></dd></div>
            </dl>
          </div>
        </section>

        <section class="account-loyalty-card account-loyalty-card--wide">
          <header class="account-loyalty-card__head">
            <div class="account-loyalty-card__head-copy">
              <h2 class="account-loyalty-card__title">Puntos por pedido</h2>
              <p class="account-loyalty-card__intro">Vas a ver cuántos puntos sumó cada compra y cuántos están en proceso.</p>
            </div>
          </header>

          <div class="account-loyalty-card__body">
            <?php if (empty($paidRows) && empty($pendingRows)) : ?>
              <div class="account-loyalty-empty">Todavía no hay pedidos para mostrar.</div>
            <?php else: ?>
              <div class="account-loyalty-table-stack">
                <?php if (!empty($paidRows)) : ?>
                  <section class="account-loyalty-table-card">
                    <div class="account-loyalty-table-card__title">Confirmados</div>
                    <div class="account-loyalty-table-wrap">
                      <table class="account-loyalty-table">
                        <thead>
                          <tr>
                            <th>Pedido</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th class="is-right">Total</th>
                            <th class="is-right">Puntos</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($paidRows as $row) : ?>
                            <?php $rowTone = $statusTone((string)($row['status'] ?? 'pending')); ?>
                            <tr>
                              <td><a class="account-loyalty-link" href="<?= e(url('/cuenta/pedidos/ver') . '?id=' . (int)$row['id']) ?>">#<?= (int)$row['id'] ?></a></td>
                              <td><?= $row['created_at'] ? e(date('d/m/Y H:i', strtotime((string)$row['created_at']))) : '—' ?></td>
                              <td><span class="account-loyalty-badge account-loyalty-badge--<?= e($rowTone) ?>"><?= e(order_status_label_user((string)$row['status'])) ?></span></td>
                              <td class="is-right"><?= money((int)$row['total']) ?></td>
                              <td class="is-right account-loyalty-table__strong"><?= number_format((int)$row['points'], 0, ',', '.') ?></td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  </section>
                <?php endif; ?>

                <?php if (!empty($pendingRows)) : ?>
                  <section class="account-loyalty-table-card">
                    <div class="account-loyalty-table-card__title">En proceso</div>
                    <div class="account-loyalty-table-wrap">
                      <table class="account-loyalty-table">
                        <thead>
                          <tr>
                            <th>Pedido</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th class="is-right">Total</th>
                            <th class="is-right">Potencial</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($pendingRows as $row) : ?>
                            <?php $rowTone = $statusTone((string)($row['status'] ?? 'pending')); ?>
                            <tr>
                              <td><a class="account-loyalty-link" href="<?= e(url('/cuenta/pedidos/ver') . '?id=' . (int)$row['id']) ?>">#<?= (int)$row['id'] ?></a></td>
                              <td><?= $row['created_at'] ? e(date('d/m/Y H:i', strtotime((string)$row['created_at']))) : '—' ?></td>
                              <td><span class="account-loyalty-badge account-loyalty-badge--<?= e($rowTone) ?>"><?= e(order_status_label_user((string)$row['status'])) ?></span></td>
                              <td class="is-right"><?= money((int)$row['total']) ?></td>
                              <td class="is-right account-loyalty-table__strong"><?= number_format((int)$row['potential_points'], 0, ',', '.') ?></td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                    <p class="account-loyalty-note">Los puntos se acreditan cuando el pago queda confirmado.</p>
                  </section>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        </section>
      </div>

      <section class="account-loyalty-card">
        <header class="account-loyalty-card__head">
          <div class="account-loyalty-card__head-copy">
            <h2 class="account-loyalty-card__title">Movimientos de puntos</h2>
            <p class="account-loyalty-card__intro">Tu cuenta corriente de puntos confirmados.</p>
          </div>
          <?php if ($ledgerEnabled && $ledgerMismatch && $ledgerNote !== '') : ?>
            <div class="account-loyalty-note account-loyalty-note--compact"><?= e($ledgerNote) ?></div>
          <?php endif; ?>
        </header>

        <div class="account-loyalty-card__body">
          <?php if (!$ledgerEnabled) : ?>
            <div class="account-loyalty-empty">La sección de movimientos se habilita cuando la base del programa está actualizada en tu tienda.</div>
          <?php elseif (empty($ledgerRows)) : ?>
            <div class="account-loyalty-empty">Todavía no hay movimientos para mostrar.</div>
          <?php else: ?>
            <div class="account-loyalty-table-wrap">
              <table class="account-loyalty-table">
                <thead>
                  <tr>
                    <th>Fecha</th>
                    <th>Concepto</th>
                    <th class="is-right">Delta</th>
                    <th class="is-right">Saldo</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($ledgerRows as $row) : ?>
                    <?php
                      $delta = (int)($row['points_delta'] ?? 0);
                      $deltaSign = $delta > 0 ? '+' : '';
                      $deltaTone = $delta > 0 ? 'ok' : ($delta < 0 ? 'danger' : 'neutral');
                    ?>
                    <tr>
                      <td><?= $row['created_at'] ? e(date('d/m/Y H:i', strtotime((string)$row['created_at']))) : '—' ?></td>
                      <td>
                        <div class="account-loyalty-ledger-label">
                          <span><?= e((string)($row['label'] ?? 'Movimiento')) ?></span>
                          <?php if (!empty($row['order_id'])) : ?>
                            <a class="account-loyalty-link" href="<?= e(url('/cuenta/pedidos/ver') . '?id=' . (int)$row['order_id']) ?>">Ver pedido #<?= (int)$row['order_id'] ?></a>
                          <?php endif; ?>
                        </div>
                      </td>
                      <td class="is-right account-loyalty-table__strong account-loyalty-table__strong--<?= e($deltaTone) ?>"><?= e($deltaSign) . number_format($delta, 0, ',', '.') ?></td>
                      <td class="is-right account-loyalty-table__strong"><?= number_format((int)($row['balance_after'] ?? 0), 0, ',', '.') ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <p class="account-loyalty-note">Los movimientos se registran por etapas. Por ahora, se inicializa con tu saldo actual.</p>
          <?php endif; ?>
        </div>
      </section>
        </div>
      </div>

    </div>
  </div>
</section>




