<?php
$warnList = is_array($show['warn_list'] ?? null) ? $show['warn_list'] : [];
if (empty($warnList)) {
    return;
}
?>
<section class="av3-card">
  <header class="av3-flex av3-items-center av3-justify-between av3-space-bottom-3">
    <h3 class="av3-weight-strong">Avisos</h3>
    <div class="av3-type-body av3-text-var-muted"><?= (int)count($warnList) ?> avisos</div>
  </header>

  <div class="max-h-[320px] av3-overflow-auto av3-border av3-border-var-border av3-radius-card">
    <table class="av3-table av3-width-full av3-type-body">
      <thead class="av3-sticky top-0 av3-bg--color-var---admin-card">
        <tr><th class="av3-w--110px">Fila</th><th>Aviso</th></tr>
      </thead>
      <tbody>
        <?php foreach ($warnList as $w): ?>
          <tr>
            <td class="whitespace-nowrap"><?= (int)($w['row'] ?? 0) ?></td>
            <td><?= e((string)($w['warning'] ?? '')) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
