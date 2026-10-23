<section id="ajustes-automatizaciones" class="av3-ui-card av3-settings-detail__panel av3-flow-3">
  <div class="av3-flex av3-items-center av3-justify-between av3-gap-3">
    <h2 class="av3-type-base av3-weight-strong">Cuándo se manda cada mensaje</h2>
    <p class="av3-type-caption av3-text-var-muted">Se aplica a mensajes futuros.</p>
  </div>

  <?php if (!$groups): ?>
    <p class="av3-type-body av3-text-var-muted">Todavía no hay mensajes automáticos creados. Entrá a "Emails → Mensajes automáticos" o usá "Procesar ahora" una vez para inicializar.</p>
  <?php else: ?>
    <div class="av3-flow-4">
      <?php foreach ($groups as $code => $g): ?>
        <div class="av3-border av3-border--color-var---line av3-radius-card av3-pad-3">
          <div class="av3-weight-strong av3-space-bottom-2"><?= e($g['name']) ?></div>
          <?php if (empty($g['steps'])): ?>
            <p class="av3-type-body av3-text-var-muted">Sin pasos.</p>
          <?php else: ?>
            <div class="av3-overflow-auto" style="max-height: 360px;">
              <table class="table av3-width-full">
                <thead class="av3-sticky top-0 av3-bg-var-surface">
                  <tr><th>Paso</th><th style="width: 240px;">Enviar después de</th><th class="av3-hidden md:table-cell">Asunto</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($g['steps'] as $r): ?>
                    <?php
                      $stepId = (int)($r['step_id'] ?? 0);
                      $delay = (int)($r['delay_minutes'] ?? 0);
                      $unit = 'min';
                      $value = $delay;
                      if ($delay >= 1440 && $delay % 1440 === 0) { $unit = 'd'; $value = (int)($delay / 1440); }
                      elseif ($delay >= 60 && $delay % 60 === 0) { $unit = 'h'; $value = (int)($delay / 60); }
                    ?>
                    <tr>
                      <td><div class="av3-weight-medium"><?= e((string)($r['step_name'] ?? 'Paso')) ?></div><div class="av3-type-caption av3-text-var-muted">Código: <?= e((string)($r['step_code'] ?? '')) ?></div></td>
                      <td><div class="av3-flex av3-items-center av3-gap-2"><input class="input" type="number" name="delay_value[<?= (int)$stepId ?>]" value="<?= (int)$value ?>" min="0" max="9999" style="max-width: 110px;"><select class="input" name="delay_unit[<?= (int)$stepId ?>]" style="max-width: 120px;"><option value="min" <?= $unit==='min'?'selected':'' ?>>minutos</option><option value="h" <?= $unit==='h'?'selected':'' ?>>horas</option><option value="d" <?= $unit==='d'?'selected':'' ?>>días</option></select></div></td>
                      <td class="av3-hidden md:table-cell"><div class="av3-type-body"><?= e((string)($r['subject'] ?? '')) ?></div></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
