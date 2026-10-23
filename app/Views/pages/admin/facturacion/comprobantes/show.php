<?php render_partial('admin/subnav'); ?>
<main class="av3-fiscal-page av3-max-w-6xl av3-mx-auto">
  <?php
ob_start(); ?><?= e((string)($title ?? 'Detalle comprobante')) ?><?php $adminHeaderTitle=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>Comprobante fiscal asociado al pedido.<?php $adminHeaderDescription=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>
    

    <div class="av3-flex av3-flex-wrap av3-items-center av3-gap-2">
      <?php if (!empty($is_issued)): ?>
        <a class="btn btn-primary" href="<?= e((string)($pdf_url ?? '')) ?>">Descargar PDF</a>
      <?php endif; ?>
      <a class="btn btn-secondary" href="<?= e((string)($back_url ?? '')) ?>">Volver</a>
    </div>
  <?php $adminHeaderActions=trim(ob_get_clean());
$adminHeaderBreadcrumbs=(isset($crumbs)&&is_array($crumbs)&&$crumbs)?$crumbs:[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Facturación','href'=>url('/admin/facturacion/control')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
render_partial('admin/facturacion_nav');
?>

  <section class="av3-fiscal-panel admin-surface av3-pad-4 av3-space-bottom-4">
    <div class="av3-flex av3-flex-wrap av3-items-center av3-gap-2 av3-space-bottom-4">
      <span class="av3-inline-flex av3-items-center av3-px-2_5 av3-pad-y1 av3-radius-full av3-type-caption av3-border <?= e((string)($status_badge_class ??'')) ?>">Estado: <?= e((string)($status_label ?? '')) ?></span>
      <span class="av3-inline-flex av3-items-center av3-px-2_5 av3-pad-y1 av3-radius-full av3-type-caption av3-border av3-bg-slate-50 av3-text-slate-800 av3-border-slate-200">Entorno: <?= e((string)($env_label ?? '-')) ?></span>
      <?php if ((string)($cae ?? '') !== ''): ?>
        <span class="av3-inline-flex av3-items-center av3-px-2_5 av3-pad-y1 av3-radius-full av3-type-caption av3-border av3-bg-emerald-50 av3-text-emerald-800 av3-border-emerald-200">CAE: <?= e((string)$cae) ?></span>
      <?php endif; ?>
      <?php if ((string)($email_sent_at ?? '') !== ''): ?>
        <span class="av3-inline-flex av3-items-center av3-px-2_5 av3-pad-y1 av3-radius-full av3-type-caption av3-border av3-bg-emerald-50 av3-text-emerald-800 av3-border-emerald-200">Email enviado: <?= e((string)$email_sent_at) ?></span>
      <?php elseif ((string)($email_error ?? '') !== ''): ?>
        <span class="av3-inline-flex av3-items-center av3-px-2_5 av3-pad-y1 av3-radius-full av3-type-caption av3-border av3-bg-red-50 av3-text-red-800 av3-border-red-200">Email: error</span>
      <?php endif; ?>
    </div>

    <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-3 av3-gap-3">
      <div class="av3-radius-xl av3-border av3-surface-white av3-pad-3">
        <div class="av3-type-caption av3-text-slate-500">Pedido</div>
        <div class="av3-weight-medium">
          <?php if ((int)($order_id ?? 0) > 0): ?>
            <a class="link" href="<?= e((string)($order_url ?? '')) ?>">#<?= (int)$order_id ?></a>
          <?php else: ?>
            -
          <?php endif; ?>
        </div>
        <div class="av3-type-caption av3-text-slate-500 av3-space-top-1">Emitido: <?= e((string)($created_at ?? '-')) ?></div>
      </div>

      <div class="av3-radius-xl av3-border av3-surface-white av3-pad-3">
        <div class="av3-type-caption av3-text-slate-500">Comprobante</div>
        <div class="av3-weight-medium"><?= e((string)($cbte_label ?? '')) ?></div>
        <div class="av3-type-caption av3-text-slate-500 av3-space-top-1">Punto de venta: <?= (int)($pto_vta ?? 0) ?> � Numero: <?= e((string)($numero_fmt ?? '-')) ?></div>
        <?php if ((string)($cae_vto ?? '') !== ''): ?>
          <div class="av3-type-caption av3-text-slate-500">Vencimiento CAE: <?= e((string)$cae_vto) ?></div>
        <?php endif; ?>
      </div>

      <div class="av3-radius-xl av3-border av3-surface-white av3-pad-3">
        <div class="av3-type-caption av3-text-slate-500">Receptor</div>
        <div class="av3-weight-medium">Doc Tipo <?= e((string)($doc_tipo ?? '-')) ?> � Doc Nro <?= e((string)($doc_nro ?? '-')) ?></div>
        <div class="av3-type-caption av3-text-slate-500 av3-space-top-1">Total: <span class="av3-weight-strong av3-text-slate-900"><?= e((string)($imp_total_label ?? '$ 0')) ?></span></div>
        <div class="av3-type-caption av3-text-slate-500">Neto: <?= e((string)($imp_neto_label ?? '$ 0')) ?> � IVA: <?= e((string)($imp_iva_label ?? '$ 0')) ?></div>
      </div>
    </div>

    <?php if ((string)($error_message ?? '') !== ''): ?>
      <div class="av3-space-top-4 av3-radius-xl av3-border av3-border-red-200 av3-bg-red-50 av3-pad-3">
        <div class="av3-type-body av3-weight-strong av3-text-red-800">Error</div>
        <div class="av3-type-body av3-text-red-800 whitespace-pre-wrap"><?= e((string)$error_message) ?></div>
      </div>
    <?php endif; ?>

    <div class="av3-space-top-4 av3-flex av3-flex-wrap av3-items-center av3-gap-2">
      <form method="post" action="<?= e((string)($consultar_url ?? '')) ?>">
        <input type="hidden" name="csrf" value="<?= e((string)($csrf ?? '')) ?>">
        <input type="hidden" name="id" value="<?= (int)($id ?? 0) ?>">
        <button class="btn btn-secondary" type="submit">Consultar / refrescar en ARCA</button>
      </form>

      <?php if (!empty($show_retry)): ?>
        <form method="post" action="<?= e((string)($retry_emit_url ?? '')) ?>">
          <input type="hidden" name="csrf" value="<?= e((string)($csrf ?? '')) ?>">
          <input type="hidden" name="id" value="<?= (int)($id ?? 0) ?>">
          <button class="btn btn-primary" type="submit">Reintentar emision</button>
        </form>
      <?php endif; ?>

      <?php if (!empty($show_resend)): ?>
        <form method="post" action="<?= e((string)($resend_email_url ?? '')) ?>">
          <input type="hidden" name="csrf" value="<?= e((string)($csrf ?? '')) ?>">
          <input type="hidden" name="id" value="<?= (int)($id ?? 0) ?>">
          <button class="btn btn-secondary" type="submit">Reenviar email</button>
        </form>
      <?php endif; ?>
    </div>
  </section>

  <section class="av3-fiscal-panel admin-surface av3-pad-4">
    <div class="av3-flex av3-items-center av3-justify-between av3-gap-2">
      <div>
        <h2 class="av3-type-body av3-weight-strong">Detalle tecnico (ARCA)</h2>
        <p class="av3-type-caption av3-text-slate-600">Solo para diagnostico. No lo compartas con clientes.</p>
      </div>
    </div>

    <details class="av3-space-top-3 av3-radius-xl av3-border av3-surface-white av3-pad-3">
      <summary class="av3-cursor-pointer av3-select-none av3-type-body av3-weight-medium">Ver request / response / observaciones</summary>
      <div class="av3-space-top-3 av3-grid av3-grid-cols-1 av3-lg-grid-cols-2 av3-gap-4">
        <div>
          <div class="av3-type-caption av3-weight-strong av3-space-bottom-2">Request (WSFE)</div>
          <pre class="av3-type-caption whitespace-pre-wrap break-words av3-bg-slate-50 av3-border av3-radius-xl av3-pad-3 av3-overflow-auto max-h-[60vh]"><?= e((string)(($request_pretty ?? '') ?: '-')) ?></pre>
        </div>
        <div>
          <div class="av3-type-caption av3-weight-strong av3-space-bottom-2">Response (WSFE)</div>
          <pre class="av3-type-caption whitespace-pre-wrap break-words av3-bg-slate-50 av3-border av3-radius-xl av3-pad-3 av3-overflow-auto max-h-[60vh]"><?= e((string)(($response_pretty ?? '') ?: '-')) ?></pre>
        </div>
        <div class="av3-lg-col-span-2">
          <div class="av3-type-caption av3-weight-strong av3-space-bottom-2">Observaciones</div>
          <pre class="av3-type-caption whitespace-pre-wrap break-words av3-bg-slate-50 av3-border av3-radius-xl av3-pad-3 av3-overflow-auto max-h-[40vh]"><?= e((string)(($observaciones_pretty ?? '') ?: '-')) ?></pre>
        </div>
      </div>
    </details>
  </section>
</main>