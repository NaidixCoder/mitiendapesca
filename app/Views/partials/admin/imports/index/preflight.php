<?php
$previewTotal = (int)($previewData['total'] ?? 0);
$previewErrors = (int)($previewData['errors'] ?? 0);
$previewWarnings = (int)($previewData['warnings'] ?? 0);
?>
<section class="av3-ui-card av3-imports__board av3-imports__board--preview" aria-labelledby="imports-preview-title">
  <div class="av3-imports__board-head">
    <div>
      <h2 id="imports-preview-title">Vista previa de seguridad</h2>
      <p>Revisá las operaciones previstas. Hasta que confirmes, no se modifica el catálogo.</p>
    </div>
    <span class="av3-ui-badge <?= $previewErrors > 0 ? 'av3-ui-badge--danger' : ($previewWarnings > 0 ? 'av3-ui-badge--warning' : '') ?>">
      <?= $previewErrors > 0 ? 'Con errores' : ($previewWarnings > 0 ? 'Con advertencias' : 'Lista para revisar') ?>
    </span>
  </div>

  <div class="av3-imports__preview-meta">
    <div><span>Archivo</span><strong><?= e((string)($previewData['original_name'] ?? 'archivo.csv')) ?></strong></div>
    <div><span>Filas analizadas</span><strong><?= $previewTotal ?></strong></div>
  </div>

  <div class="av3-imports__stats" aria-label="Resumen de la vista previa">
    <div class="av3-imports__stat"><span>Productos</span><strong><?= (int)($plan['create_products'] ?? 0) ?> <small>nuevos</small> · <?= (int)($plan['update_products'] ?? 0) ?> <small>actualizar</small></strong></div>
    <div class="av3-imports__stat"><span>Variantes</span><strong><?= (int)($plan['create_variants'] ?? 0) ?> <small>nuevas</small> · <?= (int)($plan['update_variants'] ?? 0) ?> <small>actualizar</small></strong></div>
    <div class="av3-imports__stat"><span>Catálogo a crear</span><strong><?= (int)($plan['create_brands'] ?? 0) ?> <small>marcas</small> · <?= (int)($plan['create_categories'] ?? 0) ?> <small>categorías</small> · <?= (int)($plan['create_suppliers'] ?? 0) ?> <small>proveedores</small></strong></div>
    <div class="av3-imports__stat"><span>Validación</span><strong><?= (int)($previewData['ok'] ?? 0) ?> <small>OK</small> · <?= $previewWarnings ?> <small>avisos</small> · <?= $previewErrors ?> <small>errores</small></strong></div>
  </div>

  <?php if ($preflightReason !== '' || $planCreateBrands > 0 || $planCreateCats > 0): ?>
    <div class="av3-imports__notice av3-imports__notice--<?= $preflightReason !== '' && $planCreateBrands === 0 && $planCreateCats === 0 ? 'error' : 'warning' ?>" role="status">
      <?php if ($planCreateBrands > 0 || $planCreateCats > 0): ?>
        <strong>Creaciones previstas:</strong>
        <?= $planCreateBrands ?> marcas y <?= $planCreateCats ?> categorías nuevas.
      <?php endif; ?>
      <?php if ($preflightReason !== ''): ?><p><?= e($preflightReason) ?></p><?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if ($brandNames || $catNames): ?>
    <div class="av3-imports__catalog-preview">
      <?php foreach ([['Marcas', $allBrands, $brandNames, $moreBrands], ['Categorías', $allCats, $catNames, $moreCats]] as [$label, $all, $sample, $more]): ?>
        <?php if ($sample): ?>
          <details class="av3-imports__mini-details">
            <summary><?= e($label) ?> por crear <span class="av3-ui-badge av3-ui-badge--neutral"><?= count($all) ?></span><span class="av3-imports__chevron" aria-hidden="true"></span></summary>
            <div class="av3-imports__chips">
              <?php foreach ($all as $name): ?><span class="av3-imports__chip"><?= e((string)$name) ?></span><?php endforeach; ?>
            </div>
          </details>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($previewErrors > 0): ?>
    <div class="av3-imports__notice av3-imports__notice--error" role="alert">
      <strong>El archivo contiene errores.</strong> No se puede aplicar esta importación. Revisá las filas señaladas y volvé a analizar un CSV corregido.
    </div>
  <?php elseif ($previewWarnings > 0): ?>
    <div class="av3-imports__notice av3-imports__notice--warning" role="status">
      Hay advertencias para revisar. Los detalles están disponibles por fila.
    </div>
  <?php endif; ?>

  <?php if (!empty($detailRowsPrepared)): ?>
    <details class="av3-imports__disclosure av3-imports__row-detail" <?= $previewErrors > 0 ? 'open' : '' ?>>
      <summary>
        <span><strong>Detalle por fila</strong><small><?= $cntAll ?> filas mostradas<?= !empty($previewData['rows_truncated']) ? ' (primeras 350)' : '' ?> · filtrá por resultado o SKU</small></span>
        <span class="av3-imports__chevron" aria-hidden="true"></span>
      </summary>
      <div class="av3-imports__detail-content">
        <div class="av3-imports__filters">
          <div class="av3-imports__filter-tabs" role="group" aria-label="Filtrar filas por resultado">
            <button class="js-imports-filter av3-imports__filter" type="button" data-filter="all" aria-pressed="true">Todos <span><?= $cntAll ?></span></button>
            <button class="js-imports-filter av3-imports__filter" type="button" data-filter="error" aria-pressed="false">Errores <span><?= $cntErr ?></span></button>
            <button class="js-imports-filter av3-imports__filter" type="button" data-filter="warning" aria-pressed="false">Avisos <span><?= $cntWarn ?></span></button>
            <button class="js-imports-filter av3-imports__filter" type="button" data-filter="ok" aria-pressed="false">OK <span><?= $cntOk ?></span></button>
            <button class="js-imports-filter av3-imports__filter" type="button" data-filter="changes" aria-pressed="false">Con cambios</button>
          </div>
          <div class="av3-ui-field av3-imports__search">
            <label class="av3-ui-field-label" for="imports-preview-search">Buscar por SKU</label>
            <input class="av3-ui-control js-imports-search" type="search" id="imports-preview-search" placeholder="Código o SKU padre" autocomplete="off">
          </div>
        </div>
        <?php if ($extraErr || $extraWarn): ?>
          <p class="av3-imports__global-errors"><?= $extraErr ? 'Errores globales: ' . (int)$extraErr . '. ' : '' ?><?= $extraWarn ? 'Avisos globales: ' . (int)$extraWarn . '.' : '' ?> Estos mensajes no corresponden a una fila visible.</p>
        <?php endif; ?>
        <p class="av3-imports__match-count" data-imports-count aria-live="polite"></p>
        <div class="av3-imports__table-container">
          <table class="av3-ui-table av3-imports__table av3-imports__table--preview">
            <thead><tr>
              <th scope="col">Fila</th><th scope="col">Tipo</th><th scope="col">SKU</th><th scope="col">SKU padre</th><th scope="col">Entidad</th><th scope="col">Acción</th><th scope="col">Notas</th>
            </tr></thead>
            <tbody>
              <?php foreach ($detailRowsPrepared as $row): ?>
                <?php $level = (string)($row['level'] ?? 'ok'); ?>
                <tr class="js-imports-row av3-imports__row" data-level="<?= e($level) ?>" data-sku="<?= e((string)($row['sku'] ?? '')) ?>" data-parent-sku="<?= e((string)($row['sku_padre'] ?? '')) ?>" data-accion="<?= e((string)($row['accion'] ?? '')) ?>">
                  <td data-label="Fila" class="av3-imports__row-number"><?= (int)($row['row'] ?? 0) ?></td>
                  <td data-label="Tipo"><span class="av3-ui-badge av3-ui-badge--neutral"><?= e((string)($row['tipo_fila'] ?? '')) ?></span></td>
                  <td data-label="SKU" class="av3-imports__sku"><?= e((string)($row['sku'] ?? '')) ?></td>
                  <td data-label="SKU padre" class="av3-imports__sku"><?= e((string)($row['sku_padre'] ?? '')) ?: '—' ?></td>
                  <td data-label="Entidad"><?= e((string)($row['entidad'] ?? '')) ?></td>
                  <td data-label="Acción"><span class="av3-imports__outcome av3-imports__outcome--<?= e(in_array($level, ['error','warning'], true) ? $level : 'ok') ?>"><?= e((string)($row['accion'] ?? '')) ?></span></td>
                  <td data-label="Notas" class="av3-imports__notes">
                    <?php foreach ((array)($row['notas'] ?? []) as $n): ?><div><?= e((string)$n) ?></div><?php endforeach; ?>
                    <?php if (empty($row['notas'])): ?><span class="av3-imports__muted">Sin observaciones</span><?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <p class="av3-imports__no-match" data-imports-empty hidden>No hay filas que coincidan con este filtro.</p>
        </div>
        <?php if (!empty($previewData['rows_truncated'])): ?>
          <p class="av3-imports__hint av3-imports__table-foot">El detalle visual se limita a las primeras 350 filas; los totales de validación consideran el archivo completo.</p>
        <?php endif; ?>
      </div>
    </details>
  <?php endif; ?>

  <footer class="av3-imports__decision">
    <?php if ($canApply): ?>
      <form method="post" action="<?= e(url('/admin/imports/apply')) ?>" class="av3-imports__apply-form">
        <?= csrf_field() ?>
        <input type="hidden" name="existing_file" value="<?= e((string)($confirm['existing_file'] ?? '')) ?>">
        <input type="hidden" name="opt_b64" value="<?= e((string)($confirm['opt_b64'] ?? '')) ?>">
        <?php if ($needsPhrase): ?>
          <div class="av3-imports__confirm-block">
            <label class="av3-ui-field av3-imports__confirm-field" for="imports-confirm">
              <span class="av3-ui-field-label">Confirmación de seguridad</span>
              <span class="av3-imports__hint">Escribí exactamente <code><?= e($preflightPhrase) ?></code> para autorizar la aplicación.</span>
              <input id="imports-confirm" name="confirm_phrase" class="av3-ui-control" required autocomplete="off" placeholder="Frase de confirmación">
            </label>
            <p class="av3-imports__hint">Esta verificación evita aplicar archivos con autocompletados accidentales de filas.</p>
          </div>
        <?php endif; ?>
        <div class="av3-ui-actions av3-imports__decision-actions">
          <a href="<?= e(url('/admin/imports')) ?>" class="av3-ui-btn">Cancelar y volver</a>
          <button class="av3-ui-btn <?= $needsPhrase ? 'av3-ui-btn--danger' : 'av3-ui-btn--primary' ?>" type="submit"><?= $needsPhrase ? 'Confirmar y aplicar importación' : 'Aplicar importación' ?></button>
        </div>
      </form>
    <?php else: ?>
      <a class="av3-ui-btn av3-ui-btn--primary" href="<?= e(url('/admin/imports')) ?>">Volver y corregir archivo</a>
    <?php endif; ?>
  </footer>
</section>
