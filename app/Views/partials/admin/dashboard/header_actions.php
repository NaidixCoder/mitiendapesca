<?php ?>
<div class="flex flex-wrap items-center justify-between gap-3">
  <div>
    <h1 class="text-2xl font-semibold">Dashboard</h1>
    <p class="text-sm" style="color:var(--muted)">Resumen operativo y accesos rápidos</p>
  </div>

  <nav class="flex flex-wrap gap-2">
    <a class="btn-primary"   href="<?= url('/admin/products') ?>">Productos</a>
    <a class="btn-secondary" href<?= '="'.url('/admin/products/new').'"' ?>>Nuevo producto</a>
    <a class="btn-secondary" href="<?= url('/admin/brands') ?>">Marcas</a>
    <a class="btn-secondary" href="<?= url('/admin/categories') ?>">Categorías</a>
    <a class="btn-secondary" href="<?= url('/admin/imports') ?>">Imports</a>
    <a class="btn-secondary" href="<?= url('/admin/users') ?>">Usuarios</a>
    <a class="btn-secondary" href="<?= url('/admin/settings') ?>">Ajustes</a>
  </nav>
</div>

