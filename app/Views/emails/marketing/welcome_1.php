<?php
$unsubscribe_url = (string)($unsubscribe_url ?? '');
?>
<h2 style="margin:0 0 12px;font-size:18px;">Elegí mejor (y gastá menos) ✅</h2>
<p style="margin:0 0 10px;">Te dejamos 3 tips rápidos para comprar sin vueltas:</p>
<ol style="margin:0 0 12px;padding-left:18px;">
  <li style="margin:0 0 6px;">Si un producto tiene <strong>variantes</strong>, elegí la opción que mejor te sirva (tamaño/color/etc.) y mirá el stock.</li>
  <li style="margin:0 0 6px;">Revisá el carrito: ahí podés ajustar cantidades y ver el total.</li>
  <li style="margin:0 0 6px;">Si tenés dudas, guardá el producto y volvé después: el carrito queda listo.</li>
</ol>
<p style="margin:0 0 12px;">
  <a href="<?= e(url('/productos')) ?>" style="display:inline-block;padding:10px 14px;border-radius:10px;background:#111827;color:#fff;text-decoration:none;">Explorar catálogo</a>
</p>
<hr style="border:none;border-top:1px solid #e5e7eb;margin:18px 0;" />
<p style="margin:0;color:#6b7280;font-size:12px;">Baja: <a href="<?= e($unsubscribe_url) ?>">darse de baja</a>.</p>
