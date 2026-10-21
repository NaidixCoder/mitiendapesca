<div id="email-send-test-modal" class="av3-email-composer__modal is-hidden" aria-hidden="true">
  <div class="av3-email-composer__backdrop" data-send-test-close></div>
  <section class="av3-email-composer__dialog av3-email-composer__dialog--compact" role="dialog" aria-modal="true" aria-labelledby="email-send-test-heading">
    <header class="av3-email-composer__dialog-head">
      <div><h2 id="email-send-test-heading">Enviar correo de prueba</h2><p>Comprobá cómo se recibe el mensaje antes de programarlo.</p></div>
      <button type="button" class="av3-ui-btn" data-send-test-close>Cerrar</button>
    </header>
    <div class="av3-email-composer__dialog-body">
      <div class="av3-email-field">
        <label for="email-send-test-to">Enviar a</label>
        <input id="email-send-test-to" type="email" inputmode="email" placeholder="tu@email.com" value="<?= e($_SESSION['email'] ?? '') ?>">
        <p>Podés cambiar la dirección de destino.</p>
      </div>
      <div class="av3-email-composer__note">
        <strong>Se enviará un correo real con:</strong>
        <ul><li>El asunto y el texto previo actuales</li><li>El contenido del editor Visual o HTML</li></ul>
      </div>
      <div id="email-send-test-status" role="status" aria-live="polite">—</div>
    </div>
    <footer class="av3-email-composer__dialog-foot">
      <button type="button" class="av3-ui-btn" data-send-test-close>Cancelar</button>
      <button type="button" id="email-send-test-send" class="av3-ui-btn av3-ui-btn--primary">Enviar prueba</button>
    </footer>
  </section>
</div>
