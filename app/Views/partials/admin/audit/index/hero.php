<div class="audit-hero av3-card">
  <div class="av3-tabs">
    <a class="av3-tab active" href="<?= base_url('/admin/audit') ?>">Actividad</a>
    <a class="av3-tab" href="<?= base_url('/admin/audit/logins') ?>">Logins</a>
  </div>
  <div class="audit-hero__head">
    <div class="audit-hero__kpis" aria-label="Resumen">
      <div class="audit-kpi"><div class="audit-kpi__k">Eventos</div><div class="audit-kpi__v"><?= (int)$total ?></div></div>
      <div class="audit-kpi"><div class="audit-kpi__k">Pagina</div><div class="audit-kpi__v"><?= (int)$page ?><span class="audit-muted">/<?= (int)$pages ?></span></div></div>
    </div>

    <?php if (!empty($groupRid)): ?>
      <div class="av3-card" style="margin:12px auto 14px; max-width:980px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:12px;">
          <div>
            <div style="font-weight:700;">Detalle de accion agrupada</div>
            <div class="audit-quiet" style="margin-top:2px;">Request: <span class="audit-mono"><?= htmlspecialchars((string)$groupRid) ?></span> - No incluye <strong>stock</strong> (se muestra independiente).</div>
            <?php if (!empty($groupError)): ?>
              <div class="audit-quiet" style="margin-top:6px;"><strong style="color:var(--admin-danger,#ef4444)">!</strong> <?= htmlspecialchars((string)$groupError) ?></div>
            <?php endif; ?>
          </div>
          <a class="av3-btn" href="<?= htmlspecialchars(base_url('/admin/audit')) ?>">Volver</a>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <form method="get" class="audit-toolbar" aria-label="Filtros de auditoria" data-audit-filters>
    <div class="audit-toolbar__main">
      <label class="audit-field audit-field--grow"><span>Buscar</span><input name="q" class="audit-input" value="<?= htmlspecialchars($q) ?>" placeholder="Pedido #9 - Producto 12 - Usuario Matias - accion..."></label>
      <label class="audit-field"><span>Rango</span><select name="range" class="audit-input" data-audit-range><?php foreach ($rangeOptions as $k => $label): ?><option value="<?= htmlspecialchars($k) ?>" <?= $range==$k?'selected':'' ?>><?= htmlspecialchars($label) ?></option><?php endforeach; ?></select></label>
      <label class="audit-field"><span>Por pagina</span><select name="per_page" class="audit-input"><?php foreach ([25,50,100] as $pp): ?><option value="<?= (int)$pp ?>" <?= $perPage===$pp?'selected':'' ?>><?= (int)$pp ?></option><?php endforeach; ?></select></label>
      <div class="audit-toolbar__actions"><button class="btn btn-primary" type="submit">Aplicar</button><a class="btn btn-ghost" href="<?= base_url('/admin/audit') ?>">Limpiar</a></div>
    </div>

    <details class="audit-advanced" <?= $hasAdvancedFilters ? 'open' : '' ?> data-audit-advanced>
      <summary class="audit-advanced__summary"><span>Filtros avanzados</span><?php if ($hasAdvancedFilters): ?><span class="audit-advanced__hint">(activos)</span><?php endif; ?></summary>
      <div class="audit-advanced__grid">
        <label class="audit-field"><span>Actor</span><input name="actor" class="audit-input" value="<?= htmlspecialchars($actor) ?>" placeholder="Nombre, email o #ID"></label>
        <label class="audit-field"><span>Tipo</span><select name="tipo" class="audit-input"><option value="todos" <?= $tipo=='todos'?'selected':'' ?>>Todos</option><option value="order" <?= $tipo=='order'?'selected':'' ?>>Pedidos</option><option value="product" <?= $tipo=='product'?'selected':'' ?>>Productos</option><option value="category" <?= $tipo=='category'?'selected':'' ?>>Categorias</option><option value="brand" <?= $tipo=='brand'?'selected':'' ?>>Marcas</option><option value="attribute" <?= $tipo=='attribute'?'selected':'' ?>>Atributos</option><option value="attribute_value" <?= $tipo=='attribute_value'?'selected':'' ?>>Valores</option><option value="user" <?= $tipo=='user'?'selected':'' ?>>Usuarios</option></select></label>
        <label class="audit-field"><span>Accion</span><select name="action" class="audit-input"><option value="" <?= $action===''?'selected':'' ?>>Todas</option><?php foreach ($actionGroups as $groupLabel => $opts): ?><optgroup label="<?= htmlspecialchars($groupLabel) ?>"><?php foreach ($opts as $k => $label): ?><option value="<?= htmlspecialchars($k) ?>" <?= $action===$k?'selected':'' ?>><?= htmlspecialchars($label) ?></option><?php endforeach; ?></optgroup><?php endforeach; ?></select></label>
        <div class="audit-advanced__dates" data-audit-dates>
          <label class="audit-field"><span>Desde</span><input type="date" name="from" class="audit-input" value="<?= htmlspecialchars($from) ?>" data-audit-from></label>
          <label class="audit-field"><span>Hasta</span><input type="date" name="to" class="audit-input" value="<?= htmlspecialchars($to) ?>" data-audit-to></label>
        </div>
        <div class="audit-advanced__note">El rango personalizado se habilita cuando <strong>Rango = Personalizado</strong>.</div>
      </div>
    </details>
  </form>

  <div class="audit-presets" aria-label="Accesos rapidos">
    <span class="audit-presets__label">Atajos:</span>
    <a class="audit-chip" href="<?= base_url('/admin/audit?range=today') ?>">Hoy</a>
    <a class="audit-chip" href="<?= base_url('/admin/audit?tipo=order&range=7') ?>">Pedidos (7 dias)</a>
    <a class="audit-chip" href="<?= base_url('/admin/audit?action=stock_updated&range=30') ?>">Stock (30 dias)</a>
    <a class="audit-chip" href="<?= base_url('/admin/audit?action=product_images_bulk_assign&range=30') ?>">Imagenes masivo</a>
    <a class="audit-chip" href="<?= base_url('/admin/audit?tipo=user&range=30') ?>">Usuarios</a>
    <a class="audit-chip" href="<?= base_url('/admin/audit?action=password_reset_requested&range=30') ?>">Seguridad</a>
    <a class="audit-chip audit-chip--ghost" href="<?= base_url('/admin/audit?range=all') ?>">Todo</a>
  </div>
</div>
