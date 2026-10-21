<section class="av3-card" data-role="attr-sets-root">
  <div class="av3-flex av3-items-start av3-justify-between av3-gap-3">
    <div>
      <div class="av3-type-body av3-weight-strong">Plantillas de variantes</div>
      <div class="av3-type-caption av3-text-var-admin-muted">Biblioteca para reusar atributos y valores. Se guardan como <strong>Categoría + Marca</strong> (anti-contaminación).</div>
    </div>
  </div>

  <?php if (!$setsTablesOk): ?>
    <div class="av3-alert warn av3-space-top-4">No están disponibles las tablas de plantillas (<code>variant_value_sets</code>). Ejecutá la migración correspondiente.</div>
  <?php else: ?>

    <form class="av3-space-top-4" method="get" action="<?= url('/admin/attributes') ?>">
      <input type="hidden" name="q" value="<?= e($q) ?>">
      <div class="av3-grid av3-md-grid-cols-6 av3-gap-3">
        <div class="av3-md-col-span-2"><label class="label">Buscar plantilla</label><input class="input" type="text" name="sets_q" value="<?= e($sets_q) ?>" placeholder="Nombre de plantilla"></div>
        <div class="av3-md-col-span-2"><label class="label">Categoría</label><select class="input" name="sets_category_id"><option value="0">Todas</option><?php foreach ($categories as $c): $cid=(int)($c['id'] ?? 0); ?><option value="<?= $cid ?>" <?= $sets_category_id===$cid ? 'selected' : '' ?>><?= e($c['name'] ?? '') ?></option><?php endforeach; ?></select></div>
        <div class="av3-md-col-span-2"><label class="label">Marca</label><select class="input" name="sets_brand_id"><option value="0">Todas</option><?php foreach ($brands as $b): $bid=(int)($b['id'] ?? 0); ?><option value="<?= $bid ?>" <?= $sets_brand_id===$bid ? 'selected' : '' ?>><?= e($b['name'] ?? '') ?></option><?php endforeach; ?></select></div>
        <div class="av3-flex av3-items-end av3-gap-2 av3-md-col-span-6"><button class="btn-primary" type="submit">Filtrar</button><a class="btn-secondary" href="<?= url('/admin/attributes') ?>">Limpiar</a></div>
      </div>
    </form>

    <div class="av3-table av3-space-top-4 max-h-[420px] av3-overflow-auto">
      <table class="av3-width-full av3-type-body">
        <thead class="av3-sticky top-0 av3-surface-white"><tr><th class="av3-text-left">Plantilla</th><th class="av3-text-left">Categoría</th><th class="av3-text-left">Marca</th><th class="av3-text-right">Items</th><th class="av3-text-right">Acciones</th></tr></thead>
        <tbody>
        <?php if (!empty($sets)): foreach ($sets as $s): $sid=(int)($s['id'] ?? 0); ?>
          <tr data-set-row="<?= $sid ?>">
            <td class="av3-weight-medium"><?= e($s['name'] ?? '') ?></td>
            <td class="av3-text-var-admin-muted"><?= e($s['category_name'] ?? '—') ?></td>
            <td class="av3-text-var-admin-muted"><?= e($s['brand_name'] ?? '—') ?></td>
            <td class="av3-text-right"><?= (int)($s['items_count'] ?? 0) ?></td>
            <td class="av3-text-right"><button type="button" class="btn-secondary" data-role="set-view" data-set-id="<?= $sid ?>">Ver</button><button type="button" class="btn-secondary" data-role="set-edit" data-set-id="<?= $sid ?>">Editar</button><button type="button" class="btn-danger" data-role="set-delete" data-set-id="<?= $sid ?>">Eliminar</button></td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="5" class="av3-pad-y6 av3-text-center av3-text-var-admin-muted">Todavía no hay plantillas para estos filtros.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="av3-help av3-space-top-3">Tip: Creá plantillas desde un producto en <strong>Productos → Editar → Variantes</strong> usando <strong>Guardar como plantilla</strong>.</div>

    <div class="av3-hidden" data-role="attr-sets-meta"
         data-endpoint-items="<?= e(url('/admin/attributes/templates/items-fragment')) ?>"
         data-endpoint-delete="<?= e(url('/admin/attributes/templates/delete.json')) ?>"
         data-endpoint-edit="<?= e(url('/admin/attributes/templates/edit-fragment')) ?>"
         data-endpoint-save="<?= e(url('/admin/attributes/templates/save.json')) ?>"
         data-csrf="<?= e(csrf_token()) ?>"></div>

    <div class="av3-fixed inset-0 av3-z--90 av3-hidden" data-role="set-modal">
      <div class="av3-absolute inset-0 av3-bg-black-30" data-role="set-modal-backdrop"></div>
      <div class="av3-absolute inset-0 av3-flex av3-items-center av3-justify-center av3-pad-4">
        <div class="av3-width-full av3-width-max-2xl av3-radius-xl av3-surface-white av3-shadow-xl av3-border av3-border-var-admin-border">
          <div class="av3-relative">
            <button type="button" class="btn-icon av3-absolute right-3 top-3" data-role="set-modal-close">✕</button>
            <div class="av3-pad-5" data-role="set-modal-body"><div class="av3-type-body av3-text-var-admin-muted">Cargando…</div></div>
          </div>
          <div class="av3-pad-x5 av3-pad-y4 av3-border-top av3-border-var-admin-border av3-flex av3-justify-end av3-gap-2"><button type="button" class="btn-secondary" data-role="set-modal-close-2">Cerrar</button></div>
        </div>
      </div>
    </div>

  <?php endif; ?>
</section>
