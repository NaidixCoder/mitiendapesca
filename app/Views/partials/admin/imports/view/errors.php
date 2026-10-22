<?php
$errList = is_array($show['err_list'] ?? null) ? $show['err_list'] : [];
$errRows = (int)($show['err_rows'] ?? 0);
?>
<?php if ($errList || $errRows): ?>
<section class="av3-ui-card av3-imports__block" aria-labelledby="imports-errors-heading">
  <header class="av3-imports__block-head"><h3 id="imports-errors-heading">Errores registrados</h3><span><?= $errRows ?> errores</span></header>
  <?php if (!$errList): ?>
    <p class="av3-ui-empty">No se conserva el detalle de errores de esta ejecución.</p>
  <?php else: ?>
    <ul class="av3-imports__log">
      <?php foreach ($errList as $eRow): ?>
        <li class="av3-imports__log-item"><strong>Fila <?= (int)($eRow['row'] ?? 0) ?></strong><p><?= e((string)($eRow['error'] ?? '')) ?></p></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>
<?php endif; ?>
