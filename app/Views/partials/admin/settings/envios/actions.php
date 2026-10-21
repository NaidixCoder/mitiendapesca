<div class="av3-flex av3-items-center av3-justify-end av3-gap-2">
  <button class="btn-secondary" type="button" id="correo-test-btn" data-endpoint="<?= e(url('/admin/settings/envios/correo/test')) ?>">
    Probar conexión
  </button>
  <button class="btn-primary" type="submit">Guardar ajustes</button>
</div>

<div class="av3-space-top-2 av3-type-caption av3-text-var-muted">
  <?php if ($customerId !== ''): ?>
    CustomerId cacheado: <span class="av3-font-mono"><?= e($customerId) ?></span>
  <?php else: ?>
    CustomerId: <span class="av3-text-slate-400">(aún no detectado)</span>
  <?php endif; ?>
</div>
