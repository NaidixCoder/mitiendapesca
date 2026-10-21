<?php
// Vista PDF (Dompdf / SimplePdf). Mantener HTML simple (tablas) para compatibilidad.
// Variables esperadas (ver FacturaPdfService):
// $tipoLabel, $numeroFmt, $fecha, $orderId, $cbte, $condVenta, $fiscal, $receptor, $items, $totales, $cae, $caeVto, $qrDataUri, $qrUrlShort, $qrUrl, $logoDataUri

// Helpers defensivos
if (!function_exists('pdf_h')) {
    function pdf_h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

$letter = (string)($cbte['letter'] ?? '—');
$cod = (string)($cbte['cod'] ?? '');
$pto5 = (string)($cbte['pto5'] ?? '');
$nro8 = (string)($cbte['nro8'] ?? '');

$isFactura = stripos((string)$tipoLabel, 'factura') !== false;
$docTitle = $isFactura ? 'FACTURA' : strtoupper((string)$tipoLabel);

?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <style>
        @page { margin: 22px 26px 90px 26px; }

        body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 11px; color: #111; }
        .muted { color: #666; }
        .small { font-size: 9.5px; }
        .right { text-align: right; }
        .center { text-align: center; }

        .box { border: 1px solid #d0d0d0; padding: 10px; }
        .box-tight { border: 1px solid #d0d0d0; padding: 8px; }

        .top-original { border: 1px solid #111; padding: 6px 0; font-weight: 700; }

        .brandbar { background: #0b6f6e; color: #fff; padding: 10px 14px; }
        .brandbar table { width: 100%; border-collapse: collapse; }
        .brand-name { font-size: 18px; font-weight: 800; letter-spacing: 0.5px; }
        .brand-sub { margin-top: 2px; font-size: 11px; font-weight: 500; opacity: 0.95; }
        .brand-right { text-align: right; }
        .brand-right .tipo { font-size: 18px; font-weight: 800; }
        .brand-right .nro { margin-top: 2px; font-size: 11px; }
        .brand-right .fecha { margin-top: 2px; font-size: 11px; }

        .headwrap { border: 1px solid #111; }

        .dochead { width: 100%; border-collapse: collapse; }
        .dochead td { vertical-align: top; padding: 10px; }
        .dochead .mid { width: 90px; text-align: center; }
        .letterbox { border: 1px solid #111; width: 54px; height: 54px; line-height: 54px; font-size: 28px; font-weight: 800; margin: 0 auto; }
        .codbox { font-size: 10px; margin-top: 4px; font-weight: 700; }
        .docright-title { font-size: 16px; font-weight: 800; }
        .kv { margin-top: 6px; }
        .kv b { font-weight: 700; }

        .section-title { margin: 14px 0 6px; font-size: 12px; font-weight: 800; }

        .grid { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .grid th { background: #ffc300; padding: 7px 8px; font-weight: 800; border: 1px solid #e0b100; }
        .grid td { padding: 7px 8px; border: 1px solid #e6e6e6; white-space: nowrap; overflow: hidden; word-break: keep-all; hyphens: none; }
        .grid th { white-space: nowrap; word-break: keep-all; hyphens: none; }

        /* Ajuste columnas para 1 línea por ítem (Dompdf) */
        .grid .col-code { width: 85px; }
        .grid .col-desc { width: auto; }
        .grid .col-qty { width: 48px; text-align: right; }
        .grid .col-unit { width: 88px; text-align: right; }
        .grid .col-bp { width: 60px; text-align: right; }
        .grid .col-bi { width: 78px; text-align: right; }
        .grid .col-sub { width: 92px; text-align: right; }

        .grid td.col-code { font-size: 10px; }

        .totals-wrap { width: 100%; margin-top: 16px; }
        .totals { width: 320px; margin-left: auto; border-collapse: collapse; }
        .totals td { padding: 6px 8px; }
        .totals .label { color: #333; }
        .totals .val { text-align: right; font-weight: 700; }
        .totals .sep { border-top: 1px solid #e0e0e0; padding-top: 8px; }

        .footer { position: fixed; left: 26px; right: 26px; bottom: 18px; }
        .footer table { width: 100%; border-collapse: collapse; }
        .footer .qrbox { width: 150px; }
        .footer .qrimg { width: 92px; height: 92px; border: 1px solid #ddd; }
        .footer .caebox { text-align: right; }
        .footer .caebox .line { margin-top: 2px; }
        .footer .page { text-align: center; }

        .legal { margin-top: 6px; font-size: 9px; color: #666; }
    </style>
</head>
<body>

    <div class="top-original center">ORIGINAL</div>

    <div style="height: 10px;"></div>

    <div class="brandbar">
        <table>
            <tr>
                <td>
                    <?php if (!empty($logoDataUri)): ?>
                        <img src="<?= pdf_h($logoDataUri) ?>" alt="Logo" style="height: 26px; vertical-align: middle;" />
                    <?php else: ?>
                        <div class="brand-name"><?= pdf_h((string)($fiscal['nombre'] ?? 'MI TIENDA PESCA')) ?></div>
                    <?php endif; ?>
                    <div class="brand-sub">Comprobante generado electrónicamente</div>
                </td>
                <td class="brand-right">
                    <div class="tipo"><?= pdf_h($tipoLabel) ?></div>
                    <?php if ($pto5 !== '' && $nro8 !== ''): ?>
                        <div class="nro">Punto de Venta: <b><?= pdf_h($pto5) ?></b> &nbsp;&nbsp; Comp. Nro: <b><?= pdf_h($nro8) ?></b></div>
                    <?php elseif (!empty($numeroFmt)): ?>
                        <div class="nro">N° <b><?= pdf_h($numeroFmt) ?></b></div>
                    <?php endif; ?>
                    <div class="fecha">Fecha de Emisión: <b><?= pdf_h($fecha) ?></b></div>
                </td>
            </tr>
        </table>
    </div>

    <div class="headwrap">
        <table class="dochead">
            <tr>
                <td>
                    <div><b>Razón Social:</b> <?= pdf_h((string)($fiscal['nombre'] ?? '—')) ?></div>
                    <?php if (!empty($fiscal['domicilio'])): ?>
                        <div class="kv"><b>Domicilio Comercial:</b> <?= pdf_h((string)$fiscal['domicilio']) ?></div>
                    <?php endif; ?>
                    <div class="kv"><b>Condición frente al IVA:</b> <?= pdf_h((string)($fiscal['regime_label'] ?? '—')) ?></div>
                </td>
                <td class="mid">
                    <div class="letterbox"><?= pdf_h($letter) ?></div>
                    <?php if ($cod !== ''): ?>
                        <div class="codbox">COD. <?= pdf_h($cod) ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="docright-title"><?= pdf_h($docTitle) ?></div>
                    <div class="kv"><b>CUIT:</b> <?= pdf_h((string)($fiscal['cuit'] ?? '')) ?></div>
                    <div class="kv"><b>Condición de venta:</b> <?= pdf_h((string)($condVenta ?? '—')) ?></div>
                </td>
            </tr>
            <tr>
                <td colspan="3" style="padding: 0 10px 10px;">
                    <table style="width:100%; border-collapse: collapse;">
                        <tr>
                            <td style="width:50%; padding-right: 6px;">
                                <div class="box-tight">
                                    <div style="font-weight:800; margin-bottom: 6px;">Receptor</div>
                                    <div><b>Apellido y Nombre / Razón Social:</b> <?= pdf_h((string)($receptor['nombre'] ?? '—')) ?></div>
                                    <?php if (!empty($receptor['domicilio'])): ?>
                                        <div class="kv"><b>Domicilio:</b> <?= pdf_h((string)$receptor['domicilio']) ?></div>
                                    <?php endif; ?>
                                    <div class="kv"><b>CUIT/DNI:</b> <?= pdf_h((string)($receptor['doc_nro'] ?? '')) ?></div>
                                    <div class="kv"><b>Condición frente al IVA:</b> <?= pdf_h((string)($receptor['tax_condition'] ?? '—')) ?></div>
                                </div>
                            </td>
                            <td style="width:50%; padding-left: 6px;">
                                <div class="box-tight">
                                    <div style="font-weight:800; margin-bottom: 6px;">Referencia</div>
                                    <div><b>Pedido:</b> #<?= pdf_h((string)$orderId) ?></div>
                                    <?php if (!empty($receptor['email'])): ?>
                                        <div class="kv"><b>Email:</b> <?= pdf_h((string)$receptor['email']) ?></div>
                                    <?php endif; ?>
                                    <div class="kv"><b>Entorno:</b> <?= pdf_h((string)($cbte['env'] ?? '')) ?></div>
                                    <div class="legal">Este comprobante fue generado a partir del pedido indicado.</div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <div class="section-title">Detalle</div>

    <table class="grid">
        <thead>
            <tr>
                <th class="col-code">Código</th>
                <th class="col-desc">Producto / Servicio</th>
                <th class="col-qty">Cantidad</th>
                <th class="col-unit">Precio Unit.</th>
                <th class="col-bp">% Bonif</th>
                <th class="col-bi">Imp. Bonif.</th>
                <th class="col-sub">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($items)): foreach ($items as $it):
                $desc = trim((string)($it['name'] ?? ''));
                $variant = trim((string)($it['variant_title'] ?? ''));
                $sku = trim((string)($it['sku'] ?? ''));
                if ($variant !== '') $desc .= ' — ' . $variant;
            ?>
                <tr>
                    <td class="col-code"><?= pdf_h($sku !== '' ? $sku : '—') ?></td>
                    <td class="col-desc"><?= pdf_h($desc) ?></td>
                    <td class="col-qty"><?= pdf_h((string)($it['qty'] ?? 1)) ?></td>
                    <td class="col-unit"><?= pdf_h((string)($it['price_fmt'] ?? '')) ?></td>
                    <td class="col-bp"><?= pdf_h((string)($it['bonif_pct'] ?? '0,00')) ?></td>
                    <td class="col-bi"><?= pdf_h((string)($it['bonif_imp_fmt'] ?? '')) ?></td>
                    <td class="col-sub"><?= pdf_h((string)($it['subtotal_fmt'] ?? '')) ?></td>
                </tr>
            <?php endforeach; else: ?>
                <tr>
                    <td colspan="7" class="center muted">Sin ítems</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="totals-wrap">
        <table class="totals">
            <tr>
                <td class="label">Subtotal:</td>
                <td class="val"><?= pdf_h((string)($totales['neto'] ?? '')) ?></td>
            </tr>
            <tr>
                <td class="label">IVA:</td>
                <td class="val"><?= pdf_h((string)($totales['iva_label'] ?? ($totales['iva'] ?? ''))) ?></td>
            </tr>
            <tr>
                <td class="label">Importe Otros Tributos:</td>
                <td class="val"><?= pdf_h((string)($totales['otros_tributos'] ?? '')) ?></td>
            </tr>
            <tr>
                <td class="label sep"><b>Importe Total:</b></td>
                <td class="val sep"><b><?= pdf_h((string)($totales['total'] ?? '')) ?></b></td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <table>
            <tr>
                <td class="qrbox">
                    <?php if (!empty($qrDataUri)): ?>
                        <img class="qrimg" src="<?= pdf_h($qrDataUri) ?>" alt="QR" />
                        <div class="small muted" style="margin-top: 4px;"><b>ARCA/AFIP QR</b></div>
                        <?php if (!empty($qrUrlShort)): ?>
                            <div class="small muted"><?= pdf_h($qrUrlShort) ?></div>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
                <td class="page">
                    <div style="font-weight:800;">Comprobante Autorizado</div>
                    <div class="legal">La Administración Federal no se responsabiliza por los datos ingresados en el detalle de la operación.</div>
                </td>
                <td class="caebox">
                    <?php if (!empty($cae)): ?>
                        <div class="line"><b>CAE N°:</b> <?= pdf_h($cae) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($caeVto)): ?>
                        <div class="line"><b>Fecha de Vto. de CAE:</b> <?= pdf_h($caeVto) ?></div>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->get_font('Helvetica', 'normal');
            $pdf->page_text(280, 812, "Pág. {PAGE_NUM}/{PAGE_COUNT}", $font, 9, array(0,0,0));
        }
    </script>

</body>
</html>
