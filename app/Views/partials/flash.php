<?php
// Flash bridge (legacy -> v2 toast host).
// Consume flash session keys and expose as JSON for assets/js/core/flash.js.

$toasts = [];

$ok = flash('ok');
if (!$ok) { $ok = flash('success'); }
$warn = flash('warn');
if (!$warn) { $warn = flash('info'); }
$error = flash('error');
if (!$error) { $error = flash('danger'); }

if ($ok) {
    $toasts[] = ['type' => 'ok', 'text' => (string)$ok];
}
if ($warn) {
    $toasts[] = ['type' => 'warn', 'text' => (string)$warn];
}
if ($error) {
    $toasts[] = ['type' => 'error', 'text' => (string)$error];
}

if (!$toasts) return;
?>
<script id="tp-flash-data" type="application/json"><?= e(json_encode($toasts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]') ?></script>
