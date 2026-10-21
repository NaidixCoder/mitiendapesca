<?php
// Flash inline (consumido una sola vez), pensado para mostrar dentro del contenido.
// Soporta claves: ok/warn/error + legacy success/info/danger.

$items = [];

$ok    = flash('ok');     if (!$ok)    { $ok    = flash('success'); }
$warn  = flash('warn');   if (!$warn)  { $warn  = flash('info'); }
$error = flash('error');  if (!$error) { $error = flash('danger'); }

if ($ok)    $items[] = ['type' => 'ok', 'text' => (string)$ok];
if ($warn)  $items[] = ['type' => 'warn', 'text' => (string)$warn];
if ($error) $items[] = ['type' => 'error', 'text' => (string)$error];

if (!$items) return;

$tone = static function(string $t): string {
    return match ($t) {
        'ok' => 'is-ok',
        'warn' => 'is-warn',
        default => 'is-error',
    };
};
?>

<div class="site-inline-flash" data-inline-flash>
  <?php foreach ($items as $it): ?>
    <div
      class="site-inline-flash__item <?= e($tone((string)$it['type'])) ?>"
      role="status"
      aria-live="polite"
      data-inline-flash-item
      data-flash-type="<?= e((string)$it['type']) ?>"
    >
      <?= e((string)$it['text']) ?>
    </div>
  <?php endforeach; ?>
</div>
