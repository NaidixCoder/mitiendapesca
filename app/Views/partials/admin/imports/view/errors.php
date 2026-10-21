<?php
$errList = is_array($show['err_list'] ?? null) ? $show['err_list'] : [];
$errRows = (int)($show['err_rows'] ?? 0);
?>
<section class="av3-card">
  <header class="av3-flex av3-items-center av3-justify-between av3-space-bottom-3">
    <h3 class="av3-weight-strong">Errores</h3>
    <div class="av3-type-body av3-text-var-muted"><?= $errRows ?> errores</div>
  </header>

  <?php if (empty($errList)): ?>
    <p class="av3-type-body av3-text-var-muted">No hay errores registrados.</p>
  <?php else: ?>
    <div class="max-h-[420px] av3-overflow-auto av3-border av3-border-var-border av3-radius-card">
      <table class="av3-table av3-width-full av3-type-body">
        <thead class="av3-sticky top-0 av3-bg--color-var---admin-card">
          <tr><th class="av3-w--110px">Fila</th><th>Error</th></tr>
        </thead>
        <tbody>
          <?php foreach ($errList as $eRow): ?>
            <tr>
              <td class="whitespace-nowrap"><?= (int)($eRow['row'] ?? 0) ?></td>
              <td><?= e((string)($eRow['error'] ?? '')) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="help av3-space-top-2">Tip: si hay errores, el sistema no aplica cambios.</p>
  <?php endif; ?>
</section>
