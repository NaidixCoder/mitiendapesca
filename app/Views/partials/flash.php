<?php
$ok    = flash('ok');
$warn  = flash('warn');
$error = flash('error');
if (!$ok && !$warn && !$error) return;

$toasts = [];
if ($ok)    $toasts[] = ['ok',    $ok];
if ($warn)  $toasts[] = ['warn',  $warn];
if ($error) $toasts[] = ['error', $error];
?>
<div id="toast-root" class="fixed top-4 right-4 z-[9999] w-[min(92vw,22rem)] space-y-3 pointer-events-none select-none">

    <?php foreach ($toasts as [$type, $msg]): ?>
        <?php
        // Paletas por tipo (usando variables y utilidades)
        if ($type==='ok') {
            $tone = 'border-emerald-400/35 text-emerald-50 bg-emerald-500/15';
            $bar  = 'bg-emerald-400/90';
            $icon = '<svg viewBox="0 0 24 24" class="w-5 h-5"><path fill="currentColor" d="M9 16.17 4.83 12 3.41 13.41 9 19l12-12-1.41-1.41z"/></svg>';
        } elseif ($type==='warn') {
            $tone = 'border-amber-400/40 text-amber-50 bg-amber-500/15';
            $bar  = 'bg-amber-400/90';
            $icon = '<svg viewBox="0 0 24 24" class="w-5 h-5"><path fill="currentColor" d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>';
        } else {
            $tone = 'border-[color:var(--danger)]/45 text-white bg-[color:var(--danger)]/15';
            $bar  = 'bg-[color:var(--danger)]';
            $icon = '<svg viewBox="0 0 24 24" class="w-5 h-5"><path fill="currentColor" d="M11 15h2V7h-2v8zm0 4h2v-2h-2v2z"/></svg>';
        }
        ?>
        <div class="toast group pointer-events-auto rounded-[var(--radius-sm)] border <?= $tone ?> shadow-xl backdrop-blur-sm ring-1 ring-black/5 will-change-transform will-change-opacity"
            role="status" aria-live="polite" data-autohide="true" data-duration="5000">

        <div class="px-4 py-3">
            <div class="flex items-start gap-3">
            <div class="shrink-0 opacity-90"><?= $icon ?></div>
            <div class="flex-1 leading-relaxed text-[0.95rem]"><?= e($msg) ?></div>
            <button type="button"
                    class="toast-close shrink-0 ml-1 rounded-[6px] px-2 py-1 text-sm opacity-70 hover:opacity-100 hover:bg-white/10 transition"
                    aria-label="Cerrar">✕</button>
            </div>
        </div>

        <!-- Progress bar -->
        <div class="h-1 w-full overflow-hidden rounded-b-[var(--radius-sm)]">
            <div class="toast-progress h-full <?= $bar ?>" style="width:100%;"></div>
        </div>
        </div>
    <?php endforeach; ?>

</div>
