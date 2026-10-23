<?php
/** @var array<string,mixed> $totals_cards */
?>
<div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-3 av3-gap-3 av3-space-bottom-4">
  <section class="av3-fiscal-panel card av3-pad-4">
    <div class="av3-type-body av3-text-slate-600">IVA debito (ventas)</div>
    <div class="av3-type-2xl av3-weight-strong">$ <?= e((string)($totals_cards['iva_debito_label'] ?? '0,00')) ?></div>
  </section>
  <section class="av3-fiscal-panel card av3-pad-4">
    <div class="av3-type-body av3-text-slate-600">IVA credito (compras)</div>
    <div class="av3-type-2xl av3-weight-strong">$ <?= e((string)($totals_cards['iva_credito_label'] ?? '0,00')) ?></div>
  </section>
  <section class="av3-fiscal-panel card av3-pad-4">
    <div class="av3-type-body av3-text-slate-600">Saldo IVA</div>
    <div class="av3-type-2xl av3-weight-strong">$ <?= e((string)($totals_cards['saldo_label'] ?? '0,00')) ?></div>
    <div class="av3-type-caption av3-text-slate-500 av3-space-top-1"><?= e((string)($totals_cards['saldo_hint'] ?? '')) ?></div>
  </section>
</div>
