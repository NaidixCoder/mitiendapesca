<?php
/** @var bool $is_edit */
/** @var int $item_id */
/** @var array<int,array<string,mixed>> $attachments_prepared */
?>
<?php if ($is_edit): ?>
  <section class="av3-fiscal-panel card av3-pad-4 av3-space-top-4">
    <h2 class="av3-type-lg av3-weight-strong av3-space-bottom-2">Adjuntos (PDF/imagen)</h2>

    <form method="post" action="<?= e(base_url('admin/facturacion/compras/adjunto/subir')) ?>" enctype="multipart/form-data" class="av3-flex av3-flex-col av3-md-flex-row av3-gap-2 av3-items-start av3-md-items-end">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$item_id ?>">
      <div class="av3-flex-1">
        <label class="label">Archivo</label>
        <input class="input" type="file" name="attachment" accept="application/pdf,image/png,image/jpeg" required>
        <p class="av3-type-caption av3-text-gray-500 av3-space-top-1">Max 8MB. Formatos: PDF, JPG, PNG.</p>
      </div>
      <div>
        <button class="btn btn-primary" type="submit">Subir adjunto</button>
      </div>
    </form>

    <?php if (!empty($attachments_prepared)): ?>
      <div class="av3-space-top-4 max-h-72 av3-overflow-auto">
        <table class="av3-fiscal-data-table av3-fiscal-data-table--cards table av3-width-full">
          <thead class="av3-sticky top-0 av3-surface-white">
            <tr>
              <th>Archivo</th>
              <th class="av3-text-right">Tamano</th>
              <th>Fecha</th>
              <th class="av3-text-right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($attachments_prepared as $attachment): ?>
              <tr>
                <td data-label="Archivo" class="av3-weight-medium"><?= e((string)$attachment['name']) ?></td>
                <td data-label="Tamano" class="av3-text-right av3-type-body av3-text-gray-600"><?= e((string)$attachment['size_kb']) ?> KB</td>
                <td data-label="Fecha" class="av3-type-body av3-text-gray-600"><?= e((string)$attachment['created_at']) ?></td>
                <td data-label="Acciones" class="av3-text-right">
                  <a class="btn btn-outline btn-sm" target="_blank" href="<?= e((string)$attachment['view_url']) ?>">Abrir</a>
                  <a class="btn btn-secondary btn-sm" href="<?= e((string)$attachment['download_url']) ?>">Descargar</a>
                  <form method="post" action="<?= e(base_url('admin/facturacion/compras/adjunto/eliminar')) ?>" class="av3-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$item_id ?>">
                    <input type="hidden" name="file" value="<?= e((string)$attachment['file']) ?>">
                    <button class="btn btn-danger btn-sm" type="submit" onclick="return confirm('Eliminar este adjunto?')">Eliminar</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="av3-type-body av3-text-gray-600 av3-space-top-3">Todavia no hay adjuntos para esta compra.</p>
    <?php endif; ?>
  </section>
<?php endif; ?>
