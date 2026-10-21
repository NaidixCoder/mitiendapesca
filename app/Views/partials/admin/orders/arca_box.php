<?php
/**
 * Partial: Admin → Orders → ARCA box (KPI + acciones)
 *
 * Variables esperadas:
 * - array $orderView
 * - ?array $comprobante
 * - ?array $creditNote
 * - bool $comprobantesHasKind
 */

$comp = is_array($comprobante ?? null) ? $comprobante : null;
$compStatus = (string)($comp['status'] ?? '');
$compStatusLabel = function (string $st): string {
  return match ($st) {
    'issued' => 'Emitida',
    'pending' => 'Pendiente',
    'error' => 'Error',
    default => $st,
  };
};

$sellerRegime = (string)($orderView['seller_tax_regime'] ?? 'monotributo');
$totalNet = (int)($orderView['total_net'] ?? 0);
$totalVat = (int)($orderView['total_vat'] ?? 0);
$totalGross = (int)($orderView['total'] ?? 0);


/** @var array<int, array<string,mixed>> $comprobantes */
$comprobantes = $comprobantes ?? [];

// Resolver comprobante principal a mostrar en el pedido.
// Regla: si hay Factura A emitida => mostrar A; si no, Factura B emitida; si no, lo que venga por defecto.
$issuedA = null;
$issuedB = null;
$issuedC = null;

$pickLatest = function(?array $cur, array $row): array {
  if (!$cur) return $row;
  return ((int)($row['id'] ?? 0) > (int)($cur['id'] ?? 0)) ? $row : $cur;
};

foreach ($comprobantes as $row) {
  if (!is_array($row)) continue;
  if ((string)($row['status'] ?? '') !== 'issued') continue;
  if ((string)($row['kind'] ?? '') !== 'invoice' && !empty($row['kind'])) continue;

  $tipo = (int)($row['cbte_tipo'] ?? 0);
  if ($tipo === 1) { $issuedA = $pickLatest($issuedA, $row); continue; }
  if ($tipo === 6) { $issuedB = $pickLatest($issuedB, $row); continue; }
  if ($tipo === 11) { $issuedC = $pickLatest($issuedC, $row); continue; }
}

$primaryInvoice = $issuedA ?? $issuedB ?? $issuedC ?? null;
if ($primaryInvoice) {
  $comp = $primaryInvoice;
  $compStatus = (string)($comp['status'] ?? '');
}

// Etiquetas de comprobante (micro-UX para modal)
$invoiceLabel = 'Factura C';
$creditLabel  = 'Nota de Crédito C';

if (in_array($sellerRegime, ['responsable_inscripto','ri'], true)) {
  // RI: B por defecto, pero A opcional post-compra si existe solicitud aprobada y se emitió.
  $invoiceLabel = $issuedA ? 'Factura A' : 'Factura B';
  $creditLabel  = 'Nota de Crédito B';
}

$arcaAutoEmit = (int)($orderView['arca_auto_emit'] ?? 0) === 1;
$arcaTrigger  = (string)($orderView['arca_auto_emit_trigger'] ?? 'paid');

$invoiceIssued = ($comp && $compStatus === 'issued');
$creditIssued  = (is_array($creditNote ?? null) && (string)($creditNote['status'] ?? '') === 'issued');

$orderId = (int)($orderView['id'] ?? 0);
$orderStatus = (string)($orderView['status'] ?? '');


?>

<div id="arca-box-inner" data-order-id="<?= (int)$orderId ?>" data-order-status="<?= e($orderStatus) ?>" data-arca-auto-emit="<?= $arcaAutoEmit ? 1 : 0 ?>" data-arca-trigger="<?= e($arcaTrigger) ?>" data-has-invoice="<?= $invoiceIssued ? 1 : 0 ?>" data-invoice-status="<?= e($compStatus) ?>" data-has-credit-note="<?= $creditIssued ? 1 : 0 ?>" data-invoice-label="<?= e($invoiceLabel) ?>" data-credit-label="<?= e($creditLabel) ?>">
<section class="av3-card">
  <header class="av3-flex av3-items-center av3-justify-between av3-space-bottom-3">
    <h4 class="av3-weight-strong">Facturación (ARCA)</h4>
    <div class="av3-type-caption av3-text-slate-500">Emisión manual + auto-emisión opcional</div>
  </header>

  <!-- Fallback inline para feedback cuando el sistema de toasts no esté consistente -->
  <div id="arca-inline" class="av3-hidden av3-space-bottom-3 av3-radius-control av3-border av3-pad-x3 av3-pad-y2 av3-type-body"></div>

  <div class="av3-grid av3-md-grid-cols-3 av3-gap-3 av3-type-body">
    <div class="admin-surface av3-pad-3 av3-radius-control">
      <div class="av3-type-caption av3-text-slate-500">Régimen</div>
      <div class="av3-weight-medium av3-space-top-1"><?=
        e(
          $sellerRegime === 'responsable_inscripto' ? 'Responsable Inscripto'
          : ($sellerRegime === 'ri' ? 'Responsable Inscripto'
          : ($sellerRegime === 'exento' ? 'Exento'
          : ($sellerRegime === 'no_facturar' ? 'No facturar' : 'Monotributo')))
        )
      ?></div>
    </div>
    <div class="admin-surface av3-pad-3 av3-radius-control">
      <div class="av3-type-caption av3-text-slate-500">Totales</div>
      <?php if (in_array($sellerRegime, ['monotributo','responsable_inscripto','ri'], true)):
        // R1: tanto Factura C (Monotributo) como Factura B (RI) se muestran sin discriminar IVA.
      ?>
        <?php $compTipo = (int)($comp['cbte_tipo'] ?? 0); ?>
    <div class="av3-weight-medium av3-space-top-1"><?= $compTipo === 1 ? 'Discrimina IVA' : 'No discrimina IVA' ?></div>
        <div class="av3-type-caption av3-text-slate-500 av3-space-top-1"><?= e($invoiceLabel) ?> · Total: <?= e($orderView['total_fmt'] ?? money($totalGross)) ?></div>
      <?php else: ?>
        <div class="av3-weight-medium av3-space-top-1"><?= money($totalNet) ?> <span class="av3-text-slate-400">/</span> <?= money($totalVat) ?></div>
        <div class="av3-type-caption av3-text-slate-500 av3-space-top-1">Total: <?= e($orderView['total_fmt'] ?? money($totalGross)) ?></div>
      <?php endif; ?>
    </div>
    <div class="admin-surface av3-pad-3 av3-radius-control">
      <div class="av3-type-caption av3-text-slate-500">Estado</div>
      <?php if (!$comp): ?>
        <div class="av3-weight-medium av3-space-top-1">Sin emitir</div>
        <div class="av3-type-caption av3-text-slate-500 av3-space-top-1">Se puede emitir desde acá.</div>
      <?php else: ?>
        <div class="av3-weight-medium av3-space-top-1"><?= e($compStatusLabel($compStatus)) ?></div>
        <?php if ($compStatus === 'issued'): ?>
          <div class="av3-type-caption av3-text-slate-500 av3-space-top-1">CAE: <?= e((string)($comp['cae'] ?? '')) ?> · N°: <?= e((string)($comp['cbte_nro'] ?? '')) ?></div>
        <?php elseif ($compStatus === 'error'): ?>
          <div class="av3-type-caption av3-text-rose-700 av3-space-top-1">
            <?= e((string)($comp['error_code'] ?? 'ERROR')) ?>:
            <?= e((string)($comp['error_message'] ?? 'Error')) ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="av3-space-top-3 av3-flex av3-items-center av3-justify-end av3-gap-2">
    <?php
      $sellerIsMono = ($sellerRegime === 'monotributo');
      $sellerIsRI = ($sellerRegime === 'responsable_inscripto' || $sellerRegime === 'ri');
      $sellerIsNoFacturar = ($sellerRegime === 'no_facturar');
    ?>

    <?php
      $needsNc = ($comp && $compStatus === 'issued' && in_array((string)($orderView['status'] ?? ''), ['cancelled','refunded','chargeback'], true));
      $cn = $creditNote ?? null;
      $cnStatus = $cn ? (string)($cn['status'] ?? '') : '';
    ?>

    <?php if ($needsNc): ?>
      <?php if (empty($comprobantesHasKind)): ?>
        <div class="av3-type-caption av3-text-amber-700 mr-auto">
          ⚠️ Pedido <?= e((string)($orderView['status'] ?? '')) ?> con factura emitida: corresponde emitir <b>Nota de Crédito</b>.
          <span class="av3-text-slate-600">(Tu DB parece no tener soporte de NC: faltan columnas <code>kind</code>/<code>related_comprobante_id</code>. Ejecutá la migración 20260128_02_*.)</span>
        </div>
      <?php elseif ($cn && $cnStatus === 'issued'): ?>
        <div class="av3-type-caption av3-text-emerald-700 mr-auto">
          ✅ Nota de Crédito emitida: <?= e((string)($cn['pto_vta'] ?? '')) ?> / <?= e((string)($cn['cbte_tipo'] ?? '')) ?> / <?= e((string)($cn['cbte_nro'] ?? '')) ?> (CAE: <?= e((string)($cn['cae'] ?? '')) ?>)
          <a class="underline av3-ml-2" href="<?= e(base_url('/admin/facturacion/comprobantes/show?id='.(int)$cn['id'])) ?>">Ver</a>
          <a class="underline av3-ml-2" href="<?= e(base_url('/admin/facturacion/comprobantes/pdf?id='.(int)$cn['id'])) ?>">PDF</a>
        </div>
      <?php else: ?>
        <div class="av3-type-caption av3-text-amber-700 mr-auto av3-flex av3-flex-wrap av3-items-center av3-gap-2">
          <span>⚠️ Pedido <?= e((string)($orderView['status'] ?? '')) ?> con factura emitida: corresponde emitir <b>Nota de Crédito</b>.</span>
          <form action="<?= e(base_url('/admin/orders/arca/emit-nc')) ?>" method="post" class="av3-inline-flex av3-items-center av3-gap-2">
            <?php csrf_field(); ?>
            <input type="hidden" name="order_id" value="<?= (int)($orderView['id'] ?? 0) ?>">
            <button type="submit" class="btn-ghost">Emitir NC ahora</button>
          </form>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <?php if (!$comp || $compStatus !== 'issued'): ?>
      <?php if ($sellerIsNoFacturar): ?>
        <div class="av3-type-caption av3-text-amber-700 mr-auto">Facturación desactivada en Ajustes: no se emiten comprobantes.</div>
      <?php elseif ($sellerIsMono): ?>
        <form method="post" action="<?= e(url('/admin/orders/arca/emit')) ?>" class="av3-inline">
          <?= csrf_field() ?>
          <input type="hidden" name="order_id" value="<?= (int)$orderView['id'] ?>">
          <input type="hidden" name="tipo" value="C">
          <button class="btn-primary" type="submit">Emitir Factura C</button>
        </form>
      <?php elseif ($sellerIsRI): ?>
        <div class="av3-type-caption av3-text-slate-500 mr-auto">
          RI: se emite <b>Factura B</b> por defecto. <b>Factura A</b> es opcional post-compra a solicitud. <span class="av3-text-slate-400">(Factura A opcional post-compra)</span>
        </div>

        <form method="post" action="<?= e(url('/admin/orders/arca/emit')) ?>" class="av3-inline">
          <?= csrf_field() ?>
          <input type="hidden" name="order_id" value="<?= (int)$orderView['id'] ?>">
          <input type="hidden" name="tipo" value="B">
          <button class="btn-primary" type="submit">Emitir Factura B</button>
        </form>
      <?php else: ?>
        <div class="av3-type-caption av3-text-amber-700 mr-auto">Régimen no soportado para emisión automática/manual por ahora.</div>
      <?php endif; ?>

      <?php if ($comp && !empty($comp['id'])): ?>
        <a class="btn-secondary" href="<?= e(base_url('admin/facturacion/comprobantes/show?id=' . (int)$comp['id'])) ?>">Ver</a>
        <a class="btn-secondary" href="<?= e(base_url('admin/facturacion/comprobantes/pdf?id=' . (int)$comp['id'])) ?>" target="_blank" rel="noopener">PDF</a>
        <?php if (in_array($compStatus, ['pending','error'], true)): ?>
          <form method="post" action="<?= e(base_url('admin/facturacion/comprobantes/retry-emit')) ?>" class="av3-inline">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= (int)$comp['id'] ?>">
            <button class="btn-primary" type="submit">Reintentar emisión</button>
          </form>
        <?php endif; ?>
      <?php endif; ?>
    <?php else: ?>
      <div class="av3-flex av3-items-center av3-gap-2">
        <span class="av3-type-caption av3-text-slate-500">Emitida ✅</span>
        <?php if (!empty($comp['id'])): ?>
          <a class="btn-secondary" href="<?= e(base_url('admin/facturacion/comprobantes/show?id=' . (int)$comp['id'])) ?>">Ver</a>
          <a class="btn-primary" href="<?= e(base_url('admin/facturacion/comprobantes/pdf?id=' . (int)$comp['id'])) ?>" target="_blank" rel="noopener">PDF</a>

          <?php if (!empty($comprobantesHasKind) && is_array($creditNote ?? null) && !empty($creditNote['id'])):
            $cnId = (int)$creditNote['id'];
            $cnSt = (string)($creditNote['status'] ?? '');
          ?>
            <a class="btn-secondary" href="<?= e(base_url('admin/facturacion/comprobantes/show?id=' . $cnId)) ?>">Ver NC</a>
            <a class="btn-secondary" href="<?= e(base_url('admin/facturacion/comprobantes/pdf?id=' . $cnId)) ?>" target="_blank" rel="noopener">PDF NC</a>

            <?php if (in_array($cnSt, ['pending','error'], true)): ?>
              <form method="post" action="<?= e(base_url('admin/facturacion/comprobantes/retry-emit')) ?>" class="av3-inline">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$cnId ?>">
                <button class="btn-primary" type="submit">Reintentar NC</button>
              </form>
            <?php endif; ?>
          <?php endif; ?>

          <?php if (!empty($comprobantesHasKind) && !$creditIssued): ?>
            <form action="<?= e(base_url('/admin/orders/arca/emit-nc')) ?>" method="post" class="av3-inline">
              <?php csrf_field(); ?>
              <input type="hidden" name="order_id" value="<?= (int)($orderView['id'] ?? 0) ?>">
              <button type="submit" class="btn-secondary">Emitir <?= e($creditLabel) ?></button>
            </form>
          <?php endif; ?>

          <form method="post" action="<?= e(base_url('admin/facturacion/comprobantes/resend-email')) ?>" class="av3-inline">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= (int)$comp['id'] ?>">
            <button class="btn-secondary" type="submit">Reenviar email</button>
          </form>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="av3-space-top-2 av3-type-caption av3-text-slate-500">
    Requiere configurar <code>.env</code> (ARCA_CUIT, ARCA_CERT_PATH, ARCA_KEY_PATH). Recomendado: probar en <b>homologación</b> primero.
  </div>

<?php
  // Historial de comprobantes del pedido (UX: evitar ir a listado global)
  $hist = [];
  foreach ($comprobantes as $row) {
    if (!is_array($row)) continue;
    if ((string)($row['status'] ?? '') !== 'issued') continue;
    $hist[] = $row;
  }
  if ($hist) {
    usort($hist, fn($a,$b) => (int)($b['id'] ?? 0) <=> (int)($a['id'] ?? 0));
    $tipoLabel = function(int $cbteTipo): string {
      return match ($cbteTipo) {
        1 => 'Factura A',
        6 => 'Factura B',
        11 => 'Factura C',
        8 => 'Nota de Crédito B',
        13 => 'Nota de Crédito C',
        default => 'Comprobante',
      };
    };
  }
?>

<?php if (!empty($hist)): ?>
  <div class="av3-space-top-4">
    <div class="av3-flex av3-items-center av3-justify-between av3-space-bottom-2">
      <div class="av3-type-body av3-weight-strong">Comprobantes del pedido</div>
      <div class="av3-type-caption av3-text-slate-500">Historial</div>
    </div>

    <div class="max-h-64 av3-overflow-auto av3-radius-control av3-border av3-border-slate-200">
      <table class="av3-min-w-full av3-type-body">
        <thead class="av3-sticky top-0 av3-bg-slate-50 av3-border-bottom av3-border-slate-200">
          <tr class="av3-text-left">
            <th class="av3-pad-x3 av3-pad-y2">Tipo</th>
            <th class="av3-pad-x3 av3-pad-y2">Pto/Cbte</th>
            <th class="av3-pad-x3 av3-pad-y2">CAE</th>
            <th class="av3-pad-x3 av3-pad-y2">Total</th>
            <th class="av3-pad-x3 av3-pad-y2 av3-text-right">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php foreach ($hist as $h): ?>
            <?php
              $hid = (int)($h['id'] ?? 0);
              $pto = (int)($h['pto_vta'] ?? 0);
              $nro = (int)($h['cbte_nro'] ?? 0);
              $cae = (string)($h['cae'] ?? '');
              $tot = (int)($h['imp_total'] ?? 0);
              $cbteTipo = (int)($h['cbte_tipo'] ?? 0);
            ?>
            <tr>
              <td class="av3-pad-x3 av3-pad-y2 av3-weight-medium"><?= e($tipoLabel($cbteTipo)) ?></td>
              <td class="av3-pad-x3 av3-pad-y2"><?= e(sprintf('%04d-%08d', $pto, $nro)) ?></td>
              <td class="av3-pad-x3 av3-pad-y2"><?= e($cae ?: '—') ?></td>
              <td class="av3-pad-x3 av3-pad-y2">$ <?= e(number_format($tot, 0, ',', '.')) ?></td>
              <td class="av3-pad-x3 av3-pad-y2 av3-text-right whitespace-nowrap">
                <a class="btn-ghost" href="<?= url('/admin/facturacion/comprobantes/show') ?>?id=<?= $hid ?>">Ver</a>
                <a class="btn-ghost" href="<?= url('/admin/facturacion/comprobantes/pdf') ?>?id=<?= $hid ?>">PDF</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
</section>
</div>
