<?php
$isSuccess = !empty($show['is_success']);
$rowsCount = (int)($show['rows_count'] ?? 0);
$rowsPrepared = is_array($show['rows_prepared'] ?? null) ? $show['rows_prepared'] : [];
$hasDiagnostics = !empty($show['has_diagnostics']);
?>
<section class="av3-ui-card av3-imports__block av3-imports__row-details" aria-labelledby="imports-rows-heading">
  <header class="av3-imports__block-head">
    <h3 id="imports-rows-heading">Diferencias y detalle por fila</h3>
    <span><?= $hasDiagnostics ? ($rowsCount . ' filas · Detalle registrado') : 'Diagnóstico por fila no disponible' ?></span>
  </header>
  <?php if (!$rowsPrepared): ?>
    <p class="av3-ui-empty"><?= $hasDiagnostics ? 'El diagnóstico no contiene filas detalladas.' : 'No se conserva el archivo de diagnóstico por fila de esta ejecución. Los cambios pueden haberse realizado y revertido; consultá el estado, el snapshot y la auditoría.' ?></p>
  <?php else: ?>
    <div class="av3-imports__table-container">
      <table class="av3-ui-table av3-imports__table av3-imports__table--details">
        <thead><tr>
          <th scope="col">Fila</th><th scope="col">Tipo</th><th scope="col">SKU</th><th scope="col">SKU padre</th><th scope="col">Entidad</th><th scope="col">Resultado</th><th scope="col">Acción</th><th scope="col">Cambios / notas</th>
        </tr></thead>
        <tbody>
          <?php foreach ($rowsPrepared as $rr): ?>
            <?php
            $hasChanges = !empty($rr['cambios']);
            $level = (string)($rr['level'] ?? 'ok');
            $tone = $level === 'error' ? 'av3-ui-badge--danger' : ($level === 'warning' ? 'av3-ui-badge--warning' : '');
            ?>
            <tr class="av3-imports__row">
              <td data-label="Fila" class="av3-imports__row-number"><?= (int)($rr['row'] ?? 0) ?></td>
              <td data-label="Tipo"><?= e((string)($rr['tipo_fila'] ?? '')) ?></td>
              <td data-label="SKU" class="av3-imports__sku"><?= e((string)($rr['sku'] ?? '')) ?: '—' ?></td>
              <td data-label="SKU padre" class="av3-imports__sku"><?= e((string)($rr['sku_padre'] ?? '')) ?: '—' ?></td>
              <td data-label="Entidad"><?= e((string)($rr['entidad'] ?? '')) ?></td>
              <td data-label="Resultado"><span class="av3-ui-badge <?= e($tone) ?>"><?= e((string)($rr['result_label'] ?? '')) ?></span></td>
              <td data-label="Acción"><?= e((string)($rr['accion'] ?? '')) ?: '—' ?></td>
              <td data-label="Cambios / notas" class="av3-imports__notes">
                <?php if (!empty($rr['is_noop']) && !$hasChanges): ?>
                  <span class="av3-imports__muted">Sin cambios detectados.</span>
                <?php elseif (!$hasChanges): ?>
                  <span class="av3-imports__muted">Sin diferencias registradas.</span>
                <?php else: ?>
                  <div class="av3-imports__diffs">
                    <?php foreach ((array)$rr['cambios'] as $c): ?>
                      <?php if (($c['type'] ?? '') === 'diff'): ?>
                        <div class="av3-imports__diff">
                          <span class="av3-imports__diff-key"><?= e((string)($c['k'] ?? '')) ?></span>
                          <span class="av3-imports__diff-values">
                            <span class="av3-imports__diff-from"><?= e((string)($c['from'] ?? '')) ?></span>
                            <span class="av3-imports__diff-arrow" aria-label="cambia a">→</span>
                            <span class="av3-imports__diff-to"><?= e((string)($c['to'] ?? '')) ?></span>
                          </span>
                        </div>
                      <?php elseif (($c['type'] ?? '') === 'metadata'): ?>
                        <div class="av3-imports__details-values">
                          <?php foreach ((array)($c['items'] ?? []) as $item): ?>
                            <span><small><?= e((string)($item['label'] ?? '')) ?></small><strong><?= e((string)($item['value'] ?? '')) ?></strong></span>
                          <?php endforeach; ?>
                        </div>
                      <?php else: ?>
                        <div class="av3-imports__hint"><?= e((string)($c['raw'] ?? '')) ?></div>
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
    <p class="av3-imports__block-note">El detalle refleja los datos registrados al ejecutar la importación. <?= !empty($show['job']) && (string)($show['job']['status'] ?? '') === 'review_required' ? 'Resultado de commit sin confirmar: solicitá conciliación supervisada antes de realizar otra operación.' : (((string)($show['revert_status'] ?? '')) === 'reverted' ? 'Los cambios de esta importación fueron revertidos según el snapshot verificado.' : (((string)($show['job']['status'] ?? '')) === 'failed' ? 'La ejecución terminó sin confirmar cambios comerciales.' : 'Consultá el estado y las advertencias antes de continuar.')) ?></p>
  <?php endif; ?>
</section>
