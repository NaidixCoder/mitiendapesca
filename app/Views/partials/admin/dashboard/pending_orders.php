<?php
/**
 * Panel: Pedidos por gestionar (Dashboard)
 * Espera: $pendingPanel = ['attrs'=>[],'orders_base'=>'','rows'=>[]]
 */
$pendingPanel = $pendingPanel ?? ['attrs' => [], 'orders_base' => '', 'rows' => []];
$attrs = is_array($pendingPanel['attrs'] ?? null) ? $pendingPanel['attrs'] : [];
$rows = is_array($pendingPanel['rows'] ?? null) ? $pendingPanel['rows'] : [];
$ordersBase = (string)($pendingPanel['orders_base'] ?? '');

$containerAttrs = [];
if (!empty($attrs['index_base'])) {
  $containerAttrs[] = 'data-index-base="' . e((string)$attrs['index_base']) . '"';
}
if (!empty($attrs['show_base'])) {
  $containerAttrs[] = 'data-show-base="' . e((string)$attrs['show_base']) . '"';
}
if (!empty($attrs['status_endpoint'])) {
  $containerAttrs[] = 'data-status-endpoint="' . e((string)$attrs['status_endpoint']) . '"';
}
?>

<section id="pending-orders"  <?= implode(' ', $containerAttrs) ?>>
  <header >
    <div>
      <h3 >Pedidos por gestionar</h3>
      <p >Pendientes de pago, pagados o en preparacion</p>
    </div>
    <div>
      <?php if ($ordersBase !== ''): ?>
        <a  href="<?= e($ordersBase) ?>">Ver todos</a>
      <?php endif; ?>
    </div>
  </header>

  <div >
    <div >
    <table >
      <thead >
        <tr>
          <th>#</th>
          <th>Fecha</th>
          <th>Cliente</th>
          <th>Prioridad</th>
          <th>Estado</th>
          <th >Total</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr>
          <td colspan="6">No hay pedidos por gestionar.</td>
        </tr>
      <?php else: ?>
        <?php foreach ($rows as $o): ?>
          <?php
            $statusClass = (string)($o['badge_class'] ?? 'badge');
            $statusTone = 'is-info';
            if (strpos($statusClass, 'red-') !== false) {
              $statusTone = 'is-danger';
            } elseif (strpos($statusClass, 'amber-') !== false) {
              $statusTone = 'is-warn';
            } elseif (strpos($statusClass, 'green-') !== false) {
              $statusTone = 'is-ok';
            }
          ?>
          <tr data-admin-row-link="1" data-href="<?= e((string)($o['href'] ?? '')) ?>">
            <td ><?= e((string)($o['id_label'] ?? '#0')) ?></td>
            <td ><?= e((string)($o['created_label'] ?? '')) ?></td>
            <td><?= e((string)($o['customer_label'] ?? '-')) ?></td>
            <td >
              <?php if (!empty($o['is_urgent'])): ?>
                <span >Urgente</span>
              <?php else: ?>
                <span >Normal</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="dv-tag <?= e($statusTone) ?>">
                <span  aria-hidden="true"></span>
                <?= e((string)($o['status_label'] ?? '')) ?>
              </span>
            </td>
            <td ><?= e((string)($o['total_label'] ?? '$ 0')) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</section>
