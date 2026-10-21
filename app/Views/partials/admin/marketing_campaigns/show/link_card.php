<section class="av3-marketing-board av3-marketing-detail__links" aria-labelledby="mkt-detail-link-title">
  <div class="av3-marketing-section-head">
    <div><h2 id="mkt-detail-link-title">Enlace de campaña</h2><p>Compartilo para medir las visitas y atribuir las conversiones.</p></div>
    <div class="av3-marketing-detail__link-actions">
      <button class="av3-ui-btn av3-ui-btn--primary" type="button" data-copy-text="<?= e((string)($campaign['promo_url'] ?? '')) ?>">Copiar enlace</button>
      <a class="av3-ui-btn" target="_blank" rel="noopener noreferrer" href="<?= e((string)($campaign['promo_url'] ?? '')) ?>">Abrir</a>
    </div>
  </div>
  <div class="av3-marketing-detail__link-grid">
    <div><span>Enlace público</span><a href="<?= e((string)($campaign['promo_url'] ?? '')) ?>" target="_blank" rel="noopener noreferrer"><?= e((string)($campaign['promo_url'] ?? '')) ?></a></div>
    <div><span>Destino</span><code><?= e((string)($campaign['destination_label'] ?? '/')) ?></code></div>
    <div><span>Campaña UTM</span><code><?= e((string)($campaign['utm_campaign_label'] ?? '')) ?></code></div>
  </div>
  <details class="av3-marketing-detail__qr">
    <summary>Código QR para compartir</summary>
    <div class="av3-marketing-detail__qr-body">
      <img src="<?= e((string)($links['qr_data_url'] ?? '')) ?>" width="160" height="160" loading="lazy" alt="Código QR del enlace de la campaña">
      <div>
        <p>El QR apunta al enlace corto <?= e((string)($campaign['promo_path'] ?? '')) ?>.</p>
        <div class="av3-marketing-detail__link-actions">
          <a class="av3-ui-btn" target="_blank" rel="noopener noreferrer" href="<?= e((string)($links['qr_open'] ?? '')) ?>">Abrir QR</a>
          <a class="av3-ui-btn" href="<?= e((string)($links['qr_download'] ?? '')) ?>">Descargar PNG</a>
        </div>
      </div>
    </div>
  </details>
</section>
