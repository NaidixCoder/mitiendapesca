<?php
$email = (string)($email ?? '');
$unsubscribe_url = (string)($unsubscribe_url ?? '');
?>
<h1 style="margin:0 0 12px;font-size:20px;">¡Bienvenido/a! 🎣</h1>
<p style="margin:0 0 10px;">Gracias por sumarte a <strong>Mi Tienda Pesca</strong>. A partir de ahora vas a recibir novedades y ofertas reales (sin spam).</p>
<p style="margin:0 0 10px;">Tip rápido: si estás buscando algo específico, probá el buscador del sitio y guardá el producto en tu carrito para comprar cuando quieras.</p>
<p style="margin:0 0 12px;">
  <a href="<?= e(url('/productos')) ?>" style="display:inline-block;padding:10px 14px;border-radius:10px;background:#111827;color:#fff;text-decoration:none;">Ver productos</a>
</p>
<hr style="border:none;border-top:1px solid #e5e7eb;margin:18px 0;" />
<p style="margin:0;color:#6b7280;font-size:12px;">Si no querés recibir más emails, podés darte de baja en un clic: <a href="<?= e($unsubscribe_url) ?>">darse de baja</a>.</p>
