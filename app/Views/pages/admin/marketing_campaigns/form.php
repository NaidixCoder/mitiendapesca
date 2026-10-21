<?php
$pageTitle = 'Campaña';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';
$campaign = is_array($campaign ?? null) ? $campaign : [];
$id = (int)($campaign['id'] ?? 0);
$isEdit = $id > 0;
$slug = (string)($campaign['slug'] ?? '');
$promoUrl = $slug !== '' ? url('/promo/'.$slug) : '';
$adminHeaderTitle = $isEdit ? 'Editar campaña' : 'Nueva campaña';
$adminHeaderBreadcrumbs = [
  ['label'=>'Inicio','href'=>url('/admin')],
  ['label'=>'Marketing','href'=>url('/admin/marketing/campanas')],
  ['label'=>$adminHeaderTitle],
];
$adminHeaderActions = '<a class="av3-ui-btn" href="'.e(url('/admin/marketing/campanas')).'">Volver a campañas</a>';
?>
<section class="admin-surface">
  <div class="av3-wrap av3-marketing-campaigns av3-marketing-editor">
    <?php require BASE_PATH.'/app/Views/partials/admin/page_header.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/marketing_subnav.php'; ?>
    <?php if ($m = flash('error')): ?><div class="av3-alert error" role="alert"><?= e($m) ?></div><?php endif; ?>
    <?php if ($m = flash('ok')): ?><div class="av3-alert ok" role="status"><?= e($m) ?></div><?php endif; ?>

    <form class="av3-marketing-board av3-marketing-editor__form" method="post" action="<?= url('/admin/marketing/campanas/save') ?>" data-marketing-campaign-form data-promo-base="<?= e(url('/promo/')) ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= $id ?>">
      <div class="av3-marketing-editor__layout">
        <div class="av3-marketing-editor__main">
          <section class="av3-marketing-editor__section" aria-labelledby="mkt-base-title">
            <div class="av3-marketing-editor__section-head">
              <h2 id="mkt-base-title">Información de campaña</h2>
              <p>Definí el nombre interno y el destino de tu enlace.</p>
            </div>
            <div class="av3-marketing-editor__fields av3-marketing-editor__fields--two">
              <div class="av3-marketing-field">
                <label for="mkt-name">Nombre <span aria-hidden="true">*</span></label>
                <input id="mkt-name" name="name" value="<?= e((string)($campaign['name'] ?? '')) ?>" required maxlength="140" autocomplete="off" placeholder="Campaña Instagram">
                <p class="av3-marketing-hint">Solo para identificarla en la administración.</p>
              </div>
              <div class="av3-marketing-field">
                <label for="mkt-slug">Enlace corto</label>
                <div class="av3-marketing-input-prefix"><span>/promo/</span><input id="mkt-slug" name="slug" value="<?= e($slug) ?>" maxlength="80" placeholder="instagram-primavera" data-marketing-slug></div>
                <p class="av3-marketing-hint">Usá letras, números y guiones. Si lo dejás vacío, se genera desde el nombre.</p>
              </div>
            </div>
            <div class="av3-marketing-field">
              <label for="mkt-dest">Destino <span aria-hidden="true">*</span></label>
              <input id="mkt-dest" name="destination_path" required maxlength="255" value="<?= e((string)($campaign['destination_path'] ?? '/productos')) ?>" placeholder="/productos o https://..." spellcheck="false">
              <p class="av3-marketing-hint">Ruta interna o URL completa HTTPS. Los parámetros UTM se agregan al redirigir.</p>
            </div>
          </section>

          <section class="av3-marketing-editor__section" aria-labelledby="mkt-utm-title">
            <div class="av3-marketing-editor__section-head">
              <h2 id="mkt-utm-title">Seguimiento UTM</h2>
              <p>Identificá de dónde llega el tráfico y a qué acción pertenece.</p>
            </div>
            <div class="av3-marketing-editor__fields av3-marketing-editor__fields--three">
              <div class="av3-marketing-field">
                <label for="mkt-source">Fuente</label>
                <input id="mkt-source" name="utm_source" value="<?= e((string)($campaign['utm_source'] ?? '')) ?>" placeholder="instagram" spellcheck="false">
                <p class="av3-marketing-hint">utm_source</p>
              </div>
              <div class="av3-marketing-field">
                <label for="mkt-medium">Medio</label>
                <input id="mkt-medium" name="utm_medium" value="<?= e((string)($campaign['utm_medium'] ?? '')) ?>" placeholder="social" spellcheck="false">
                <p class="av3-marketing-hint">utm_medium</p>
              </div>
              <div class="av3-marketing-field">
                <label for="mkt-campaign">Identificador</label>
                <input id="mkt-campaign" name="utm_campaign" value="<?= e((string)($campaign['utm_campaign'] ?? '')) ?>" placeholder="primavera" spellcheck="false">
                <p class="av3-marketing-hint">utm_campaign</p>
              </div>
            </div>
            <details class="av3-marketing-editor__advanced" <?= !empty($campaign['utm_content']) || !empty($campaign['utm_term']) || !empty($campaign['notes']) ? 'open' : '' ?>>
              <summary>Parámetros y notas opcionales</summary>
              <div class="av3-marketing-editor__fields av3-marketing-editor__fields--two">
                <div class="av3-marketing-field">
                  <label for="mkt-content">Contenido (utm_content)</label>
                  <input id="mkt-content" name="utm_content" value="<?= e((string)($campaign['utm_content'] ?? '')) ?>" placeholder="historia-1" spellcheck="false">
                </div>
                <div class="av3-marketing-field">
                  <label for="mkt-term">Término (utm_term)</label>
                  <input id="mkt-term" name="utm_term" value="<?= e((string)($campaign['utm_term'] ?? '')) ?>" placeholder="reeles" spellcheck="false">
                </div>
              </div>
              <div class="av3-marketing-field">
                <label for="mkt-notes">Notas internas</label>
                <textarea id="mkt-notes" name="notes" rows="3" placeholder="Objetivo, contexto y dónde se publica..."><?= e((string)($campaign['notes'] ?? '')) ?></textarea>
              </div>
            </details>
          </section>
        </div>
        <aside class="av3-marketing-editor__aside" aria-label="Publicación y enlace">
          <div class="av3-marketing-editor__aside-head">
            <h2>Publicación</h2>
            <p>Controlá el estado y revisá el enlace corto.</p>
          </div>
          <label class="av3-marketing-editor__check">
            <input type="checkbox" name="is_active" value="1" <?= !empty($campaign['is_active']) ? 'checked' : '' ?>>
            <span><strong>Campaña activa</strong><small>El enlace redirige y registra visitas.</small></span>
          </label>
          <div class="av3-marketing-editor__preview">
            <span>Enlace corto</span>
            <a href="<?= e($promoUrl !== '' ? $promoUrl : '#') ?>" data-marketing-preview-url <?= $promoUrl === '' ? 'aria-disabled="true" tabindex="-1"' : 'target="_blank" rel="noopener noreferrer"' ?>><?= e($promoUrl !== '' ? $promoUrl : 'Se mostrará al completar el enlace') ?></a>
            <button type="button" class="av3-marketing-textlink" data-copy-text="<?= e($promoUrl) ?>" data-marketing-preview-copy <?= $promoUrl === '' ? 'disabled' : '' ?>>Copiar enlace</button>
          </div>
        </aside>
      </div>
      <footer class="av3-marketing-editor__footer">
        <a class="av3-ui-btn" href="<?= url('/admin/marketing/campanas') ?>">Cancelar</a>
        <button class="av3-ui-btn av3-ui-btn--primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear campaña' ?></button>
      </footer>
    </form>
  </div>
</section>
