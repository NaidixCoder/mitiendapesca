<?php
/**
 * Alertas operativas (Dashboard)
 * Espera: $alerts = array de [text,href,tone,badge_class]
 */
$alerts = $alerts ?? [];
if (empty($alerts)) return;
?>
<section>
  <header >
    <h3 >Alertas operativas</h3>
  </header>

  <ul >
    <?php foreach ($alerts as $a):
      $text = (string)($a['text'] ?? '');
      $href = (string)($a['href'] ?? '');
      $tone = (string)($a['tone'] ?? 'is-info');
      $badgeClass = (string)($a['badge_class'] ?? '');
      if ($text === '') continue;
      // Fallback legacy para alertas antiguas que lleguen solo con badge_class.
      if (!isset($a['tone'])) {
        if (strpos($badgeClass, 'red-') !== false) {
          $tone = 'is-danger';
        } elseif (strpos($badgeClass, 'amber-') !== false) {
          $tone = 'is-warn';
        } else {
          $tone = 'is-info';
        }
      }
    ?>
      <li>
        <?php if ($href !== ''): ?>
          <a class="dashboard-alert-item <?= e($tone) ?>" href="<?= e($href) ?>">
            <span  aria-hidden="true"></span>
            <span class="dashboard-alert-text <?= e($tone) ?>"><?= e($text) ?></span>
          </a>
        <?php else: ?>
          <div class="dashboard-alert-item <?= e($tone) ?>">
            <span  aria-hidden="true"></span>
            <span class="dashboard-alert-text <?= e($tone) ?>"><?= e($text) ?></span>
          </div>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
</section>
