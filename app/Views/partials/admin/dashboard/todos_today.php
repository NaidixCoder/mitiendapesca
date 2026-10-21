<?php
/**
 * Pendientes de hoy (Dashboard)
 * Espera: $todosCards = [{label,value,href}]
 */
$todosCards = $todosCards ?? [];
?>

<section >
  <header >
    <h3 >Pendientes de hoy</h3>
    <span >Resumen operativo</span>
  </header>

  <div >
    <?php foreach ($todosCards as $card): ?>
      <a  href="<?= e((string)($card['href'] ?? '#')) ?>">
        <p ><?= e((string)($card['label'] ?? '')) ?></p>
        <p ><?= (int)($card['value'] ?? 0) ?></p>
      </a>
    <?php endforeach; ?>
  </div>
</section>
