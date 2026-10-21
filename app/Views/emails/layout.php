<?php
/** @var string $title */
/** @var string $content */
/** @var string $brandName */
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($title) ?></title>
</head>
<body style="margin:0;padding:0;background:#f5f6f8;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f5f6f8;padding:24px 0;">
    <tr>
      <td align="center" style="padding:0 12px;">
        <table role="presentation" width="640" cellspacing="0" cellpadding="0" style="max-width:640px;width:100%;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.08);">
          <tr>
            <td style="padding:18px 22px;background:#0b1220;color:#ffffff;">
              <div style="font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:700;letter-spacing:.2px;">
                <?= e($brandName) ?>
              </div>
              <div style="font-family:Arial,Helvetica,sans-serif;font-size:12px;opacity:.85;margin-top:4px;">
                Email transaccional — no respondas a este mensaje
              </div>
            </td>
          </tr>

          <tr>
            <td style="padding:22px 22px 10px 22px;">
              <?= $content ?>
            </td>
          </tr>

          <tr>
            <td style="padding:14px 22px 22px 22px;color:#6b7280;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.45;">
              <div style="border-top:1px solid #eef0f3;padding-top:14px;">
                Si tenés dudas, revisá <strong>Mis pedidos</strong> desde tu cuenta.
                <br>
                © <?= date('Y') ?> <?= e($brandName) ?>
              </div>
            </td>
          </tr>
        </table>

        <div style="font-family:Arial,Helvetica,sans-serif;font-size:11px;color:#9ca3af;margin-top:10px;">
          Estás recibiendo este email porque hiciste una compra en <?= e($brandName) ?>.
        </div>
      </td>
    </tr>
  </table>
</body>
</html>
