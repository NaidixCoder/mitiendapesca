<?php
$isSuccess = !empty($show['is_success']);
$rowsCount = (int)($show['rows_count'] ?? 0);
$rowsPrepared = is_array($show['rows_prepared'] ?? null) ? $show['rows_prepared'] : [];
?>
<section class="av3-card">
  <header class="av3-flex av3-items-center av3-justify-between av3-space-bottom-3">
    <h3 class="av3-weight-strong"><?= $isSuccess ? 'Detalle por fila (ejecutado)' : 'Detalle por fila (validacion)' ?></h3>
    <div class="av3-type-body av3-text-var-muted"><?= $rowsCount ?> filas</div>
  </header>

  <div class="av3-space-top-2 av3-type-caption av3-text-var-muted av3-flex av3-flex-wrap av3-gap-2">
    <span class="badge-pill badge-success">OK</span>
    <span class="badge-pill badge-neutral">Aviso</span>
    <span class="badge-pill badge-danger">Error</span>
    <span class="av3-ml-2">La columna "Cambios / notas" explica que se modifico en cada fila.</span>
  </div>

  <?php if (empty($rowsPrepared)): ?>
    <p class="av3-type-body av3-text-var-muted">No hay detalle por fila (importaciones viejas).</p>
  <?php else: ?>
    <div class="max-h-[520px] av3-overflow-auto av3-border av3-border-var-border av3-radius-card">
      <table class="av3-table av3-width-full av3-type-body">
        <thead class="av3-sticky top-0 av3-bg--color-var---admin-card">
          <tr>
            <th class="av3-w--90px">Fila</th>
            <th class="av3-w--110px">Tipo</th>
            <th class="av3-w--220px">SKU</th>
            <th class="av3-w--220px">SKU padre</th>
            <th class="av3-w--160px">Entidad</th>
            <th class="av3-w--130px">Resultado</th>
            <th class="av3-w--170px">Accion</th>
            <th>Cambios / notas</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rowsPrepared as $rr): ?>
            <?php $hasCambios = !empty($rr['cambios'] ?? []); ?>
            <tr class="<?= e((string)($rr['row_class'] ?? '')) ?>">
              <td class="whitespace-nowrap"><?= (int)($rr['row'] ?? 0) ?></td>
              <td class="whitespace-nowrap"><?= e((string)($rr['tipo_fila'] ?? '')) ?></td>
              <td class="whitespace-nowrap"><?= e((string)($rr['sku'] ?? '')) ?></td>
              <td class="whitespace-nowrap"><?= e((string)($rr['sku_padre'] ?? '')) ?></td>
              <td class="whitespace-nowrap"><?= e((string)($rr['entidad'] ?? '')) ?></td>
              <td class="whitespace-nowrap"><span class="<?= e((string)($rr['result_badge'] ?? 'badge-pill badge-neutral')) ?>"><?= e((string)($rr['result_label'] ?? '')) ?></span></td>
              <td class="whitespace-nowrap"><span class="<?= e((string)($rr['action_badge'] ?? 'badge-pill badge-neutral')) ?>"><?= e(((string)($rr['accion'] ?? '')) ?: '-') ?></span></td>
              <td>
                <?php if (!empty($rr['is_noop']) && !$hasCambios): ?>
                  <span class="av3-text-var-muted">Sin cambios detectados.</span>
                <?php elseif (!$hasCambios): ?>
                  <span class="av3-text-var-muted">-</span>
                <?php else: ?>
                  <div class="av3-flow-1">
                    <?php foreach ((array)($rr['cambios'] ?? []) as $c): ?>
                      <?php if (($c['type'] ?? '') === 'diff'): ?>
                        <div class="av3-type-body"><span class="av3-weight-medium"><?= e((string)($c['k'] ?? '')) ?>:</span> <span class="av3-text-var-muted"><?= e((string)($c['from'] ?? '')) ?></span> <span class="av3-mx-1 av3-text-var-muted">-></span> <span class="av3-weight-medium"><?= e((string)($c['to'] ?? '')) ?></span></div>
                      <?php else: ?>
                        <div class="av3-type-body av3-text-var-muted">- <?= e((string)($c['raw'] ?? '')) ?></div>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="help av3-space-top-2"><?= $isSuccess ? 'Detalle real de lo ejecutado. Revisa "Cambios / notas" para ver cada modificacion.' : 'Detalle de validacion: no se aplico nada si hubo errores.' ?></p>
  <?php endif; ?>
</section>
