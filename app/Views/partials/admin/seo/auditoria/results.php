<?php
$rowsPrepared = is_array($rows_prepared ?? null) ? $rows_prepared : [];
$paginationLinks = is_array($pagination_links ?? null) ? $pagination_links : [];
$pages = (int)($pages ?? 1);
?>
<section class="av3-seo-board av3-seo-results" data-seo-audit-table aria-labelledby="av3-seo-results-title">
  <header class="av3-seo-board__head av3-seo-results__head">
    <div>
      <h2 id="av3-seo-results-title">Resultados</h2>
      <p><?= (int)($total ?? 0) ?> productos coinciden con los filtros · Ordená por menor score para priorizar</p>
    </div>
    <span class="av3-seo-results__range"><?= e((string)($range_label ?? '')) ?></span>
  </header>
  <?php if (!$rowsPrepared): ?>
    <div class="av3-seo-empty">
      <h3>No encontramos productos</h3>
      <p>Probá con otro término o quitá algún filtro.</p>
      <a class="av3-ui-btn" href="<?= url('/admin/seo/auditoria') ?>">Mostrar todos</a>
    </div>
  <?php else: ?>
    <div class="av3-seo-table-wrap">
      <table class="av3-seo-table">
        <colgroup>
          <col class="av3-seo-col-product">
          <col class="av3-seo-col-score">
          <col class="av3-seo-col-issues">
          <col class="av3-seo-col-preview">
          <col class="av3-seo-col-publication">
          <col class="av3-seo-col-action">
        </colgroup>
        <thead>
          <tr><th scope="col">Producto</th><th scope="col">Score</th><th scope="col">Problemas detectados</th><th scope="col">Vista previa en buscadores</th><th scope="col">Publicación</th><th scope="col" class="av3-seo-table__action">Acción</th></tr>
        </thead>
        <tbody>
        <?php foreach ($rowsPrepared as $r): ?>
          <tr>
            <td class="av3-seo-table__product">
              <a class="av3-seo-product-title" href="<?= e((string)$r['seo_url']) ?>"><?= e((string)$r['name']) ?></a>
              <div class="av3-seo-product-meta">
                <?php if ($r['sku'] !== ''): ?><span><?= e((string)$r['sku']) ?></span><?php endif; ?>
                <?php if ($r['category_name'] !== ''): ?><span><?= e((string)$r['category_name']) ?></span><?php endif; ?>
              </div>
            </td>
            <td class="av3-seo-table__score"><span class="av3-seo-score av3-seo-score--<?= e((string)$r['score_tone']) ?>"><?= (int)$r['score'] ?><small>/100</small></span></td>
            <td class="av3-seo-table__issues">
              <div class="av3-seo-issues">
                <?php foreach ($r['issues'] as $issue): ?><span class="av3-seo-issue"><?= e((string)$issue) ?></span><?php endforeach; ?>
                <?php if (!$r['issues']): ?><span class="av3-seo-no-issues">Sin incidencias detectadas</span><?php endif; ?>
              </div>
            </td>
            <td class="av3-seo-table__preview">
              <?php require BASE_PATH . '/app/Views/partials/admin/seo/auditoria/serp_preview.php'; ?>
            </td>
            <td class="av3-seo-table__publication"><span class="av3-seo-publication <?= !empty($r['is_active']) ? 'is-active' : '' ?>"><?= !empty($r['is_active']) ? 'Activo' : 'Inactivo' ?></span></td>
            <td class="av3-seo-table__action"><a class="av3-ui-btn av3-ui-btn--primary" href="<?= e((string)$r['seo_url']) ?>">Editar SEO</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="av3-seo-mobile-list">
      <?php foreach ($rowsPrepared as $r): ?>
        <article class="av3-seo-mobile-item">
          <div class="av3-seo-mobile-item__top">
            <div class="av3-seo-mobile-item__name">
              <a class="av3-seo-product-title" href="<?= e((string)$r['seo_url']) ?>"><?= e((string)$r['name']) ?></a>
              <span class="av3-seo-product-meta"><?= e((string)$r['sku']) ?><?= $r['brand_name'] !== '' ? ' · '.e((string)$r['brand_name']) : '' ?></span>
            </div>
            <span class="av3-seo-score av3-seo-score--<?= e((string)$r['score_tone']) ?>"><?= (int)$r['score'] ?><small>/100</small></span>
          </div>
          <div class="av3-seo-issues">
            <?php foreach ($r['issues_primary'] as $issue): ?><span class="av3-seo-issue"><?= e((string)$issue) ?></span><?php endforeach; ?>
            <?php if ($r['issues_hidden_count'] > 0): ?><span class="av3-seo-issue-count">+<?= (int)$r['issues_hidden_count'] ?> más</span><?php endif; ?>
            <?php if (!$r['issues']): ?><span class="av3-seo-no-issues">Sin incidencias detectadas</span><?php endif; ?>
          </div>
          <div class="av3-seo-mobile-item__bottom">
            <span class="av3-seo-publication <?= !empty($r['is_active']) ? 'is-active' : '' ?>"><?= !empty($r['is_active']) ? 'Activo' : 'Inactivo' ?></span>
            <a class="av3-seo-mobile-action" href="<?= e((string)$r['seo_url']) ?>">Editar SEO <span aria-hidden="true">→</span></a>
          </div>
          <?php require BASE_PATH . '/app/Views/partials/admin/seo/auditoria/diagnostic.php'; ?>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <?php if ($pages > 1): ?>
    <nav class="av3-seo-pagination" aria-label="Páginas de resultados">
      <span><?= e((string)($range_label ?? '')) ?></span>
      <div>
        <?php foreach ($paginationLinks as $lnk): ?>
          <?php if (!empty($lnk['disabled'])): ?><span class="is-disabled"><?= e((string)$lnk['label']) ?></span>
          <?php elseif (!empty($lnk['active'])): ?><span class="is-active" aria-current="page"><?= e((string)$lnk['label']) ?></span>
          <?php else: ?><a href="<?= e((string)$lnk['href']) ?>"><?= e((string)$lnk['label']) ?></a><?php endif; ?>
        <?php endforeach; ?>
      </div>
    </nav>
  <?php endif; ?>
</section>
