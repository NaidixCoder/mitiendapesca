<section class="av3-ui-card av3-imports__history" aria-label="Historial de importaciones">
  <details class="av3-imports__disclosure" <?= !empty($historyRows) ? 'open' : '' ?>>
    <summary>
      <span><strong>Historial de importaciones</strong><small>Últimas 10 ejecuciones · auditoría y reversión segura</small></span>
      <span class="av3-imports__chevron" aria-hidden="true"></span>
    </summary>
    <div class="av3-imports__history-body av3-imports__table-container">
      <?php if (!empty($historyRows)): ?>
        <table class="av3-ui-table av3-imports__table av3-imports__table--history">
          <thead><tr><th scope="col">Archivo</th><th scope="col">Estado</th><th scope="col">Correctas</th><th scope="col">Errores</th><th scope="col">Fecha</th><th scope="col">Acciones</th></tr></thead>
          <tbody>
            <?php foreach ($historyRows as $r): ?>
              <?php
              $rollbackUi = $r['rollback_ui'] ?? ['kind' => 'none', 'label' => ''];
              $statusLabel = (string)($r['status_label'] ?? '');
              $tone = in_array($statusLabel, ['OK', 'Finalizada'], true) ? 'success' : (in_array($statusLabel, ['Con errores', 'Fallida'], true) ? 'danger' : (in_array($statusLabel, ['Procesando', 'Revisión requerida'], true) ? 'warning' : 'neutral'));
              ?>
              <tr class="av3-imports__row">
                <td data-label="Archivo" class="av3-imports__filename" title="<?= e((string)($r['file_name'] ?? '')) ?>"><?= e((string)($r['file_name'] ?? '')) ?></td>
                <td data-label="Estado"><span class="av3-ui-badge <?= $tone === 'success' ? '' : 'av3-ui-badge--' . e($tone) ?>"><?= e($statusLabel ?: 'Sin estado') ?></span></td>
                <td data-label="Correctas"><?= (int)($r['ok_rows'] ?? 0) ?></td>
                <td data-label="Errores"><?= (int)($r['err_rows'] ?? 0) ?></td>
                <td data-label="Fecha"><?= e((string)($r['created_at'] ?? '')) ?></td>
                <td data-label="Acciones">
                  <div class="av3-ui-actions av3-imports__history-actions">
                    <a class="av3-ui-btn av3-ui-btn--small" href="<?= e((string)($r['detail_url'] ?? url('/admin/imports'))) ?>">Ver detalle</a>
                    <?php if ((string)($rollbackUi['kind'] ?? '') === 'can_revert_last'): ?>
                      <form method="post" action="<?= e(url('/admin/imports/rollback')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)($r['id'] ?? 0) ?>">
                        <button type="submit" class="av3-ui-btn av3-ui-btn--danger av3-ui-btn--small" data-confirm="¿Revertir esta importación? Se restaurará el punto de restauración y se intentará limpiar lo creado por la importación.">Revertir</button>
                      </form>
                    <?php elseif (in_array((string)($rollbackUi['kind'] ?? ''), ['can_revert_non_last', 'done', 'blocked', 'legacy'], true)): ?>
                      <span class="av3-imports__hint" title="<?= (string)($rollbackUi['kind'] ?? '') === 'can_revert_non_last' ? 'Solo se permite revertir la última importación (LIFO).' : '' ?>"><?= e((string)($rollbackUi['label'] ?? '')) ?></span>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="av3-ui-empty">Todavía no hay importaciones registradas.</p>
      <?php endif; ?>
    </div>
  </details>
</section>
