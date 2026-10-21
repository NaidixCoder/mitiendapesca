<section class="av3-card">
  <div class="av3-flex av3-items-center av3-justify-between av3-gap-3">
    <div>
      <div class="av3-type-body av3-weight-strong">Atributos</div>
      <div class="av3-type-caption av3-text-var-admin-muted">Crea y edita opciones reutilizables para variantes reales. Los valores se gestionan dentro de cada atributo.</div>
    </div>
  </div>

  <form class="av3-space-top-4" method="get" action="<?= url('/admin/attributes') ?>">
    <div class="av3-grid av3-md-grid-cols-4 av3-gap-3">
      <div class="av3-md-col-span-3">
        <label class="label">Buscar atributo</label>
        <input class="input" type="text" name="q" value="<?= e($q) ?>" placeholder="Nombre o slug">
      </div>
      <div class="av3-flex av3-items-end av3-gap-2">
        <button class="btn-primary" type="submit">Filtrar</button>
        <a class="btn-secondary" href="<?= url('/admin/attributes') ?>">Limpiar</a>
      </div>
    </div>
  </form>

  <div class="av3-table av3-space-top-4 max-h-[420px] av3-overflow-auto">
    <table class="av3-width-full av3-type-body">
      <thead class="av3-sticky top-0 av3-surface-white">
        <tr><th class="av3-text-left">Nombre</th><th class="av3-text-left">Slug</th><th class="av3-text-left">Tipo</th><th class="av3-text-right">Acciones</th></tr>
      </thead>
      <tbody>
      <?php if (!empty($rows)): foreach ($rows as $r): $id=(int)($r['id'] ?? 0); ?>
        <tr>
          <td class="av3-weight-medium"><?= e($r['name'] ?? '') ?></td>
          <td class="av3-text-var-admin-muted"><?= e($r['slug'] ?? '') ?></td>
          <td><?= e($r['type'] ?? '') ?></td>
          <td class="av3-text-right"><a class="btn-secondary" href="<?= url('/admin/attributes/edit?id='.$id) ?>">Editar</a></td>
        </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="4" class="av3-pad-y6 av3-text-center av3-text-var-admin-muted">Sin resultados.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="av3-help av3-space-top-3">Tip: carga valores por atributo, por ejemplo <strong>Color</strong> -> Rojo/Verde o <strong>Medida</strong> -> 2.70m/3.60m. Luego asignalos desde <strong>Productos</strong> -> Editar -> pestaña <strong>Variantes</strong>.</div>
</section>
