<?php if ($m = flash('ok')): ?>
  <div class="alert-success"><?= e($m) ?></div>
<?php endif; ?>
<?php if ($m = flash('error')): ?>
  <div class="alert-error"><?= e($m) ?></div>
<?php endif; ?>
