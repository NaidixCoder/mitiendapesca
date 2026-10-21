
<h1 class="av3-type-xl av3-weight-strong av3-space-bottom-4">Precios Mayoristas</h1>

<form method="post" action="/admin/settings/save?tab=mayoristas">
  <?php csrf_field(); ?>

  <label class="av3-flex av3-items-center av3-gap-2 av3-space-bottom-4">
    <input type="checkbox" name="wh_enabled" value="1" <?= ($cfg['wh_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
    <span>Habilitar precios mayoristas</span>
  </label>

  <div class="av3-grid av3-grid-cols-2 av3-gap-4 av3-space-bottom-4">
    <div>
      <label>Descuento Mayorista 1 (%)</label>
      <input type="text" name="wh1_discount_percent" value="<?= htmlspecialchars($cfg['wh1_discount_percent'] ?? '') ?>" class="input">
    </div>
    <div>
      <label>Descuento Mayorista 2 (%)</label>
      <input type="text" name="wh2_discount_percent" value="<?= htmlspecialchars($cfg['wh2_discount_percent'] ?? '') ?>" class="input">
    </div>
  </div>

  <label class="av3-flex av3-items-center av3-gap-2 av3-space-bottom-2">
    <input type="checkbox" name="wh_round_enabled" value="1" <?= ($cfg['wh_round_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
    <span>Habilitar redondeo</span>
  </label>

  <div class="av3-grid av3-grid-cols-2 av3-gap-4 av3-space-bottom-4">
    <div>
      <label>Redondear a</label>
      <input type="number" name="wh_round_to" value="<?= htmlspecialchars($cfg['wh_round_to'] ?? '') ?>" class="input">
    </div>
    <div>
      <label>Modo</label>
      <select name="wh_round_mode" class="input">
        <?php $mode = $cfg['wh_round_mode'] ?? 'round'; ?>
        <option value="round" <?= $mode==='round'?'selected':'' ?>>Redondear</option>
        <option value="ceil" <?= $mode==='ceil'?'selected':'' ?>>Hacia arriba</option>
        <option value="floor" <?= $mode==='floor'?'selected':'' ?>>Hacia abajo</option>
      </select>
    </div>
  </div>

  <button class="btn-primary">Guardar</button>
</form>
