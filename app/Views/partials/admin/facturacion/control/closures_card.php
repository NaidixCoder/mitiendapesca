<article class="fisc-card">
  <header class="fisc-card-head">
    <div>
      <h2>Ultimos cierres</h2>
      <p>Resumen de cierres y saldos.</p>
    </div>
    <a class="btn btn-secondary" href="<?= e(base_url('admin/facturacion/cierres-mensuales')) ?>">Ver detalle</a>
  </header>
  <div class="fisc-card-body">
    <div class="fisc-table-wrap">
      <table class="av3-fiscal-data-table av3-fiscal-data-table--cards fisc-table">
        <thead>
          <tr>
            <th>Periodo</th>
            <th class="is-right">IVA ventas</th>
            <th class="is-right">IVA compras</th>
            <th class="is-right">Saldo</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$latestClosures): ?>
            <tr>
              <td colspan="4">Todavia no hay cierres.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($latestClosures as $r): ?>
              <tr>
                <td data-label="Periodo" class="is-strong"><?= e((string)($r['period'] ?? '')) ?></td>
                <td data-label="IVA ventas" class="is-right"><?= e(money((int)($r['iva_ventas'] ?? 0))) ?></td>
                <td data-label="IVA compras" class="is-right"><?= e(money((int)($r['iva_compras'] ?? 0))) ?></td>
                <td data-label="Saldo" class="is-right is-strong"><?= e(money((int)($r['saldo_iva'] ?? 0))) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</article>
