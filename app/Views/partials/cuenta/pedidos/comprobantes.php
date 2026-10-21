<?php
/** @var array<int, array<string,mixed>> $comprobantes */
$comprobantes = $comprobantes ?? [];

$rows = [];
foreach ($comprobantes as $c) {
  if (!is_array($c)) continue;
  if ((string)($c['status'] ?? '') !== 'issued') continue;
  $rows[] = $c;
}

if (!$rows) return;

usort($rows, function($a, $b) {
  return (int)($b['id'] ?? 0) <=> (int)($a['id'] ?? 0);
});

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
?>

<section class="order-show-card order-show-card--sidebar">
  <header class="order-show-card__head order-show-card__head--split">
    <div>
      <h2 class="order-show-card__title">Comprobantes del pedido</h2>
      <p class="order-show-card__intro">Historial fiscal emitido para esta compra.</p>
    </div>
    <span class="order-show-sidebar-caption">Historial</span>
  </header>

  <div class="order-show-card__body">
    <div class="order-show-fiscal-table-wrap">
      <table class="order-show-fiscal-table">
        <thead>
          <tr>
            <th>Tipo</th>
            <th>Punto / N°</th>
            <th>CAE</th>
            <th>Emitido</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $c): ?>
            <?php
              $pto = (int)($c['pto_vta'] ?? 0);
              $nro = (int)($c['cbte_nro'] ?? 0);
              $cae = (string)($c['cae'] ?? '—');
              $issuedAt = (string)($c['issued_at'] ?? $c['created_at'] ?? '');
              $cbteTipo = (int)($c['cbte_tipo'] ?? 0);
            ?>
            <tr>
              <td><?= e($tipoLabel($cbteTipo)) ?></td>
              <td><?= e(sprintf('%04d-%08d', $pto, $nro)) ?></td>
              <td><?= e($cae ?: '—') ?></td>
              <td><?= e($issuedAt ?: '—') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <p class="order-show-sidebar-note">Para descargar, usá el botón "Descargar factura". Si existe Factura A, se descarga esa.</p>
  </div>
</section>


