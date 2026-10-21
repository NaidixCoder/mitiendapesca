<?php
$p = is_array($show['profile'] ?? null) ? $show['profile'] : [];
?>
<div class="av3-card">
  <div class="av3-card__head">
    <h2 class="av3-card__title">Perfil</h2>
    <div class="av3-card__meta">Datos opcionales</div>
  </div>

  <?php if (!empty($p['has_profile'])): ?>
    <div class="kv">
      <div class="k">Telefono</div><div class="v"><?= e((string)($p['phone'] ?? '-')) ?></div>
      <div class="k">Nacimiento</div><div class="v"><?= !empty($p['birth_date']) ? e((string)$p['birth_date']) : '-' ?></div>
      <div class="k">Marketing email</div>
      <div class="v"><?php if (!empty($p['marketing_email_opt_in'])): ?><span class="badge badge-ok">Acepta</span><?php else: ?><span class="badge badge-muted">No</span><?php endif; ?></div>
      <div class="k">Marketing WhatsApp</div>
      <div class="v"><?php if (!empty($p['marketing_whatsapp_opt_in'])): ?><span class="badge badge-ok">Acepta</span><?php else: ?><span class="badge badge-muted">No</span><?php endif; ?></div>
    </div>
  <?php else: ?>
    <div class="empty-mini"><p>No hay datos de perfil adicionales.</p></div>
  <?php endif; ?>
</div>
