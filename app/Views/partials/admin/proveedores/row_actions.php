<div class="av3-ui-actions av3-proveedores__actions">
  <a class="av3-ui-btn av3-ui-btn--small" href="<?= e(url('/admin/proveedores/edit?id=' . (int)$it['id'])) ?>">Editar</a>
  <?php if (!empty($it['is_active'])): ?>
  <form method="post" action="<?= e(url('/admin/proveedores/delete')) ?>" onsubmit="return confirm('¿Desactivar este proveedor? Sus compras se conservarán.')">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
    <button class="av3-ui-btn av3-ui-btn--danger av3-ui-btn--small" type="submit">Desactivar</button>
  </form>
  <?php endif; ?>
</div>
