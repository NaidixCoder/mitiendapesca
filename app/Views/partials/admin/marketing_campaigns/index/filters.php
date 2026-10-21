<?php
$tabs = is_array($campaign_tabs ?? null) ? $campaign_tabs : [];
?>
<section class="av3-marketing-board av3-marketing-filters" aria-label="Buscar y filtrar campañas">
  <nav class="av3-marketing-tabs" aria-label="Filtrar por estado">
    <?php foreach ($tabs as $tab): ?>
      <a href="<?= e((string)$tab['url']) ?>" class="av3-marketing-tabs__tab<?= !empty($tab['active']) ? ' is-active' : '' ?>"<?= !empty($tab['active']) ? ' aria-current="page"' : '' ?>><?= e((string)$tab['label']) ?></a>
    <?php endforeach; ?>
  </nav>
  <form class="av3-marketing-filters__form" method="get" action="<?= url('/admin/marketing/campanas') ?>">
    <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    <div class="av3-marketing-field av3-marketing-field--search">
      <label for="av3-marketing-q">Buscar campaña</label>
      <input id="av3-marketing-q" type="search" name="q" value="<?= e($q) ?>" placeholder="Nombre, enlace o destino" autocomplete="off">
    </div>
    <div class="av3-marketing-field av3-marketing-field--days">
      <label for="av3-marketing-days">Período</label>
      <select id="av3-marketing-days" name="days">
        <?php foreach ([7,30,90] as $n): ?><option value="<?= $n ?>"<?= $days === $n ? ' selected' : '' ?>><?= $n ?> días</option><?php endforeach; ?>
      </select>
    </div>
    <div class="av3-marketing-field av3-marketing-field--per">
      <label for="av3-marketing-per">Por página</label>
      <select id="av3-marketing-per" name="per">
        <?php foreach ([25,50] as $n): ?><option value="<?= $n ?>"<?= $per === $n ? ' selected' : '' ?>><?= $n ?></option><?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="av3-ui-btn av3-ui-btn--primary av3-marketing-filters__submit">Buscar</button>
    <a class="av3-marketing-textlink" href="<?= e(url('/admin/marketing/campanas?days='.$days.'&per='.$per)) ?>">Limpiar</a>
  </form>
</section>
