<?php foreach (['ok' => 'success', 'error' => 'error', 'warn' => 'warning'] as $type => $tone): ?>
  <?php if ($message = flash($type)): ?>
    <div class="av3-imports__notice av3-imports__notice--<?= e($tone) ?>" role="<?= $tone === 'error' ? 'alert' : 'status' ?>"><?= e((string)$message) ?></div>
  <?php endif; ?>
<?php endforeach; ?>
