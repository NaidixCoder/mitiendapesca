<section class="av3-card av3-p-0 av3-overflow-hidden">
  <details class="av3-group">
    <summary class="av3-pad-4 av3-md-pad-5 av3-border-bottom av3-border-slate-200 av3-flex av3-items-center av3-justify-between av3-gap-3 av3-cursor-pointer av3-select-none">
      <div>
        <h2 class="av3-type-base av3-weight-strong">Historial</h2>
        <p class="av3-type-body av3-text-slate-600 av3-space-top-1">�ltimas 10 importaciones (para auditor�a y seguimiento).</p>
      </div>
      <span class="av3-text-slate-500 av3-type-body group-open:rotate-180 transition-transform">?</span>
    </summary>

    <div class="av3-overflow-x-auto">
      <div class="max-h-[520px] av3-overflow-auto">
        <table class="av3-table av3-width-full av3-type-body">
          <thead class="av3-sticky top-0 av3-surface-white av3-z-10">
            <tr>
              <th>Archivo</th>
              <th>Estado</th>
              <th>OK</th>
              <th>Errores</th>
              <th>Creado</th>
              <th class="av3-text-right">Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!empty($historyRows)): foreach ($historyRows as $r): ?>
            <?php $rollbackUi = $r['rollback_ui'] ?? ['kind' => 'none', 'label' => '']; ?>
            <tr>
              <td class="av3-max-w--320px av3-truncate" title="<?= e($r['file_name'] ?? '') ?>"><?= e($r['file_name'] ?? '') ?></td>
              <td><span class="<?= e((string)($r['status_badge'] ?? 'badge-pill badge-neutral')) ?>"><?= e((string)($r['status_label'] ?? '')) ?></span></td>
              <td><?= (int)($r['ok_rows'] ?? 0) ?></td>
              <td><?= (int)($r['err_rows'] ?? 0) ?></td>
              <td class="whitespace-nowrap"><?= e($r['created_at'] ?? '') ?></td>
              <td class="av3-text-right whitespace-nowrap">
                <a class="btn-link" href="<?= e((string)($r['detail_url'] ?? url('/admin/imports'))) ?>">Ver detalle</a>
                <?php if ((string)($rollbackUi['kind'] ?? '') === 'can_revert_last'): ?>
                  <form method="post" action="<?= url('/admin/imports/rollback') ?>" class="av3-inline av3-ml-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)($r['id'] ?? 0) ?>">
                    <button type="submit" class="btn-danger" data-confirm="�Revertir esta importaci�n? Se restaurar� el punto de restauraci�n y se intentar� limpiar lo creado por el import.">Revertir</button>
                  </form>
                <?php elseif ((string)($rollbackUi['kind'] ?? '') === 'can_revert_non_last'): ?>
                  <span class="badge-pill badge-neutral av3-ml-2" title="Solo se puede revertir el �ltimo import."><?= e((string)($rollbackUi['label'] ?? 'Solo �ltimo import')) ?></span>
                <?php elseif ((string)($rollbackUi['kind'] ?? '') === 'done'): ?>
                  <span class="badge-pill badge-neutral av3-ml-2"><?= e((string)($rollbackUi['label'] ?? 'Revertida')) ?></span>
                <?php elseif ((string)($rollbackUi['kind'] ?? '') === 'blocked' || (string)($rollbackUi['kind'] ?? '') === 'legacy'): ?>
                  <span class="badge-pill badge-danger av3-ml-2"><?= e((string)($rollbackUi['label'] ?? 'Revert bloqueado')) ?></span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="6" class="av3-text-slate-600 av3-pad-4">Todav�a no hay importaciones registradas.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </details>
</section>
