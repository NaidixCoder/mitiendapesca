<?php
// Espera $active = 'login' | 'registro'
$active = $active ?? 'login';
function tabClass($isActive){
  return $isActive
    ? 'px-4 py-2 rounded-[var(--radius-xs)] bg-brand text-white'
    : 'px-4 py-2 rounded-[var(--radius-xs)] hover:bg-brand/10';
}
?>
<nav class="flex items-center gap-2 mb-6">
  <a class="<?= tabClass($active==='login') ?>" href="<?= url('/login') ?>">Iniciar sesión</a>
  <a class="<?= tabClass($active==='registro') ?>" href="<?= url('/registro') ?>">Crear cuenta</a>
</nav>
