<?php $p=is_array($show['profile']??null)?$show['profile']:[]; ?>
<section class="av3-ui-card">
 <div class="av3-ui-card-header"><h2 class="av3-ui-card-title">Perfil de cliente</h2></div>
 <div class="av3-ui-card-body">
 <?php if (!empty($p['has_profile'])): ?>
 <dl class="av3-users__facts">
  <dt>Teléfono</dt><dd><?= e((string)(($p['phone']??'')?:'No informado')) ?></dd>
  <dt>Nacimiento</dt><dd><?= e((string)(($p['birth_date']??'')?:'No informado')) ?></dd>
  <dt>Email comercial</dt><dd><?= !empty($p['marketing_email_opt_in'])?'Aceptado':'No aceptado' ?></dd>
  <dt>WhatsApp comercial</dt><dd><?= !empty($p['marketing_whatsapp_opt_in'])?'Aceptado':'No aceptado' ?></dd>
 </dl>
 <?php else: ?><p class="av3-ui-empty">Sin información de perfil adicional.</p><?php endif; ?>
 </div>
</section>
