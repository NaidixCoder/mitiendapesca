<section class="av3-card">
  <div class="av3-pad-4 av3-md-pad-6 av3-flow-4">
    <div class="av3-flex av3-items-start av3-gap-3">
      <div class="av3-mt-0_5">??</div>
      <div>
        <h2 class="av3-type-base av3-weight-strong">Vista previa de seguridad</h2>
        <p class="av3-type-body av3-text-slate-600 av3-space-top-1">Antes de aplicar, revis� lo que el CSV har�a sobre tu cat�logo.</p>
        <?php if (!empty($previewData)): ?><p class="av3-type-body av3-text-slate-600 av3-space-top-2">Archivo: <b><?= e((string)($previewData['original_name'] ?? 'archivo.csv')) ?></b> � Filas analizadas: <b><?= (int)($previewData['total'] ?? 0) ?></b></p><?php endif; ?>
      </div>
    </div>

    <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-4 av3-gap-3">
      <div class="av3-radius-card av3-border av3-border-slate-200 av3-pad-3">
        <div class="av3-type-caption av3-text-slate-500">Productos</div>
        <div class="av3-type-body av3-space-top-1">
          Crear: <b><?= (int)($plan['create_products'] ?? 0) ?></b> � Actualizar: <b><?= (int)($plan['update_products'] ?? 0) ?></b>
        </div>
      </div>
      <div class="av3-radius-card av3-border av3-border-slate-200 av3-pad-3">
        <div class="av3-type-caption av3-text-slate-500">Variantes</div>
        <div class="av3-type-body av3-space-top-1">
          Crear: <b><?= (int)($plan['create_variants'] ?? 0) ?></b> � Actualizar: <b><?= (int)($plan['update_variants'] ?? 0) ?></b>
        </div>
      </div>
      <div class="av3-radius-card av3-border av3-border-slate-200 av3-pad-3">
        <div class="av3-type-caption av3-text-slate-500">Cat�logo</div>
        <div class="av3-type-body av3-space-top-1">
          Marcas nuevas: <b><?= (int)($plan['create_brands'] ?? 0) ?></b> � Categor�as nuevas: <b><?= (int)($plan['create_categories'] ?? 0) ?></b> � Proveedores nuevos: <b><?= (int)($plan['create_suppliers'] ?? 0) ?></b>
        </div>
      </div>

      <div class="av3-radius-card av3-border av3-border-slate-200 av3-pad-3">
        <div class="av3-type-caption av3-text-slate-500">Salud</div>
        <div class="av3-type-body av3-space-top-1">
          OK: <b><?= (int)($previewData['ok'] ?? 0) ?></b> � Saltadas: <b><?= (int)($previewData['skipped'] ?? 0) ?></b> � Warnings: <b><?= (int)($previewData['warnings'] ?? 0) ?></b> � Errores: <b><?= (int)($previewData['errors'] ?? 0) ?></b>
        </div>
      </div>
    </div>

    <?php if ($planCreateBrands > 0 || $planCreateCats > 0): ?>
      <div class="av3-alert warn av3-flex av3-items-start av3-justify-between av3-gap-3">
        <div class="av3-type-body">
          <?php if ($planCreateBrands > 0): ?>
            <div>Se crear�an <b><?= $planCreateBrands ?></b> marcas nuevas.</div>
          <?php endif; ?>
          <?php if ($planCreateCats > 0): ?>
            <div>Se crear�an <b><?= $planCreateCats ?></b> categor�as nuevas.</div>
          <?php endif; ?>
          <?php if ($preflightReason !== ''): ?>
            <div class="av3-space-top-2 av3-text-slate-700"><?= e($preflightReason) ?></div>
          <?php endif; ?>
        </div>
      </div>
    <?php elseif ($preflightReason !== ''): ?>
      <div class="av3-alert error">
        <?= e($preflightReason) ?>
      </div>
    <?php endif; ?>

    <?php if ($brandNames): ?>
      <div class="av3-radius-card av3-border av3-border-slate-200 av3-pad-3">
        <input id="preflight_brands_more" type="checkbox" class="peer sr-only" />
        <div class="av3-flex av3-items-center av3-justify-between av3-gap-3">
          <div class="av3-type-body av3-weight-medium">Marcas a crear (muestra)</div>
          <?php if ($moreBrands > 0): ?>
            <label for="preflight_brands_more" class="av3-type-caption av3-text-slate-600 av3-cursor-pointer av3-select-none hover:underline">
              <span class="peer-checked:av3-hidden">Ver <?= (int)$moreBrands ?> m�s</span>
              <span class="av3-hidden peer-checked:inline">Ocultar</span>
            </label>
          <?php endif; ?>
        </div>

        <div class="av3-type-body av3-text-slate-700 av3-space-top-2 av3-flex av3-flex-wrap av3-gap-2">
          <?php foreach ($brandNames as $bn): ?>
            <span class="av3-pad-x2 av3-pad-yhalf av3-radius-full av3-bg-slate-100 av3-border av3-border-slate-200 av3-type-caption"><?= e($bn) ?></span>
          <?php endforeach; ?>
        </div>

        <?php if (count($allBrands) > 20): ?>
          <div class="av3-type-body av3-text-slate-700 av3-space-top-2 av3-hidden peer-checked:flex av3-flex-wrap av3-gap-2">
            <?php foreach (array_slice($allBrands, 20) as $bn): ?>
              <span class="av3-pad-x2 av3-pad-yhalf av3-radius-full av3-bg-slate-100 av3-border av3-border-slate-200 av3-type-caption"><?= e($bn) ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($catNames): ?>
      <div class="av3-radius-card av3-border av3-border-slate-200 av3-pad-3">
        <input id="preflight_cats_more" type="checkbox" class="peer sr-only" />
        <div class="av3-flex av3-items-center av3-justify-between av3-gap-3">
          <div class="av3-type-body av3-weight-medium">Categor�as a crear (muestra)</div>
          <?php if ($moreCats > 0): ?>
            <label for="preflight_cats_more" class="av3-type-caption av3-text-slate-600 av3-cursor-pointer av3-select-none hover:underline">
              <span class="peer-checked:av3-hidden">Ver <?= (int)$moreCats ?> m�s</span>
              <span class="av3-hidden peer-checked:inline">Ocultar</span>
            </label>
          <?php endif; ?>
        </div>

        <div class="av3-type-body av3-text-slate-700 av3-space-top-2 av3-flex av3-flex-wrap av3-gap-2">
          <?php foreach ($catNames as $cn): ?>
            <span class="av3-pad-x2 av3-pad-yhalf av3-radius-full av3-bg-slate-100 av3-border av3-border-slate-200 av3-type-caption"><?= e($cn) ?></span>
          <?php endforeach; ?>
        </div>

        <?php if (count($allCats) > 20): ?>
          <div class="av3-type-body av3-text-slate-700 av3-space-top-2 av3-hidden peer-checked:flex av3-flex-wrap av3-gap-2">
            <?php foreach (array_slice($allCats, 20) as $cn): ?>
              <span class="av3-pad-x2 av3-pad-yhalf av3-radius-full av3-bg-slate-100 av3-border av3-border-slate-200 av3-type-caption"><?= e($cn) ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($detailRowsPrepared)): ?>
      <div class="av3-radius-card av3-border av3-border-slate-200 av3-overflow-hidden">
        <input id="preflight_detail_toggle" type="checkbox" class="peer sr-only" />
        <label for="preflight_detail_toggle" class="av3-flex av3-items-center av3-justify-between av3-gap-3 av3-pad-3 av3-cursor-pointer av3-select-none">
          <div class="av3-type-body av3-weight-medium">Detalle por fila</div>
          <div class="av3-type-caption av3-text-slate-600">
            <span class="peer-checked:av3-hidden">Ver detalle</span>
            <span class="av3-hidden peer-checked:inline">Ocultar</span>
          </div>
        </label>
        <div class="av3-hidden peer-checked:block av3-border-top av3-border-slate-200">
          <div class="av3-pad-3 av3-border-bottom av3-border-slate-200 av3-bg-slate-50">
            <div class="av3-flex av3-flex-col av3-md-flex-row av3-md-items-center av3-gap-2 av3-md-gap-3">
              <div class="av3-flex av3-flex-wrap av3-items-center av3-gap-2">
                <button type="button" class="js-imports-filter av3-pad-x3 av3-pad-y1half av3-radius-full av3-border av3-type-caption av3-surface-white" data-filter="all">Todos <span class="av3-text-slate-500">(<?= (int)$cntAll ?>)</span></button>
                <button type="button" class="js-imports-filter av3-pad-x3 av3-pad-y1half av3-radius-full av3-border av3-type-caption av3-surface-white" data-filter="error">Errores <span class="av3-text-slate-500">(<?= (int)$cntErr ?>)</span></button>
                <button type="button" class="js-imports-filter av3-pad-x3 av3-pad-y1half av3-radius-full av3-border av3-type-caption av3-surface-white" data-filter="warn">Warnings <span class="av3-text-slate-500">(<?= (int)$cntWarn ?>)</span></button>
                <button type="button" class="js-imports-filter av3-pad-x3 av3-pad-y1half av3-radius-full av3-border av3-type-caption av3-surface-white" data-filter="ok">OK <span class="av3-text-slate-500">(<?= (int)$cntOk ?>)</span></button>
                <button type="button" class="js-imports-filter av3-pad-x3 av3-pad-y1half av3-radius-full av3-border av3-type-caption av3-surface-white" data-filter="changes">Con cambios</button>
              </div>
              <div class="md:ml-auto av3-width-full av3-md-w-72">
                <input type="text" class="js-imports-search av3-width-full av3-border av3-radius-control av3-pad-x3 av3-pad-y2 av3-type-body" placeholder="Buscar por SKU..." />
                <p class="av3-type-caption av3-text-slate-500 av3-space-top-1">Tip: escrib� parte del SKU para filtrar r�pido.</p>
                <?php if (!empty($extraErr) || !empty($extraWarn)): ?>
                  <div class="av3-type-caption av3-text-slate-600">
                    <?php if (!empty($extraErr)): ?><span class="av3-text-red-700">Errores globales: <?= (int)$extraErr ?></span><?php endif; ?>
                    <?php if (!empty($extraWarn)): ?><span class="av3-text-amber-700 av3-ml-2">Warnings globales: <?= (int)$extraWarn ?></span><?php endif; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div class="max-h-[420px] av3-overflow-auto">
            <table class="av3-min-w-full av3-type-body">
              <thead class="av3-sticky top-0 av3-surface-white av3-z-10">
                <tr class="av3-text-left av3-border-bottom av3-border-slate-200">
                  <th class="av3-pad-x3 av3-pad-y2 av3-size-w-12">#</th>
                  <th class="av3-pad-x3 av3-pad-y2 av3-w-28">Tipo</th>
                  <th class="av3-pad-x3 av3-pad-y2 av3-size-w-40">SKU</th>
                  <th class="av3-pad-x3 av3-pad-y2 av3-size-w-40">SKU padre</th>
                  <th class="av3-pad-x3 av3-pad-y2 av3-size-w-40">Entidad</th>
                  <th class="av3-pad-x3 av3-pad-y2 av3-w-28">Acci�n</th>
                  <th class="av3-pad-x3 av3-pad-y2">Notas</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <?php foreach ($detailRowsPrepared as $row): ?>
                  <tr class="js-imports-row" data-level="<?= e((string)($row['level'] ?? '')) ?>" data-sku="<?= e((string)($row['sku'] ?? '')) ?>" data-accion="<?= e((string)($row['accion'] ?? '')) ?>">
                    <td class="av3-pad-x3 av3-pad-y2 av3-text-slate-600"><?= (int)($row['row'] ?? 0) ?></td>
                    <td class="av3-pad-x3 av3-pad-y2"><span class="av3-pad-x2 av3-pad-yhalf av3-radius-full av3-border av3-type-caption <?= e((string)($row['level_badge_class'] ?? 'av3-bg-slate-100 av3-border-slate-200 av3-text-slate-700')) ?>"><?= e((string)($row['tipo_fila'] ?? '')) ?></span></td>
                    <td class="av3-pad-x3 av3-pad-y2 av3-font-mono av3-type-caption"><?= e((string)($row['sku'] ?? '')) ?></td>
                    <td class="av3-pad-x3 av3-pad-y2 av3-font-mono av3-type-caption"><?= e((string)($row['sku_padre'] ?? '')) ?></td>
                    <td class="av3-pad-x3 av3-pad-y2"><?= e((string)($row['entidad'] ?? '')) ?></td>
                    <td class="av3-pad-x3 av3-pad-y2"><?= e((string)($row['accion'] ?? '')) ?></td>
                    <td class="av3-pad-x3 av3-pad-y2">
                      <?php $notes = $row['notas'] ?? []; ?>
                      <?php if (!empty($notes)): ?>
                        <ul class="list-disc av3-pad-left-5 av3-space-y-0-5">
                          <?php foreach ($notes as $n): ?>
                            <li class="av3-text-slate-700"><?= e((string)$n) ?></li>
                          <?php endforeach; ?>
                        </ul>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php if (!empty($previewData['rows_truncated'])): ?>
            <div class="av3-pad-x3 av3-pad-y2 av3-type-caption av3-text-slate-600 av3-border-top av3-border-slate-200">
              Mostrando las primeras 350 filas del detalle. (Se trunc� para no romper la UI.)
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($canApply): ?>
      <div class="av3-pt-2">
        <form method="post" action="<?= url('/admin/imports/apply') ?>" class="av3-flow-3">
          <?= csrf_field() ?>
          <input type="hidden" name="existing_file" value="<?= e((string)($confirm['existing_file'] ?? '')) ?>">
          <input type="hidden" name="opt_b64" value="<?= e((string)($confirm['opt_b64'] ?? '')) ?>">

          <?php if ($needsPhrase): ?>
            <div class="av3-type-body av3-text-slate-700">
              Para continuar, escrib� exactamente:
              <span class="av3-font-mono av3-pad-x2 av3-pad-yhalf av3-radius-sm av3-bg-slate-100 av3-border av3-border-slate-200"><?= e($preflightPhrase) ?></span>
            </div>
            <div class="av3-flex av3-flex-col av3-md-flex-row av3-gap-3 av3-md-items-center">
              <input name="confirm_phrase" class="av3-input" placeholder="Escrib� la frase de confirmaci�n">
              <button type="submit" class="btn-danger">Confirmar e importar</button>
              <a href="<?= url('/admin/imports') ?>" class="btn">Cancelar</a>
            </div>
            <div class="av3-type-caption av3-text-slate-500">Tip: esto existe para evitar accidentes tipo autocompletado de Excel (L10 ? L600).</div>
          <?php else: ?>
            <div class="av3-flex av3-flex-col av3-md-flex-row av3-gap-3 av3-md-items-center">
              <button type="submit" class="btn-primary">Aplicar importaci�n</button>
              <a href="<?= url('/admin/imports') ?>" class="btn">Cancelar</a>
            </div>
          <?php endif; ?>
        </form>
      </div>
    <?php endif; ?>
  </div>
</section>
