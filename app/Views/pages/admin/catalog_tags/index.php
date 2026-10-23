<?php
$pageTitle = 'Etiquetas del catálogo';
require BASE_PATH . '/app/Views/partials/admin/subnav.php';
$crumbs = admin_crumbs('catalog-tags.index');
$groups = is_array($groups ?? null) ? $groups : [];
$tags = is_array($tags ?? null) ? $tags : [];
$groupRow = is_array($groupRow ?? null) ? $groupRow : null;
$tagRow = is_array($tagRow ?? null) ? $tagRow : null;
$groupCount = count($groups);
$tagCount = count($tags);
$usedCount = count(array_filter($tags, static fn($tag) => (int)($tag['products_count'] ?? 0) + (int)($tag['variants_count'] ?? 0) > 0));
?>
<link rel="stylesheet" href="<?= e(asset('css/admin-v3/pages/catalog-tags.css')) ?>">
<section class="admin-surface">
  <div class="av3-wrap av3-layout--operational av3-catalog-tags">
    <?php
    $adminHeaderTitle = 'Etiquetas del catálogo';
    $adminHeaderDescription = 'Organizá grupos y etiquetas para clasificar y filtrar productos.';
    $adminHeaderActions = '';
    $adminHeaderBreadcrumbs = $crumbs ?: [['label' => 'Inicio', 'href' => url('/admin')], ['label' => 'Etiquetas']];
    require BASE_PATH . '/app/Views/partials/admin/page_header.php';
    ?>
    <?php if ($message = flash('ok')): ?><div class="av3-alert ok" role="status"><?= e((string)$message) ?></div><?php endif; ?>
    <?php if ($message = flash('error')): ?><div class="av3-alert error" role="alert"><?= e((string)$message) ?></div><?php endif; ?>

    <div class="av3-catalog-tags__stats" aria-label="Resumen de etiquetas">
      <div class="av3-ui-card av3-catalog-tags__stat"><span>Grupos</span><strong><?= $groupCount ?></strong></div>
      <div class="av3-ui-card av3-catalog-tags__stat"><span>Etiquetas</span><strong><?= $tagCount ?></strong></div>
      <div class="av3-ui-card av3-catalog-tags__stat"><span>Etiquetas utilizadas</span><strong><?= $usedCount ?></strong></div>
    </div>

    <div class="av3-catalog-tags__columns">
      <section class="av3-ui-card av3-catalog-tags__panel" aria-labelledby="group-heading">
        <div class="av3-catalog-tags__heading">
          <div><h2 id="group-heading"><?= $groupRow ? 'Editar grupo' : 'Crear grupo' ?></h2><p>Los grupos organizan etiquetas relacionadas.</p></div>
          <?php if ($groupRow): ?><a class="av3-ui-btn av3-ui-btn--small" href="<?= e(url('/admin/catalog-tags')) ?>">Nuevo grupo</a><?php endif; ?>
        </div>
        <form method="post" action="<?= e(url('/admin/catalog-tags/group/save')) ?>" class="av3-catalog-tags__form">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)($groupRow['id'] ?? 0) ?>">
          <label class="av3-ui-field av3-catalog-tags__full"><span class="av3-ui-field-label">Nombre del grupo</span><input class="av3-ui-control" name="name" required maxlength="80" value="<?= e((string)($groupRow['name'] ?? '')) ?>" placeholder="Ej.: Modalidad"></label>
          <label class="av3-ui-field"><span class="av3-ui-field-label">Slug</span><input class="av3-ui-control" name="slug" maxlength="120" value="<?= e((string)($groupRow['slug'] ?? '')) ?>" placeholder="Se genera a partir del nombre"><span class="av3-ui-field-hint">Opcional; debe ser único.</span></label>
          <label class="av3-ui-field"><span class="av3-ui-field-label">Orden</span><input class="av3-ui-control" type="number" min="0" max="2147483647" name="sort" value="<?= (int)($groupRow['sort'] ?? 0) ?>"></label>
          <label class="av3-ui-field av3-catalog-tags__full"><span class="av3-ui-field-label">Estado</span><select class="av3-ui-control" name="is_active"><option value="1" <?= !isset($groupRow['is_active']) || !empty($groupRow['is_active']) ? 'selected' : '' ?>>Activo</option><option value="0" <?= isset($groupRow['is_active']) && empty($groupRow['is_active']) ? 'selected' : '' ?>>Inactivo</option></select></label>
          <div class="av3-catalog-tags__actions"><button class="av3-ui-btn av3-ui-btn--primary" type="submit"><?= $groupRow ? 'Guardar cambios' : 'Crear grupo' ?></button></div>
        </form>
        <div class="av3-catalog-tags__table-heading"><h3>Grupos existentes</h3><span><?= $groupCount ?> en total</span></div>
        <?php if ($groups === []): ?><div class="av3-ui-empty">Todavía no hay grupos. Creá uno para comenzar.</div><?php else: ?>
        <div class="av3-ui-table-scroll"><table class="av3-ui-table"><thead><tr><th>Grupo</th><th>Etiquetas</th><th>Estado</th><th>Acción</th></tr></thead><tbody>
        <?php foreach ($groups as $group): ?><tr><td data-label="Grupo"><strong><?= e((string)$group['name']) ?></strong><small><?= e((string)$group['slug']) ?> · Orden <?= (int)$group['sort'] ?></small></td><td data-label="Etiquetas"><?= (int)$group['tags_count'] ?></td><td data-label="Estado"><span class="av3-ui-badge <?= !empty($group['is_active']) ? '' : 'av3-ui-badge--neutral' ?>"><?= !empty($group['is_active']) ? 'Activo' : 'Inactivo' ?></span></td><td data-label="Acción"><a class="av3-ui-btn av3-ui-btn--small" href="<?= e(url('/admin/catalog-tags?group_id=' . (int)$group['id'])) ?>">Editar</a></td></tr><?php endforeach; ?>
        </tbody></table></div><?php endif; ?>
      </section>

      <section class="av3-ui-card av3-catalog-tags__panel" aria-labelledby="tag-heading">
        <div class="av3-catalog-tags__heading">
          <div><h2 id="tag-heading"><?= $tagRow ? 'Editar etiqueta' : 'Crear etiqueta' ?></h2><p>Las etiquetas se pueden asignar a productos y variantes.</p></div>
          <?php if ($tagRow): ?><a class="av3-ui-btn av3-ui-btn--small" href="<?= e(url('/admin/catalog-tags')) ?>">Nueva etiqueta</a><?php endif; ?>
        </div>
        <form method="post" action="<?= e(url('/admin/catalog-tags/tag/save')) ?>" class="av3-catalog-tags__form">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)($tagRow['id'] ?? 0) ?>">
          <label class="av3-ui-field av3-catalog-tags__full"><span class="av3-ui-field-label">Grupo</span><select class="av3-ui-control" name="group_id" required><option value="">Seleccionar grupo</option><?php foreach ($groups as $group): ?><option value="<?= (int)$group['id'] ?>" <?= (int)($tagRow['group_id'] ?? 0) === (int)$group['id'] ? 'selected' : '' ?>><?= e((string)$group['name']) ?></option><?php endforeach; ?></select></label>
          <label class="av3-ui-field av3-catalog-tags__full"><span class="av3-ui-field-label">Nombre de etiqueta</span><input class="av3-ui-control" name="name" required maxlength="80" value="<?= e((string)($tagRow['name'] ?? '')) ?>" placeholder="Ej.: Baitcast"></label>
          <label class="av3-ui-field"><span class="av3-ui-field-label">Slug</span><input class="av3-ui-control" name="slug" maxlength="120" value="<?= e((string)($tagRow['slug'] ?? '')) ?>" placeholder="Se genera a partir del nombre"><span class="av3-ui-field-hint">Único dentro de cada grupo.</span></label>
          <label class="av3-ui-field"><span class="av3-ui-field-label">Orden</span><input class="av3-ui-control" type="number" min="0" max="2147483647" name="sort" value="<?= (int)($tagRow['sort'] ?? 0) ?>"></label>
          <label class="av3-ui-field av3-catalog-tags__full"><span class="av3-ui-field-label">Estado</span><select class="av3-ui-control" name="is_active"><option value="1" <?= !isset($tagRow['is_active']) || !empty($tagRow['is_active']) ? 'selected' : '' ?>>Activo</option><option value="0" <?= isset($tagRow['is_active']) && empty($tagRow['is_active']) ? 'selected' : '' ?>>Inactivo</option></select></label>
          <div class="av3-catalog-tags__actions"><button class="av3-ui-btn av3-ui-btn--primary" type="submit" <?= $groups === [] ? 'disabled' : '' ?>><?= $tagRow ? 'Guardar cambios' : 'Crear etiqueta' ?></button></div>
        </form>
        <div class="av3-catalog-tags__table-heading"><h3>Etiquetas existentes</h3><span><?= $tagCount ?> en total</span></div>
        <?php if ($tags === []): ?><div class="av3-ui-empty">Todavía no hay etiquetas. Agregá una al grupo correspondiente.</div><?php else: ?>
        <div class="av3-ui-table-scroll"><table class="av3-ui-table"><thead><tr><th>Etiqueta / grupo</th><th>Uso (producto / variante)</th><th>Estado</th><th>Acción</th></tr></thead><tbody>
        <?php foreach ($tags as $tag): ?><tr><td data-label="Etiqueta"><strong><?= e((string)$tag['name']) ?></strong><small><?= e((string)$tag['group_name']) ?> · <?= e((string)$tag['slug']) ?> · Orden <?= (int)$tag['sort'] ?></small></td><td data-label="Producto / variante"><?= (int)$tag['products_count'] ?> / <?= (int)($tag['variants_count'] ?? 0) ?></td><td data-label="Estado"><span class="av3-ui-badge <?= !empty($tag['is_active']) ? '' : 'av3-ui-badge--neutral' ?>"><?= !empty($tag['is_active']) ? 'Activo' : 'Inactivo' ?></span></td><td data-label="Acción"><a class="av3-ui-btn av3-ui-btn--small" href="<?= e(url('/admin/catalog-tags?tag_id=' . (int)$tag['id'])) ?>">Editar</a></td></tr><?php endforeach; ?>
        </tbody></table></div><?php endif; ?>
      </section>
    </div>
  </div>
</section>
