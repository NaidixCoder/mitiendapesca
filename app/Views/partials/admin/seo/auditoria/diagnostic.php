<?php
/** @var array<string,mixed> $r Vista preparada del producto (solo móvil). */
?>
<details class="av3-seo-diagnostic">
  <summary>Vista previa<?= !empty($r['issues_remaining']) ? ' y más problemas' : '' ?></summary>
  <div class="av3-seo-diagnostic__body">
    <?php if (!empty($r['issues_remaining'])): ?>
      <div class="av3-seo-diagnostic__extra"><strong>Otros problemas:</strong> <?= e(implode(' · ', $r['issues_remaining'])) ?></div>
    <?php endif; ?>
    <?php require BASE_PATH . '/app/Views/partials/admin/seo/auditoria/serp_preview.php'; ?>
  </div>
</details>
