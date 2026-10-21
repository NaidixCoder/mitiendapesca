<?php
$pageTitle = 'Direccion - Tienda Pesca';
$pageDesc  = 'Carga una direccion para envios.';

$mode   = $mode ?? 'create';
$errors = $errors ?? [];
$values = $values ?? [];

$isEdit = ($mode === 'edit');
$action = $isEdit ? base_url('/cuenta/direcciones/guardar') : base_url('/cuenta/direcciones/crear');
$title  = $isEdit ? 'Editar direccion' : 'Nueva direccion';

$provinces = is_array($provinces ?? null) ? $provinces : [];

function err_text(array $errs, string $k): string {
  return isset($errs[$k]) ? (string)$errs[$k] : '';
}
function field_value(array $v, string $k): string {
  $val = $v[$k] ?? '';
  return is_string($val) ? $val : '';
}
?>

<section class="acct-page ae-page">
  <div class="acct-page__inner">
      <header class="ae-hero">
        <a href="<?= url('/cuenta/direcciones') ?>" class="ae-back">
          <span aria-hidden="true"><?= ui_icon('chevron-left-solid') ?></span>
          <span>Volver a direcciones</span>
        </a>
        <h1 class="ae-title"><?= e($title) ?></h1>
        <p class="ae-intro">Actualiza tus datos para envios mas rapidos y un checkout mas agil.</p>
      </header>
    <div class="acct-page__stack ae-wrap">


      <form method="post" action="<?= e($action) ?>" class="ae-form">
        <?= csrf_field() ?>
        <?php if ($isEdit): ?>
          <input type="hidden" name="id" value="<?= (int)($values['id'] ?? 0) ?>">
        <?php endif; ?>

        <section class="ae-card">
          <header class="ae-card-head">
            <span class="ae-card-icon" aria-hidden="true"><?= ui_icon('user') ?></span>
            <div>
              <h2 class="ae-card-title">Datos de contacto</h2>
              <p class="ae-card-copy">Quien recibe y como contactarlo.</p>
            </div>
          </header>

          <div class="ae-card-body">
            <div class="ae-field ae-field--full">
              <label class="ae-label" for="full_name">Nombre y apellido</label>
              <input id="full_name" name="full_name" type="text" required class="ae-control<?= err_text($errors,'full_name') ? ' is-error' : '' ?>" value="<?= e(field_value($values,'full_name')) ?>">
              <?php if (err_text($errors,'full_name')): ?><p class="ae-error"><?= e(err_text($errors,'full_name')) ?></p><?php endif; ?>
            </div>
            <div class="ae-field ae-field--full">
              <label class="ae-label" for="phone">Telefono</label>
              <input id="phone" name="phone" type="text" required class="ae-control<?= err_text($errors,'phone') ? ' is-error' : '' ?>" value="<?= e(field_value($values,'phone')) ?>">
              <?php if (err_text($errors,'phone')): ?><p class="ae-error"><?= e(err_text($errors,'phone')) ?></p><?php endif; ?>
            </div>
          </div>
        </section>

        <section class="ae-card">
          <header class="ae-card-head">
            <span class="ae-card-icon" aria-hidden="true"><?= ui_icon('pin') ?></span>
            <div>
              <h2 class="ae-card-title">Direccion de entrega</h2>
            </div>
            <?php if ($isEdit): ?>
              <button
                class="ae-delete"
                type="submit"
                formaction="<?= e(base_url('/cuenta/direcciones/eliminar')) ?>"
                formmethod="post"
                onclick="return confirm('Eliminar esta direccion?');"
              >
                <span aria-hidden="true"><?= ui_icon('trash') ?></span>
              </button>
            <?php endif; ?>
          </header>

          <div class="ae-card-body">
            <div class="ae-field ae-field--full">
              <label class="ae-label" for="label">Etiqueta (opcional)</label>
              <input id="label" name="label" type="text" class="ae-control" value="<?= e(field_value($values,'label')) ?>">
            </div>

            <div class="ae-row ae-row--double">
              <div class="ae-field">
                <label class="ae-label" for="street">Calle</label>
                <input id="street" name="street" type="text" required class="ae-control<?= err_text($errors,'street') ? ' is-error' : '' ?>" value="<?= e(field_value($values,'street')) ?>">
                <?php if (err_text($errors,'street')): ?><p class="ae-error"><?= e(err_text($errors,'street')) ?></p><?php endif; ?>
              </div>
              <div class="ae-field">
                <label class="ae-label" for="number">Altura</label>
                <input id="number" name="number" type="text" required class="ae-control<?= err_text($errors,'number') ? ' is-error' : '' ?>" value="<?= e(field_value($values,'number')) ?>">
                <?php if (err_text($errors,'number')): ?><p class="ae-error"><?= e(err_text($errors,'number')) ?></p><?php endif; ?>
              </div>
            </div>

            <div class="ae-row">
              <div class="ae-field">
                <label class="ae-label" for="floor">Piso (opcional)</label>
                <input id="floor" name="floor" type="text" class="ae-control" value="<?= e(field_value($values,'floor')) ?>">
              </div>
              <div class="ae-field">
                <label class="ae-label" for="apartment">Depto (opcional)</label>
                <input id="apartment" name="apartment" type="text" class="ae-control" value="<?= e(field_value($values,'apartment')) ?>">
              </div>
            </div>
            <div class="ae-field ae-field--full">
              <label class="ae-label" for="city">Ciudad</label>
              <input id="city" name="city" type="text" required class="ae-control<?= err_text($errors,'city') ? ' is-error' : '' ?>" value="<?= e(field_value($values,'city')) ?>">
              <?php if (err_text($errors,'city')): ?><p class="ae-error"><?= e(err_text($errors,'city')) ?></p><?php endif; ?>
            </div>

            <div class="ae-row ae-row--double">
              <div class="ae-field">
                <label class="ae-label" for="state">Provincia</label>
                <select id="state" name="state" required class="ae-control<?= err_text($errors,'state') ? ' is-error' : '' ?>">
                  <option value="">Seleccionar provincia</option>
                  <?php $cur = field_value($values,'state'); ?>
                  <?php foreach ($provinces as $p): ?>
                    <option value="<?= e($p) ?>" <?= $cur === $p ? 'selected' : '' ?>><?= e($p) ?></option>
                  <?php endforeach; ?>
                </select>
                <?php if (err_text($errors,'state')): ?><p class="ae-error"><?= e(err_text($errors,'state')) ?></p><?php endif; ?>
              </div>
              <div class="ae-field">
                <label class="ae-label" for="zip">CP</label>
                <input id="zip" name="zip" type="text" required class="ae-control<?= err_text($errors,'zip') ? ' is-error' : '' ?>" value="<?= e(field_value($values,'zip')) ?>">
                <?php if (err_text($errors,'zip')): ?><p class="ae-error"><?= e(err_text($errors,'zip')) ?></p><?php endif; ?>
              </div>
            </div>
            <div class="ae-field ae-field--full">
              <label class="ae-label" for="notes">Notas para el envio (opcional)</label>
              <textarea id="notes" name="notes" class="ae-control ae-control--textarea" rows="3"><?= e(field_value($values,'notes')) ?></textarea>
            </div>
          </div>
        </section>

        <article class="ae-privacy">
          <div class="ae-privacy__content">
            <span class="ae-privacy__icon" aria-hidden="true"><?= ui_icon('shield-solid') ?></span>

            <div>
              <h3>Tus datos viajan seguros</h3>
              <p>Usamos esta información únicamente para gestionar la entrega de tus pedidos.</p>
            </div>
          </div>

          <img
            class="ae-privacy__illustration"
            src="<?= e(asset('img/account/undraw_security_0ubl.svg')) ?>"
            alt=""
            loading="lazy"
            aria-hidden="true"
          >
        </article>

        <div class="ae-actions">
          <a class="v2-btn v2-btn--ghost" href="<?= e(base_url('/cuenta/direcciones')) ?>">Cancelar</a>
          <button class="v2-btn v2-btn--primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Guardar direccion' ?></button>
        </div>
      </form>
    </div>
  </div>
</section>
