<?php render_partial('admin/subnav'); ?>
<div class="av3-max-w-6xl av3-mx-auto">
    <?php
ob_start(); ?>Comprobantes fiscales<?php $adminHeaderTitle=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>Historial de comprobantes emitidos (y errores) por pedido.<?php $adminHeaderDescription=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
$adminHeaderActions='';
$adminHeaderBreadcrumbs=(isset($crumbs)&&is_array($crumbs)&&$crumbs)?$crumbs:[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Facturación','href'=>url('/admin/facturacion/control')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
render_partial('admin/facturacion_nav');
?>

    <div class="admin-surface av3-pad-4 av3-space-bottom-4">
        <form method="get" action="<?= e((string)($apply_url ?? '')) ?>" class="av3-grid av3-grid-cols-1 av3-md-grid-cols-4 av3-gap-3">
            <div>
                <label class="form-label">Estado</label>
                <select name="status" class="form-select">
                    <?php foreach (($status_options ?? []) as $option): ?>
                        <option value="<?= e((string)($option['value'] ?? '')) ?>" <?= !empty($option['selected']) ? 'selected' : '' ?>><?= e((string)($option['label'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label">Tipo</label>
                <select name="kind" class="form-select">
                    <?php foreach (($kind_options ?? []) as $option): ?>
                        <option value="<?= e((string)($option['value'] ?? '')) ?>" <?= !empty($option['selected']) ? 'selected' : '' ?>><?= e((string)($option['label'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label">Buscar</label>
                <input type="text" name="q" class="form-input" value="<?= e((string)($filters['q'] ?? '')) ?>" placeholder="Pedido # o numero" />
                <div class="av3-type-caption av3-text-slate-500 av3-space-top-1">Podes buscar por <span class="av3-weight-medium">pedido</span> o por <span class="av3-weight-medium">numero</span> de comprobante.</div>
            </div>

            <div class="av3-md-col-span-2">
                <label class="form-label">Fecha (desde / hasta)</label>
                <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-3 av3-gap-2">
                    <select name="date_field" class="form-select av3-md-col-span-1">
                        <option value="issued" <?= (($filters['date_field'] ?? 'issued') === 'issued') ? 'selected' : '' ?>>Emision</option>
                        <option value="created" <?= (($filters['date_field'] ?? 'issued') === 'created') ? 'selected' : '' ?>>Creacion</option>
                    </select>
                    <input type="date" name="date_from" class="input av3-width-full av3-md-col-span-1" value="<?= e((string)($filters['date_from'] ?? '')) ?>" />
                    <input type="date" name="date_to" class="input av3-width-full av3-md-col-span-1" value="<?= e((string)($filters['date_to'] ?? '')) ?>" />
                </div>
                <div class="av3-type-caption av3-text-slate-500 av3-space-top-1">Filtra por <span class="av3-weight-medium"><?= (($filters['date_field'] ?? 'issued') === 'created') ? 'created_at' : 'issued_at' ?></span>.</div>
            </div>
            <div class="av3-flex av3-items-end av3-gap-2">
                <button class="btn btn-primary" type="submit">Filtrar</button>
                <a class="btn btn-secondary" href="<?= e((string)($reset_url ?? '')) ?>">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="admin-surface av3-pad-4">
        <div class="av3-flex av3-items-center av3-justify-between av3-gap-2 av3-space-bottom-3">
            <div class="av3-type-body av3-text-slate-600">Total: <span class="av3-weight-strong av3-text-slate-900"><?= (int)($total ?? 0) ?></span></div>
        </div>

        <div class="max-h-[520px] av3-overflow-auto av3-radius-card av3-border av3-border-slate-200">
            <table class="av3-width-full av3-type-body">
                <thead class="av3-sticky top-0 av3-surface-white av3-z-10">
                    <tr class="av3-text-left av3-border-bottom av3-border-slate-200 av3-type-caption uppercase av3-tracking-wide av3-text-slate-500">
                        <th class="av3-pad-y3 av3-pad-x3">ID</th>
                        <th class="av3-pad-y3 av3-pad-x3">Pedido</th>
                        <th class="av3-pad-y3 av3-pad-x3">Estado</th>
                        <th class="av3-pad-y3 av3-pad-x3">Pto/Cbte</th>
                        <th class="av3-pad-y3 av3-pad-x3">CAE</th>
                        <th class="av3-pad-y3 av3-pad-x3">Total</th>
                        <th class="av3-pad-y3 av3-pad-x3">Creado</th>
                        <th class="av3-pad-y3 av3-pad-x3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($has_rows)): ?>
                    <tr>
                        <td class="av3-pad-3 av3-text-slate-600" colspan="8">Sin resultados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach (($rows_prepared ?? []) as $row): ?>
                        <tr class="av3-border-bottom av3-border-slate-100 av3-hover-bg-slate-50">
                            <td class="av3-pad-3 av3-weight-medium">#<?= (int)($row['id'] ?? 0) ?></td>
                            <td class="av3-pad-y3 av3-pad-x3">
                                <?php if ((int)($row['order_id'] ?? 0) > 0): ?>
                                    <a class="underline" href="<?= e((string)($row['order_url'] ?? '')) ?>">#<?= (int)$row['order_id'] ?></a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="av3-pad-y3 av3-pad-x3">
                                <span class="av3-inline-flex av3-items-center av3-pad-x2 av3-pad-y1 av3-radius-control av3-type-caption av3-border <?= e((string)($row['status_badge_class'] ?? '')) ?>">
                                    <?= e((string)($row['status_label'] ?? '')) ?>
                                </span>
                            </td>
                            <td class="av3-pad-y3 av3-pad-x3">
                                <div class="av3-flex av3-flex-wrap av3-items-center av3-gap-2">
                                  <span class="av3-inline-flex av3-items-center av3-pad-x2 av3-pad-y1 av3-radius-control av3-type-caption av3-border <?= e((string)($row['kind_badge_class'] ?? '')) ?>"><?= e((string)($row['kind_label'] ?? '')) ?></span>
                                  <span class="av3-text-slate-800">PV <?= (int)($row['pto_vta'] ?? 0) ?> � <?= e((string)($row['cbte_label'] ?? '')) ?> � N� <?= (int)($row['cbte_nro'] ?? 0) ?></span>
                                </div>
                                <div class="av3-type-caption av3-text-slate-500 av3-space-top-1">Tipo ARCA: <?= (int)($row['cbte_tipo'] ?? 0) ?></div>
                            </td>
                            <td class="av3-pad-y3 av3-pad-x3"><?= e((string)(($row['cae'] ?? '') !== '' ? $row['cae'] : '-')) ?></td>
                            <td class="av3-pad-y3 av3-pad-x3"><?= e((string)($row['imp_total_label'] ?? '$ 0')) ?></td>
                            <td class="av3-pad-3 av3-text-slate-600"><?= e((string)($row['created_at'] ?? '')) ?></td>
                            <td class="av3-pad-y3 av3-pad-x3"><a class="btn btn-secondary btn-sm" href="<?= e((string)($row['open_url'] ?? '')) ?>">Abrir</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($show_pagination)): ?>
            <div class="av3-flex av3-items-center av3-justify-between av3-space-top-4">
                <div class="av3-type-body av3-text-slate-600">Pagina <?= (int)($page ?? 1) ?> de <?= (int)($pages ?? 1) ?></div>
                <div class="av3-flex av3-items-center av3-gap-2">
                    <?php if ((string)($prev_url ?? '') !== ''): ?>
                        <a class="btn btn-secondary btn-sm" href="<?= e((string)$prev_url) ?>">Anterior</a>
                    <?php endif; ?>
                    <?php if ((string)($next_url ?? '') !== ''): ?>
                        <a class="btn btn-secondary btn-sm" href="<?= e((string)$next_url) ?>">Siguiente</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
