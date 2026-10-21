<div class="audit-feed av3-card">
  <div class="audit-feed__head">
    <div class="audit-feed__meta">
      <?php if ($total): ?>
        <span class="audit-quiet">Mostrando</span> <strong><?= (int)$fromRow ?>-<?= (int)$toRow ?></strong> <span class="audit-quiet">de</span> <?= (int)$total ?>
      <?php else: ?>
        <strong>Sin eventos</strong>
      <?php endif; ?>
    </div>
  </div>

  <?php if (empty($itemsPrepared)): ?>
    <div class="audit-empty">
      <div class="audit-empty__t">No hay resultados</div>
      <div class="audit-empty__s">Proba cambiando filtros o limpiandolos.</div>
      <?php if (($range ?? '7') === 'today'): ?>
        <div class="audit-empty__s audit-quiet" style="margin-top:6px;">Tip: no hubo eventos hoy. Proba <strong>ultimos 7 dias</strong> o <strong>Todo</strong>.</div>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="audit-list" role="list">
      <?php foreach ($itemsPrepared as $row): ?>
        <?php
          $entity = is_array($row['entity'] ?? null) ? $row['entity'] : ['label' => '-', 'href' => null];
          $chip = is_array($row['chip'] ?? null) ? $row['chip'] : ['label' => 'Entidad', 'tone' => 'neutral'];
          $rowId = (int)($row['row_id'] ?? 0);
        ?>
        <article class="audit-item" id="row-<?= (int)$rowId ?>" role="listitem">
          <div class="audit-item__left">
            <div class="audit-bullet audit-bullet--<?= htmlspecialchars((string)($row['tone'] ?? 'neutral')) ?>" aria-hidden="true"></div>
            <div class="audit-main">
              <div class="audit-main__top">
                <div class="audit-action"><?= htmlspecialchars((string)($row['action_text'] ?? '')) ?></div>
                <span class="audit-chip audit-chip--<?= htmlspecialchars($chip['tone']) ?>"><?= htmlspecialchars($chip['label']) ?></span>
              </div>
              <?php if (!empty($row['status_from']) && !empty($row['status_to'])): ?>
                <div class="audit-sub"><?= htmlspecialchars((string)($row['status_from'] ?? '')) ?> <span class="audit-arrow">-></span> <?= htmlspecialchars((string)($row['status_to'] ?? '')) ?></div>
              <?php else: ?>
                <div class="audit-sub audit-mono"><?= htmlspecialchars((string)($row['action_key'] ?? '')) ?></div>
              <?php endif; ?>
            </div>
          </div>

          <div class="audit-item__center">
            <?php if (!empty($entity['href'])): ?>
              <a class="audit-entity" href="<?= htmlspecialchars($entity['href']) ?>"><?= htmlspecialchars($entity['label']) ?></a>
            <?php else: ?>
              <div class="audit-entity"><?= htmlspecialchars($entity['label']) ?></div>
            <?php endif; ?>
            <div class="audit-actor">por <strong><?= htmlspecialchars((string)($row['actor'] ?? 'Sistema')) ?></strong></div>
          </div>

          <div class="audit-item__right">
            <div class="audit-time"><?= htmlspecialchars((string)($row['time_ago'] ?? '')) ?></div>
            <div class="audit-date"><?= htmlspecialchars((string)($row['created_at'] ?? '')) ?></div>
            <a class="audit-detailbtn" href="<?= htmlspecialchars((string)($row['detail_url'] ?? '#')) ?>">Detalles</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="audit-pager">
    <a class="audit-page" aria-disabled="<?= !empty($pager['prev_disabled']) ? 'true' : 'false' ?>" href="<?= htmlspecialchars((string)($pager['prev_url'] ?? '#')) ?>">Anterior</a>
    <div class="audit-pager__mid">Pagina <?= (int)$page ?> de <?= (int)$pages ?> <span class="audit-muted">(<?= (int)$total ?>)</span></div>
    <a class="audit-page" aria-disabled="<?= !empty($pager['next_disabled']) ? 'true' : 'false' ?>" href="<?= htmlspecialchars((string)($pager['next_url'] ?? '#')) ?>">Siguiente</a>
  </div>
</div>
