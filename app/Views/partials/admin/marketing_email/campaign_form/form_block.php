  <form method="post" action="<?= e((string)($links['save'] ?? url('/admin/marketing/email/campanas/save'))) ?>" class="av3-email-composer__form" data-email-campaign-save>
    <?= csrf_field() ?>
    <input type="hidden" name="id" form="save-as-template-form" value="<?= $id ?>">
    <section class="av3-email-panel av3-email-composer__section" aria-labelledby="email-campaign-fields-title">
      <header class="av3-email-panel__header"><div><h2 id="email-campaign-fields-title">Configuración del envío</h2><p>Identificá la campaña, elegí a quién llegará y prepará el asunto.</p></div></header>
      <div class="av3-email-composer__fields">
        <div class="av3-email-field">
          <label for="email-campaign-name">Nombre interno</label>
          <input id="email-campaign-name" name="name" value="<?= e((string)old('name', (string)($c['name'] ?? ''))) ?>" placeholder="Ej.: Novedades de temporada">
        </div>
        <div class="av3-email-field">
          <label for="campaign-subject">Asunto del correo</label>
          <input id="campaign-subject" name="subject" value="<?= e((string)old('subject', (string)($c['subject'] ?? ''))) ?>" placeholder="Ej.: Nuevas ofertas en señuelos">
        </div>
        <div class="av3-email-field">
          <label for="campaign-preheader">Texto previo <small>(opcional)</small></label>
          <input id="campaign-preheader" name="preheader" value="<?= e((string)old('preheader', (string)($c['preheader'] ?? ''))) ?>" placeholder="Breve resumen que acompaña al asunto">
        </div>
        <div class="av3-email-field">
          <label for="campaign-audience">Destinatarios</label>
          <select id="campaign-audience" name="audience">
            <?php foreach ((array)($ui['audience_options'] ?? []) as $row): ?>
              <?php $value = (string)($row['value'] ?? ''); ?>
              <option value="<?= e($value) ?>" <?= $aud === $value ? 'selected' : '' ?>><?= e((string)($row['label'] ?? '')) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="av3-email-field" data-email-audience-condition="inactive_buyers" <?= $aud !== 'inactive_buyers' ? 'hidden' : '' ?>>
          <label for="email-campaign-days">Días sin comprar</label>
          <input id="email-campaign-days" type="number" name="aud_days" value="<?= e((string)$audDays) ?>" min="7" max="3650">
        </div>
        <div class="av3-email-field" data-email-audience-condition="vip" <?= $aud !== 'vip' ? 'hidden' : '' ?>>
          <label for="email-campaign-min-total">Gasto mínimo VIP</label>
          <input id="email-campaign-min-total" type="number" name="aud_min_total" value="<?= e((string)$audMinTotal) ?>" min="0" step="1000">
        </div>
        <div class="av3-email-field">
          <label for="email-campaign-cta-label">Botón principal: texto</label>
          <input id="email-campaign-cta-label" name="cta_label" value="<?= e((string)old('cta_label', (string)($c['cta_label'] ?? ''))) ?>" placeholder="Ej.: Ver productos">
        </div>
        <div class="av3-email-field">
          <label for="campaign-cta-url">Botón principal: enlace</label>
          <input id="campaign-cta-url" name="cta_url" value="<?= e((string)old('cta_url', (string)($c['cta_url'] ?? ''))) ?>" placeholder="/productos">
        </div>
        <div class="av3-email-field">
          <label for="campaign-status">Estado</label>
          <select id="campaign-status" name="status">
            <?php foreach ((array)($ui['status_options'] ?? []) as $row): ?>
              <?php $value = (string)($row['value'] ?? ''); ?>
              <option value="<?= e($value) ?>" <?= $st === $value ? 'selected' : '' ?>><?= e((string)($row['label'] ?? '')) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="av3-email-field <?= $showScheduled ? '' : 'is-hidden' ?>" id="campaign-scheduled-wrap">
          <label for="scheduled_at">Fecha y hora de envío</label>
          <input type="datetime-local" id="scheduled_at" name="scheduled_at" value="<?= e($scheduledAtInput) ?>">
          <p id="scheduled-help">Obligatoria cuando el estado es Programada.</p>
        </div>
      </div>
    </section>
  <div class="av3-email-composer__workspace">
    <?php require BASE_PATH.'/app/Views/partials/admin/marketing_email/campaign_form/templates.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/marketing_email/campaign_form/message.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/marketing_email/campaign_form/tools.php'; ?>
  </div>
  <footer class="av3-email-composer__savebar">
    <p>Guardar conserva los cambios; no envía correos. Para enviar, programá una fecha o usá el detalle de la campaña.</p>
    <button type="submit" class="av3-ui-btn av3-ui-btn--primary">Guardar campaña</button>
  </footer>
</form>
