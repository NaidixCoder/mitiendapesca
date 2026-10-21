<div class="card" style="padding: 16px; margin-top: 14px;">
  <?php if (!empty($bizEvents)): ?>

    <?php if ($bizBucket === 'created'): ?>
      <div class="audit-kv audit-kv--meta">
        <div class="audit-kv__row">
          <div class="audit-kv__k">Resumen</div>
          <div class="audit-kv__v">Se creo el producto y se registraron <?= (int)$bizCount ?> evento(s) internos.</div>
        </div>
        <?php if (!empty($createdCountsHuman)): ?>
          <div class="audit-kv__row">
            <div class="audit-kv__k">Incluye</div>
            <div class="audit-kv__v"><?= htmlspecialchars(implode(' · ', $createdCountsHuman)) ?></div>
          </div>
        <?php endif; ?>
      </div>

      <?php if (!empty($createdStockRows)): ?>
        <div class="audit-kv" style="margin-top:10px;">
          <div class="audit-kv__row">
            <div class="audit-kv__k">Stock inicial</div>
            <div class="audit-kv__v audit-quiet">Estado al momento de crear el producto.</div>
          </div>
          <?php foreach ($createdStockRows as $row): ?>
            <div class="audit-kv__row">
              <div class="audit-kv__k"><?= htmlspecialchars((string)($row['variant_label'] ?? 'SKU')) ?></div>
              <div class="audit-kv__v"><span class="audit-kv__after"><?= htmlspecialchars((string)($row['stock_after'] ?? '-')) ?></span></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <details class="audit-json" style="margin-top: 12px;">
        <summary class="audit-json__btn">Ver eventos internos (<?= (int)count($bizEvents) ?>)</summary>
        <div class="audit-kv" style="margin-top:10px;">
          <?php foreach ($bizEvents as $ev): ?>
            <div class="audit-kv__row">
              <div class="audit-kv__k"><?= htmlspecialchars((string)($ev['action_label'] ?? 'Evento')) ?></div>
              <div class="audit-kv__v">
                <span class="audit-sub audit-mono"><?= htmlspecialchars((string)($ev['action_key'] ?? '')) ?></span>
                <?php if (!empty($ev['who'])): ?>
                  <span class="audit-sub" style="margin-left:8px;"><?= htmlspecialchars((string)$ev['who']) ?></span>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </details>

      <?php if (!empty($createdOtherRowsWithChanges) || !$createdOtherRowsHasChanges): ?>
        <div class="audit-kv" style="margin-top:10px;">
          <div class="audit-kv__row">
            <div class="audit-kv__k">Detalles</div>
            <div class="audit-kv__v">Cambios y acciones registradas.</div>
          </div>
          <?php foreach ($createdOtherRowsWithChanges as $ev): $evChanges = $ev['changes'] ?? []; ?>
            <div class="audit-kv" style="margin-top:10px;">
              <div class="audit-kv__row">
                <div class="audit-kv__k"><?= htmlspecialchars((string)($ev['action_label'] ?? 'Evento')) ?></div>
                <div class="audit-kv__v"><span class="audit-sub audit-mono"><?= htmlspecialchars((string)($ev['action_key'] ?? '')) ?></span></div>
              </div>
              <?php foreach ($evChanges as $chg): ?>
                <div class="audit-kv__row">
                  <div class="audit-kv__k"><?= htmlspecialchars((string)($chg['field_label'] ?? '')) ?></div>
                  <div class="audit-kv__v">
                    <span class="audit-kv__before"><?= htmlspecialchars((string)($chg['before'] ?? '-')) ?></span>
                    <span class="audit-kv__arrow">→</span>
                    <span class="audit-kv__after"><?= htmlspecialchars((string)($chg['after'] ?? '-')) ?></span>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>
          <?php if (empty($createdOtherRowsWithChanges)): ?>
            <div class="audit-kv__row">
              <div class="audit-kv__k">Info</div>
              <div class="audit-kv__v">Sin cambios detallados (ej. carga de imagenes).</div>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    <?php elseif ($bizBucket === 'stock'): ?>
      <div class="audit-kv">
        <?php foreach ($bizEvents as $ev): ?>
          <div class="audit-kv__row">
            <div class="audit-kv__k"><?= htmlspecialchars((string)($ev['variant_label'] ?? 'SKU')) ?></div>
            <div class="audit-kv__v">
              <?php if (!empty($ev['sku']) && (string)($ev['variant_label'] ?? '') !== (string)$ev['sku']): ?>
                <span class="audit-sub audit-mono" style="margin-right:8px;">SKU <?= htmlspecialchars((string)$ev['sku']) ?></span>
              <?php endif; ?>
              <span class="audit-kv__before"><?= htmlspecialchars((string)($ev['stock_before'] ?? '-')) ?></span>
              <span class="audit-kv__arrow">→</span>
              <span class="audit-kv__after"><?= htmlspecialchars((string)($ev['stock_after'] ?? '-')) ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

    <?php else: ?>
      <div class="audit-kv audit-kv--meta">
        <div class="audit-kv__row">
          <div class="audit-kv__k">Eventos</div>
          <div class="audit-kv__v">Se agruparon <?= (int)$bizCount ?> evento(s) en una sola accion.</div>
        </div>
      </div>

      <?php foreach ($bizEvents as $ev): $evChanges = is_array($ev['changes'] ?? null) ? $ev['changes'] : []; ?>
        <div class="audit-kv" style="margin-top:10px;">
          <div class="audit-kv__row">
            <div class="audit-kv__k"><?= htmlspecialchars((string)($ev['action_label'] ?? 'Evento')) ?></div>
            <div class="audit-kv__v"><span class="audit-sub audit-mono"><?= htmlspecialchars((string)($ev['action_key'] ?? '')) ?></span></div>
          </div>
          <?php if (!empty($evChanges)): ?>
            <?php foreach ($evChanges as $chg): ?>
              <div class="audit-kv__row">
                <div class="audit-kv__k"><?= htmlspecialchars((string)($chg['field_label'] ?? '')) ?></div>
                <div class="audit-kv__v">
                  <span class="audit-kv__before"><?= htmlspecialchars((string)($chg['before'] ?? '-')) ?></span>
                  <span class="audit-kv__arrow">→</span>
                  <span class="audit-kv__after"><?= htmlspecialchars((string)($chg['after'] ?? '-')) ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="audit-kv__row">
              <div class="audit-kv__k">Info</div>
              <div class="audit-kv__v">Sin cambios detallados.</div>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

  <?php else: ?>
    <?php if ((int)($variantInfo['id'] ?? 0) > 0): ?>
      <div class="audit-kv audit-kv--meta">
        <div class="audit-kv__row">
          <div class="audit-kv__k">SKU</div>
          <div class="audit-kv__v"><?= htmlspecialchars((string)($variantInfo['sku'] ?? '-')) ?></div>
        </div>
        <div class="audit-kv__row">
          <div class="audit-kv__k">Variante</div>
          <div class="audit-kv__v"><?= htmlspecialchars((string)($variantInfo['name'] ?? ('#' . (int)$variantInfo['id']))) ?></div>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($changesUi)): ?>
      <?php foreach ($changesUi as $chg): ?>
        <div class="audit-kv">
          <div class="audit-kv__row">
            <div class="audit-kv__k"><?= htmlspecialchars((string)($chg['field_label'] ?? '')) ?></div>
            <div class="audit-kv__v">
              <span class="audit-kv__before"><?= htmlspecialchars((string)($chg['before'] ?? '-')) ?></span>
              <span class="audit-kv__arrow">→</span>
              <span class="audit-kv__after"><?= htmlspecialchars((string)($chg['after'] ?? '-')) ?></span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="audit-kv audit-kv--meta">
        <div class="audit-kv__row">
          <div class="audit-kv__k">Info</div>
          <div class="audit-kv__v">Sin cambios detallados.</div>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <details class="audit-json" style="margin-top: 12px;">
    <summary class="audit-json__btn">Ver JSON</summary>
    <pre><?= htmlspecialchars($metaJsonPretty) ?></pre>
  </details>
</div>
