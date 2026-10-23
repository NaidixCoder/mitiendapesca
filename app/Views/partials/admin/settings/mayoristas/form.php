<form id="ajustes-mayoristas" class="av3-settings-detail__form av3-flow-4" method="post" action="<?= e(url('/admin/settings?tab=mayoristas')) ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="tab" value="mayoristas">

  <label class="av3-flex av3-items-center av3-gap-2 av3-space-bottom-4">
    <input type="checkbox" name="wh_enabled" value="1" <?= ($cfg['wh_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
    <span>Habilitar precios mayoristas</span>
  </label>

  <div class="av3-settings-detail__field-grid">
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

  <div class="av3-settings-detail__field-grid">
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

  <div class="av3-settings-detail__actions"><button class="av3-ui-btn av3-ui-btn--primary" type="submit">Guardar mayoristas</button></div>
</form>
