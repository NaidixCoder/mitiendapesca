<section class="av3-marketing-attribution" aria-label="Configuración de atribución">
  <div class="av3-marketing-attribution__current">
    <span>Atribución de conversiones</span>
    <strong><?= ($attributionMode ?? 'first_touch') === 'last_touch' ? 'Última interacción' : 'Primera interacción' ?></strong>
  </div>
  <details class="av3-marketing-attribution__details">
    <summary>Cambiar configuración</summary>
    <form method="post" action="<?= url('/admin/marketing/settings/save') ?>">
      <?= csrf_field() ?>
      <label for="av3-marketing-attribution-mode">Modelo de atribución</label>
      <select id="av3-marketing-attribution-mode" name="attribution_mode">
        <option value="first_touch"<?= ($attributionMode ?? 'first_touch') === 'first_touch' ? ' selected' : '' ?>>Primera interacción</option>
        <option value="last_touch"<?= ($attributionMode ?? 'first_touch') === 'last_touch' ? ' selected' : '' ?>>Última interacción</option>
      </select>
      <button class="av3-ui-btn av3-ui-btn--primary" type="submit">Guardar</button>
      <p>Define qué campaña recibe el crédito cuando se registra una conversión.</p>
    </form>
  </details>
</section>
