<!-- Formularios de plantillas externos al formulario de campaña. -->
<form id="apply-template-form" method="post" action="<?= e((string)($links['apply_template'] ?? url('/admin/marketing/email/templates/apply'))) ?>" class="is-hidden" hidden>
  <?= csrf_field() ?>
</form>
<form id="save-as-template-form" method="post" action="<?= e((string)($links['save_as_template'] ?? url('/admin/marketing/email/templates/create'))) ?>" class="is-hidden" hidden>
  <?= csrf_field() ?>
</form>
</div>
<?php
// Los diálogos son hermanos del contenedor de ancho limitado: pueden cubrir
// la pantalla completa, y siguen fuera de los formularios POST.
foreach (['preview', 'send_test', 'card_builder', 'product_picker'] as $modal) {
    require BASE_PATH . '/app/Views/partials/admin/marketing_email/campaign_form/modals/' . $modal . '.php';
}
?>
</section>
