<?php
$filters = is_array($filters ?? null) ? $filters : [];
$issueOptions = is_array($issue_options ?? null) ? $issue_options : [];
$activeOptions = is_array($active_options ?? null) ? $active_options : [];
$sortOptions = is_array($sort_options ?? null) ? $sort_options : [];
$orderOptions = is_array($order_options ?? null) ? $order_options : [];
$perOptions = is_array($per_options ?? null) ? $per_options : [];
$quickLinks = is_array($quick_links ?? null) ? $quick_links : [];
?>
<section class="av3-seo-board av3-seo-filterboard" aria-labelledby="av3-seo-filter-title">
  <div class="av3-seo-filterbar">
    <h2 id="av3-seo-filter-title">Buscar y filtrar</h2>
    <nav class="av3-seo-quick" aria-label="Filtros rápidos">
      <?php foreach ($quickLinks as $lnk): ?>
        <a href="<?= e((string)$lnk['href']) ?>" class="<?= !empty($lnk['active']) ? 'is-active' : '' ?>" <?= !empty($lnk['active']) ? 'aria-current="page"' : '' ?>><?= e((string)$lnk['label']) ?></a>
      <?php endforeach; ?>
    </nav>
    <a class="av3-seo-link" href="<?= url('/admin/seo/auditoria') ?>">Limpiar</a>
  </div>
  <form id="seo-audit-filters" class="av3-seo-filters" method="get" action="<?= url('/admin/seo/auditoria') ?>">
    <div class="av3-seo-toolbar">
      <div class="av3-seo-field av3-seo-search">
        <label for="seo-audit-q">Buscar producto</label>
        <input id="seo-audit-q" type="search" name="q" value="<?= e((string)($filters['q'] ?? '')) ?>" placeholder="Nombre, SKU o URL" data-seo-audit-q>
      </div>
      <div class="av3-seo-field av3-seo-sort">
        <label for="seo-audit-sort">Ordenar por</label>
        <select id="seo-audit-sort" name="sort">
          <?php foreach ($sortOptions as $opt): ?><option value="<?= e((string)($opt['value'] ?? '')) ?>" <?= !empty($opt['selected']) ? 'selected' : '' ?>><?= e((string)($opt['label'] ?? '')) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="av3-seo-field av3-seo-direction">
        <label for="seo-audit-order">Dirección</label>
        <select id="seo-audit-order" name="order">
          <?php foreach ($orderOptions as $opt): ?><option value="<?= e((string)($opt['value'] ?? '')) ?>" <?= !empty($opt['selected']) ? 'selected' : '' ?>><?= e((string)($opt['label'] ?? '')) ?></option><?php endforeach; ?>
        </select>
      </div>
      <button class="av3-ui-btn av3-ui-btn--primary av3-seo-submit" type="submit">Buscar</button>
    </div>
    <details class="av3-seo-advanced" <?= !empty($advanced_open) ? 'open' : '' ?>>
      <summary>Filtros avanzados<?= !empty($advanced_count) ? ' ('.(int)$advanced_count.')' : '' ?></summary>
      <div class="av3-seo-advanced__grid">
        <div class="av3-seo-field">
          <label for="seo-audit-issue">Problema detectado</label>
          <select id="seo-audit-issue" name="issue">
            <?php foreach ($issueOptions as $opt): ?><option value="<?= e((string)($opt['value'] ?? '')) ?>" <?= !empty($opt['selected']) ? 'selected' : '' ?>><?= e((string)($opt['label'] ?? '')) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="av3-seo-field">
          <label for="seo-audit-active">Publicación</label>
          <select id="seo-audit-active" name="active">
            <?php foreach ($activeOptions as $opt): ?><option value="<?= e((string)($opt['value'] ?? '')) ?>" <?= !empty($opt['selected']) ? 'selected' : '' ?>><?= e((string)($opt['label'] ?? '')) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="av3-seo-field">
          <label for="seo-audit-min">Score mínimo</label>
          <input id="seo-audit-min" type="number" min="0" max="100" name="score_min" value="<?= e((string)($filters['score_min'] ?? '')) ?>" placeholder="0">
        </div>
        <div class="av3-seo-field">
          <label for="seo-audit-max">Score máximo</label>
          <input id="seo-audit-max" type="number" min="0" max="100" name="score_max" value="<?= e((string)($filters['score_max'] ?? '')) ?>" placeholder="100">
        </div>
        <div class="av3-seo-field">
          <label for="seo-audit-per">Por página</label>
          <select id="seo-audit-per" name="per">
            <?php foreach ($perOptions as $opt): ?><option value="<?= e((string)($opt['value'] ?? '')) ?>" <?= !empty($opt['selected']) ? 'selected' : '' ?>><?= e((string)($opt['label'] ?? '')) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="av3-seo-advanced__footer"><button class="av3-ui-btn av3-ui-btn--primary" type="submit">Aplicar</button></div>
      </div>
    </details>
  </form>
</section>
