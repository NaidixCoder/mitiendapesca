<div class="av3-ui-card av3-p-0">
  <div class="av3-table-wrap">
    <table class="av3-table">
      <thead>
        <tr>
          <th style="width:160px;">Fecha</th>
          <th style="width:140px;">Evento</th>
          <th style="width:140px;">Metodo</th>
          <th>Usuario</th>
          <th style="width:160px;">IP</th>
          <th style="width:180px;">Motivo</th>
          <th style="width:160px;">Huella</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$itemsPrepared): ?>
          <tr><td colspan="7" class="av3-text-center muted av3-pad-4">No hay eventos para este filtro.</td></tr>
        <?php else: ?>
          <?php foreach ($itemsPrepared as $row): ?>
            <?php
              $uid = (int)($row['user_id'] ?? 0);
              $email = (string)($row['user_email'] ?? '');
              $name = (string)($row['user_name'] ?? '');
              $created = (string)($row['created_at'] ?? '');
              $userHref = (string)($row['user_href'] ?? '');
            ?>
            <tr>
              <td class="mono"><?= h($created) ?></td>
              <td><?= h((string)($row['event_label'] ?? '')) ?></td>
              <td><?= h((string)($row['method_label'] ?? '')) ?></td>
              <td>
                <?php if ($uid > 0): ?>
                  <a href="<?= h($userHref) ?>" class="link">
                    <?= h($name) ?>
                  </a>
                  <?php if ($email !== ''): ?>
                    <div class="muted small"><?= h($email) ?></div>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="muted">Desconocido</span>
                <?php endif; ?>
              </td>
              <td class="mono"><?= h((string)($row['ip'] ?? '-')) ?></td>
              <td><?= h((string)($row['reason_label'] ?? '-')) ?></td>
              <td class="mono"><?= h((string)($row['finger'] ?? '-')) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="av3-table-foot">
    <div class="muted">
      Mostrando <?= (int)($pager['from_row'] ?? 0) ?>–<?= (int)($pager['to_row'] ?? 0) ?> de <?= (int)$total ?>
    </div>

    <?php if ($pages > 1): ?>
      <div class="av3-pagination">
        <a class="btn btn-ghost" href="<?= h((string)($links['first'] ?? '#')) ?>" <?= !empty($pager['is_first']) ? 'aria-disabled="true"' : '' ?>>«</a>
        <a class="btn btn-ghost" href="<?= h((string)($links['prev'] ?? '#')) ?>" <?= !empty($pager['is_first']) ? 'aria-disabled="true"' : '' ?>>â€¹</a>
        <span class="muted">Pagina <?= (int)$page ?> / <?= (int)$pages ?></span>
        <a class="btn btn-ghost" href="<?= h((string)($links['next'] ?? '#')) ?>" <?= !empty($pager['is_last']) ? 'aria-disabled="true"' : '' ?>>â€º</a>
        <a class="btn btn-ghost" href="<?= h((string)($links['last'] ?? '#')) ?>" <?= !empty($pager['is_last']) ? 'aria-disabled="true"' : '' ?>>»</a>
      </div>
    <?php endif; ?>
  </div>
</div>
