<section id="plantillas-listado" class="av3-attributes__tab-panel" role="tabpanel"
         aria-labelledby="av3-tab-templates" data-attr-panel="templates" data-role="attr-sets-root"
         <?= $activeTab !== 'templates' ? 'hidden' : '' ?>>
  <?php if (!$setsTablesOk): ?>
    <div class="av3-ui-empty av3-attributes__empty">
      <strong>Las plantillas no están disponibles</strong>
      <span>El esquema de plantillas necesita revisión. No apliques migraciones desde esta pantalla.</span>
    </div>
  <?php else: ?>
    <form class="av3-attributes__filters av3-attributes__filters--sets" method="get"
          action="<?= e(url('/admin/attributes')) ?>">
      <input type="hidden" name="tab" value="templates">
      <input type="hidden" name="q" value="<?= e((string)($q ?? '')) ?>">
      <label class="av3-ui-field av3-attributes__field av3-attributes__search">
        <span class="av3-ui-field-label">Buscar plantilla</span>
        <input class="av3-ui-control av3-attributes__input" type="search" name="sets_q"
               value="<?= e((string)$sets_q) ?>" placeholder="Nombre de plantilla">
      </label>
      <details class="av3-attributes__advanced" data-has-filters="<?= ($sets_category_id > 0 || $sets_brand_id > 0) ? 'true' : 'false' ?>" open>
        <summary>Filtros avanzados<?= ($sets_category_id > 0 || $sets_brand_id > 0) ? ' · activos' : '' ?></summary>
        <div class="av3-attributes__advanced-fields">
          <label class="av3-ui-field av3-attributes__field">
            <span class="av3-ui-field-label">Categoría</span>
            <select class="av3-ui-control av3-attributes__input" name="sets_category_id">
              <option value="0">Todas las categorías</option>
              <?php foreach ($categories as $c): $cid = (int)($c['id'] ?? 0); ?>
                <option value="<?= $cid ?>" <?= $sets_category_id === $cid ? 'selected' : '' ?>><?= e((string)($c['name'] ?? '')) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label class="av3-ui-field av3-attributes__field">
            <span class="av3-ui-field-label">Marca</span>
            <select class="av3-ui-control av3-attributes__input" name="sets_brand_id">
              <option value="0">Todas las marcas</option>
              <?php foreach ($brands as $b): $bid = (int)($b['id'] ?? 0); ?>
                <option value="<?= $bid ?>" <?= $sets_brand_id === $bid ? 'selected' : '' ?>><?= e((string)($b['name'] ?? '')) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        </div>
      </details>
      <div class="av3-ui-actions av3-attributes__filter-actions">
        <button class="av3-ui-btn av3-ui-btn--primary" type="submit">Filtrar</button>
        <?php if ($sets_q !== '' || $sets_category_id > 0 || $sets_brand_id > 0): ?>
          <a class="av3-ui-btn" href="<?= e(url('/admin/attributes') . '?' . http_build_query(['tab'=>'templates', 'q'=>$q])) ?>">Limpiar</a>
        <?php endif; ?>
      </div>
    </form>
    <?php if ($sets_category_id > 0 || $sets_brand_id > 0): ?>
      <div class="av3-attributes__applied" aria-label="Filtros activos">
        <span>Filtros activos:</span>
        <?php if ($sets_category_id > 0): ?>
          <?php foreach ($categories as $c): if ((int)$c['id'] !== $sets_category_id) continue; ?>
            <a href="<?= e(url('/admin/attributes') . '?' . http_build_query(['tab'=>'templates','q'=>$q,'sets_q'=>$sets_q,'sets_brand_id'=>$sets_brand_id])) ?>">Categoría: <?= e((string)$c['name']) ?> <span aria-hidden="true">×</span></a>
          <?php break; endforeach; ?>
        <?php endif; ?>
        <?php if ($sets_brand_id > 0): ?>
          <?php foreach ($brands as $b): if ((int)$b['id'] !== $sets_brand_id) continue; ?>
            <a href="<?= e(url('/admin/attributes') . '?' . http_build_query(['tab'=>'templates','q'=>$q,'sets_q'=>$sets_q,'sets_category_id'=>$sets_category_id])) ?>">Marca: <?= e((string)$b['name']) ?> <span aria-hidden="true">×</span></a>
          <?php break; endforeach; ?>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    <?php if (empty($sets)): ?>
      <div class="av3-ui-empty av3-attributes__empty" data-role="sets-empty">
        <strong>No se encontraron plantillas</strong>
        <span><?= ($sets_q !== '' || $sets_category_id > 0 || $sets_brand_id > 0) ? 'Probá con otros filtros.' : 'Las plantillas se crean desde Productos → Variantes.' ?></span>
      </div>
    <?php else: ?>
      <div class="av3-attributes__desktop" data-role="sets-desktop">
        <table class="av3-ui-table av3-attributes__list-table av3-attributes__list-table--sets">
          <thead>
            <tr><th scope="col">Plantilla</th><th scope="col">Categoría</th><th scope="col">Marca</th>
                <th scope="col" class="av3-attributes__number">Valores</th><th scope="col" class="av3-attributes__actions-col">Acciones</th></tr>
          </thead>
          <tbody>
            <?php foreach ($sets as $s): $sid = (int)($s['id'] ?? 0); ?>
              <tr data-set-row="<?= $sid ?>">
                <td><strong data-set-field="name"><?= e((string)($s['name'] ?? '')) ?></strong></td>
                <td data-set-field="category_name"><?= e((string)($s['category_name'] ?? '—')) ?></td>
                <td data-set-field="brand_name"><?= e((string)($s['brand_name'] ?? '—')) ?></td>
                <td class="av3-attributes__number" data-set-field="items_count"><?= (int)($s['items_count'] ?? 0) ?></td>
                <td class="av3-attributes__actions-col">
                  <div class="av3-attributes__set-actions">
                    <button type="button" class="av3-ui-btn av3-ui-btn--small" data-role="set-view" data-set-id="<?= $sid ?>" aria-label="Ver plantilla <?= e((string)($s['name'] ?? '')) ?>">Ver</button>
                    <details class="av3-attributes__menu">
                      <summary aria-label="Más acciones para <?= e((string)($s['name'] ?? '')) ?>">···</summary>
                      <div class="av3-attributes__menu-items">
                        <button type="button" data-role="set-edit" data-set-id="<?= $sid ?>">Editar</button>
                        <button type="button" class="av3-attributes__danger" data-role="set-delete" data-set-id="<?= $sid ?>">Eliminar</button>
                      </div>
                    </details>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="av3-attributes__mobile-list" aria-label="Plantillas de variantes para móvil" data-role="sets-mobile">
        <?php foreach ($sets as $s): $sid = (int)($s['id'] ?? 0); ?>
          <article class="av3-attributes__mobile-item" data-set-row="<?= $sid ?>">
            <div class="av3-attributes__mobile-top">
              <strong data-set-field="name"><?= e((string)($s['name'] ?? '')) ?></strong>
              <button class="av3-attributes__action-link" type="button" data-role="set-view" data-set-id="<?= $sid ?>"
                      aria-label="Ver plantilla <?= e((string)($s['name'] ?? '')) ?>">Ver <span aria-hidden="true">›</span></button>
            </div>
            <div class="av3-attributes__mobile-sub">
              <span><span data-set-field="category_name"><?= e((string)($s['category_name'] ?? '—')) ?></span> · <span data-set-field="brand_name"><?= e((string)($s['brand_name'] ?? '—')) ?></span></span>
            </div>
            <div class="av3-attributes__mobile-bottom">
              <span><span data-set-field="items_count"><?= (int)($s['items_count'] ?? 0) ?></span> valores</span>
              <details class="av3-attributes__menu">
                <summary aria-label="Más acciones para <?= e((string)($s['name'] ?? '')) ?>">···</summary>
                <div class="av3-attributes__menu-items">
                  <button type="button" data-role="set-edit" data-set-id="<?= $sid ?>">Editar</button>
                  <button type="button" class="av3-attributes__danger" data-role="set-delete" data-set-id="<?= $sid ?>">Eliminar</button>
                </div>
              </details>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
      <div class="av3-ui-empty av3-attributes__empty" data-role="sets-empty" hidden>
        <strong>No quedan plantillas con estos filtros</strong>
        <span>Podés crear nuevas desde Productos → Variantes.</span>
      </div>
    <?php endif; ?>
    <footer class="av3-attributes__footer">
      <span data-role="sets-count"><?= count($sets) ?> <?= count($sets) === 1 ? 'plantilla mostrada' : 'plantillas mostradas' ?></span>
      <?php if (count($sets) >= 120): ?><span>Máximo de 120 resultados. Usá filtros para acotar la búsqueda.</span><?php endif; ?>
    </footer>
    <div hidden data-role="attr-sets-meta"
         data-endpoint-items="<?= e(url('/admin/attributes/templates/items-fragment')) ?>"
         data-endpoint-delete="<?= e(url('/admin/attributes/templates/delete.json')) ?>"
         data-endpoint-edit="<?= e(url('/admin/attributes/templates/edit-fragment')) ?>"
         data-endpoint-save="<?= e(url('/admin/attributes/templates/save.json')) ?>"
         data-csrf="<?= e(csrf_token()) ?>"></div>
    <div class="av3-attributes__modal" data-role="set-modal" hidden>
      <div class="av3-attributes__modal-backdrop" data-role="set-modal-backdrop"></div>
      <div class="av3-attributes__modal-dialog" role="dialog" aria-modal="true" aria-label="Plantilla de variantes" tabindex="-1" data-role="set-modal-dialog">
        <div class="av3-attributes__modal-header">
          <span class="av3-attributes__modal-heading">Plantilla de variantes</span>
          <button class="av3-ui-btn av3-ui-btn--small" type="button" data-role="set-modal-close" aria-label="Cerrar diálogo">Cerrar</button>
        </div>
        <div class="av3-attributes__modal-body" data-role="set-modal-body" aria-live="polite"></div>
      </div>
    </div>
  <?php endif; ?>
</section>
