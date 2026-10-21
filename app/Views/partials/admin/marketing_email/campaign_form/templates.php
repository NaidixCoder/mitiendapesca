<details class="av3-email-composer__templates">
  <summary><strong>Plantillas</strong><span>Aplicar un diseño guardado o predefinido</span></summary>
  <div id="email-templates" class="av3-email-composer__templates-content">
    <div class="av3-email-composer__section-head">
      <div><h3>Biblioteca de diseños</h3><p>Guardá contenido propio para reutilizarlo en futuras campañas.</p></div>
      <a class="av3-ui-btn" href="<?= e((string)($links['templates_library'] ?? url('/admin/marketing/email/templates'))) ?>">Ver biblioteca</a>
    </div>
    <div class="av3-email-composer__template-grid">
      <section class="av3-email-composer__template-group">
        <h4>Plantillas guardadas</h4>
        <p>Aplicar reemplaza el contenido actual y requiere confirmación.</p>
        <?php if ($templatesLite): ?>
          <div class="av3-email-composer__control-row">
            <label class="av3-email-composer__sr-only" for="saved-template-id">Plantilla guardada</label>
            <select id="saved-template-id">
              <option value="">— Elegí una plantilla guardada —</option>
              <?php foreach ($templatesLite as $t): ?>
                <option value="<?= (int)($t['id'] ?? 0) ?>"><?= e((string)($t['name'] ?? 'Plantilla')) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if ($id > 0): ?>
              <input type="hidden" name="campaign_id" form="apply-template-form" value="<?= (int)$id ?>">
              <input type="hidden" name="template_id" form="apply-template-form" value="" data-fill="template_id">
              <button type="submit" class="av3-ui-btn" form="apply-template-form" data-confirm-apply-template>Aplicar</button>
            <?php else: ?>
              <a id="saved-template-load" class="av3-ui-btn" href="#">Cargar en campaña</a>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <p>No tenés plantillas guardadas todavía.</p>
        <?php endif; ?>
        <div class="av3-email-composer__control-row">
          <label for="template-name" class="av3-email-composer__sr-only">Nombre de la nueva plantilla</label>
          <input id="template-name" type="text" name="template_name" form="save-as-template-form" placeholder="Nombre de la plantilla" autocomplete="off" required>
          <button type="submit" id="save-as-template" form="save-as-template-form" class="av3-ui-btn av3-ui-btn--primary">Guardar como plantilla</button>
          <input type="hidden" name="subject_default" form="save-as-template-form" value="<?= e((string)($c['subject'] ?? '')) ?>">
          <input type="hidden" name="preheader_default" form="save-as-template-form" value="<?= e((string)($c['preheader'] ?? '')) ?>">
          <input type="hidden" name="editor_mode" form="save-as-template-form" value="<?= e((string)($c['editor_mode'] ?? 'visual')) ?>">
          <input type="hidden" name="body_html" form="save-as-template-form" value="<?= e((string)($c['body_html'] ?? '')) ?>">
          <input type="hidden" name="return_to" form="save-as-template-form" value="<?= e((string)($links['return_to'] ?? url($id > 0 ? '/admin/marketing/email/campanas/edit?id=' . $id : '/admin/marketing/email/campanas/new'))) ?>">
        </div>
        <p class="av3-email-composer__muted">Para guardar el diseño más reciente, guardá primero los cambios de la campaña.</p>
      </section>
      <section class="av3-email-composer__template-group">
        <h4>Diseño rápido</h4>
        <div class="av3-email-composer__control-row">
          <label for="email-template" class="av3-email-composer__sr-only">Diseño predefinido</label>
          <select id="email-template">
            <option value="">— Elegí una plantilla —</option>
            <option value="oferta">Oferta simple (título + bullets + CTA)</option>
            <option value="lanzamiento">Lanzamiento (imagen + texto + CTA)</option>
            <option value="contenido">Contenido / YouTube (imagen + texto + CTA)</option>
            <option value="reactivacion">Reactivación (texto corto + CTA)</option>
          </select>
          <button type="button" id="email-template-apply" class="av3-ui-btn">Aplicar</button>
        </div>
        <p>Aplicar reemplaza el mensaje actual y pide confirmación.</p>
      </section>
    </div>
  </div>
</details>
