<section id="atributos-listado" class="av3-attributes__tab-panel" role="tabpanel" aria-labelledby="av3-tab-attributes"
  data-attr-panel="attributes" <?= $activeTab !== 'attributes' ? 'hidden' : '' ?>>
  <form class="av3-attributes__filters av3-attributes__filters--attributes" method="get" action="<?= e(url('/admin/attributes')) ?>">
    <input type="hidden" name="tab" value="attributes">
    <input type="hidden" name="sets_q" value="<?= e((string)($sets_q ?? '')) ?>">
    <input type="hidden" name="sets_category_id" value="<?= (int)($sets_category_id ?? 0) ?>">
    <input type="hidden" name="sets_brand_id" value="<?= (int)($sets_brand_id ?? 0) ?>">
    <label class="av3-ui-field av3-attributes__field av3-attributes__search">
      <span class="av3-ui-field-label">Buscar atributo</span>
      <input class="av3-ui-control av3-attributes__input" type="search" name="q" value="<?= e($q) ?>"
             placeholder="Nombre, código o slug">
    </label>
    <div class="av3-ui-actions av3-attributes__filter-actions">
      <button class="av3-ui-btn av3-ui-btn--primary" type="submit">Buscar</button>
      <?php if ($q !== ''): ?>
        <a class="av3-ui-btn" href="<?= e(url('/admin/attributes') . '?' . http_build_query(['tab'=>'attributes','sets_q'=>$sets_q,'sets_category_id'=>$sets_category_id,'sets_brand_id'=>$sets_brand_id])) ?>">Limpiar</a>
      <?php endif; ?>
    </div>
  </form>
  <?php if (!$rows): ?>
    <div class="av3-ui-empty av3-attributes__empty">
      <strong>No se encontraron atributos</strong>
      <span><?= $q !== '' ? 'Probá con otro nombre, código o slug.' : 'Creá tu primer atributo para comenzar.' ?></span>
      <?php if ($q !== ''): ?><a href="<?= e(url('/admin/attributes?tab=attributes')) ?>">Ver todos los atributos</a><?php endif; ?>
    </div>
  <?php else: ?>
    <div class="av3-attributes__desktop">
      <table class="av3-ui-table av3-attributes__list-table">
        <thead>
          <tr><th scope="col">Atributo</th><th scope="col">Tipo</th><th scope="col">Slug</th><th scope="col" class="av3-attributes__actions-col">Acción</th></tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r):
            $id = (int)($r['id'] ?? 0);
            $typeLabel = ['select'=>'Selección','text'=>'Texto','number'=>'Número'][(string)($r['type'] ?? '')] ?? (string)($r['type'] ?? '');
          ?>
            <tr>
              <td><strong><?= e((string)($r['name'] ?? '')) ?></strong>
                <?php if (!empty($r['code'])): ?><small><?= e((string)$r['code']) ?></small><?php endif; ?>
              </td>
              <td><?= e($typeLabel) ?></td>
              <td class="av3-attributes__muted"><?= e((string)($r['slug'] ?? '')) ?></td>
              <td class="av3-attributes__actions-col">
                <a class="av3-attributes__action-link" href="<?= e(url('/admin/attributes/edit?id='.$id)) ?>" aria-label="Editar atributo <?= e((string)($r['name'] ?? '')) ?>">
                  Editar <span aria-hidden="true">›</span>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="av3-attributes__mobile-list" aria-label="Listado de atributos para móvil">
      <?php foreach ($rows as $r):
        $id = (int)($r['id'] ?? 0);
        $typeLabel = ['select'=>'Selección','text'=>'Texto','number'=>'Número'][(string)($r['type'] ?? '')] ?? (string)($r['type'] ?? '');
      ?>
        <article class="av3-attributes__mobile-item">
          <div class="av3-attributes__mobile-top">
            <strong><?= e((string)($r['name'] ?? '')) ?></strong>
            <a class="av3-attributes__action-link" href="<?= e(url('/admin/attributes/edit?id='.$id)) ?>" aria-label="Editar atributo <?= e((string)($r['name'] ?? '')) ?>">
              Editar <span aria-hidden="true">›</span>
            </a>
          </div>
          <div class="av3-attributes__mobile-sub">
            <span><?= e($typeLabel) ?></span>
            <span aria-hidden="true">·</span><span class="av3-attributes__mobile-slug" title="<?= e((string)($r['slug'] ?? '')) ?>"><?= e((string)($r['slug'] ?? '')) ?></span>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <footer class="av3-attributes__footer">
    <?= count($rows) ?> <?= count($rows) === 1 ? 'atributo' : 'atributos' ?><?= $q !== '' ? ' encontrados' : '' ?>
  </footer>
</section>
