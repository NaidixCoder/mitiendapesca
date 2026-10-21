<?php

namespace App\Services\Billing;

use App\Repositories\Settings\AppSettingsRepository;
use App\Repositories\Billing\ComprobanteFiscalRepository;
use App\Repositories\Billing\FacturaPdfRepository;
use App\Support\Pdf\SimplePdf;
use PDO;

final class FacturaPdfService
{
    private ComprobanteFiscalRepository $cfRepo;
    private FacturaPdfRepository $repo;
    private AppSettingsRepository $settings;

    public function __construct(private PDO $db)
    {
        $this->cfRepo = new ComprobanteFiscalRepository($db);
        $this->repo = new FacturaPdfRepository($db);
        $this->settings = new AppSettingsRepository($db);
    }

    public function buildPdfForComprobanteId(int $comprobanteId): array
    {
        if ($comprobanteId <= 0) {
            return ['ok' => false, 'error' => 'Comprobante inválido.'];
        }

        $cf = $this->cfRepo->findById($comprobanteId);
        if (!$cf) {
            return ['ok' => false, 'error' => 'Comprobante no encontrado.'];
        }
        if ((string)($cf['status'] ?? '') !== 'issued') {
            return ['ok' => false, 'error' => 'El comprobante no está emitido.'];
        }

        $orderId = (int)($cf['order_id'] ?? 0);
        if ($orderId <= 0) {
            return ['ok' => false, 'error' => 'El comprobante no tiene pedido asociado.'];
        }

        $order = $this->repo->findOrderWithUser($orderId);
        $items = $this->repo->listItems($orderId);

        // Preferimos el snapshot guardado en el pedido (consistencia histórica).
        $fiscal = [
            'nombre' => (string)($order['seller_business_name'] ?? ($this->settings->get('fiscal_seller_business_name', '') ?? '')),
            'cuit' => (string)($order['seller_cuit'] ?? ($this->settings->get('fiscal_seller_cuit', '') ?? '')),
            'domicilio' => (string)($order['seller_fiscal_address'] ?? ($this->settings->get('fiscal_seller_fiscal_address', '') ?? '')),
            'regime' => (string)($order['seller_tax_regime'] ?? ($this->settings->get('fiscal_seller_tax_regime', 'monotributo') ?? 'monotributo')),
        ];

        $cbteTipo = (int)($cf['cbte_tipo'] ?? 0);
        $pto = (int)($cf['pto_vta'] ?? 0);
        $nro = (int)($cf['cbte_nro'] ?? 0);

        $tipoLabel = match ($cbteTipo) {
            1 => 'Factura A',
            6 => 'Factura B',
            11 => 'Factura C',
            8 => 'Nota de Crédito B',
            13 => 'Nota de Crédito C',
            default => 'Comprobante ' . $cbteTipo,
        };

        $numeroFmt = ($pto > 0 && $nro > 0)
            ? str_pad((string)$pto, 4, '0', STR_PAD_LEFT) . '-' . str_pad((string)$nro, 8, '0', STR_PAD_LEFT)
            : '';

        $filename = ($numeroFmt !== '' ? ($tipoLabel . '_' . $numeroFmt) : ($tipoLabel . '_' . $comprobanteId));
        $filename = preg_replace('/\s+/', '_', $filename);
        $filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '', (string)$filename);
        $filename .= '.pdf';

        $out = $this->renderPdf($tipoLabel, $numeroFmt, $cf, $order, $items, $fiscal);

        return [
            'ok' => true,
            'filename' => $filename,
            'bytes' => (string)($out['bytes'] ?? ''),
            'engine' => (string)($out['engine'] ?? 'unknown'),
        ];
    }

    private function renderPdf(string $tipoLabel, string $numeroFmt, array $cf, ?array $order, array $items, array $fiscal): array
    {
        // Si están instaladas dependencias (Composer), preferimos Dompdf + QR.
        // Si no están, hacemos fallback al PDF mínimo (SimplePdf) para no romper.
        $dompdfClass = 'Dompdf' . chr(92) . 'Dompdf';
        if (class_exists($dompdfClass)) {
            $bytes = $this->renderPdfWithDompdf($tipoLabel, $numeroFmt, $cf, $order, $items, $fiscal);
            if ($bytes !== '') return ['engine' => 'dompdf', 'bytes' => $bytes];
        }

        // Colores marca (MVP)
        // SimplePdf espera RGB 0..255
        $brand  = [1, 109, 110];   // #016d6e
        $brand2 = [255, 195, 0];   // #ffc300

        $p = new SimplePdf(); // A4
        $m = 42;

        // Header
        $p->rect(0, 0, 595, 78, $brand, null, 0, true);
        $p->text($m, 26, 18, 'MI TIENDA PESCA', [255,255,255], true);
        $p->text($m, 50, 11, 'Comprobante generado electrónicamente', [255,255,255]);

        $rightX = 595 - $m;
        $p->textRight($rightX, 26, 16, $tipoLabel, [255,255,255], true);
        if ($numeroFmt !== '') {
            $p->textRight($rightX, 50, 11, 'Nº ' . $numeroFmt, [255,255,255]);
        }

        // Info blocks
        $y = 98;
        $blockH = 92;
        $gap = 12;
        $colW = (595 - $m*2 - $gap) / 2;

        // Emisor
        $p->rect($m, $y, $colW, $blockH, [255,255,255], [230,230,230], 1, true);
        $p->text($m+12, $y+18, 10, 'Emisor', [0,0,0], true);
        $razon = (string)($fiscal['nombre'] ?? '—');
        $cuit  = (string)($fiscal['cuit'] ?? '—');
        $domi  = (string)($fiscal['domicilio'] ?? '—');
        $p->text($m+12, $y+38, 10, $razon);
        $p->text($m+12, $y+54, 9, 'CUIT: ' . $cuit);
        $p->text($m+12, $y+68, 9, 'Domicilio: ' . $domi);

        // Receptor
        $rx = $m + $colW + $gap;
        $p->rect($rx, $y, $colW, $blockH, [255,255,255], [230,230,230], 1, true);
        $p->text($rx+12, $y+18, 10, 'Receptor', [0,0,0], true);
        $name = (string)($order['user_name'] ?? $order['shipping_name'] ?? '—');
        $email = (string)($order['user_email'] ?? '');
        $docTipo = (string)($cf['doc_tipo'] ?? '');
        $docNro  = (string)($cf['doc_nro'] ?? '');
        $p->text($rx+12, $y+38, 10, $name);
        if ($email !== '') {
            $p->text($rx+12, $y+54, 9, $email);
        }
        $p->text($rx+12, $y+68, 9, 'Doc: ' . ($docTipo !== '' ? $docTipo : '—') . ' · ' . ($docNro !== '' ? $docNro : '—'));

        // Items table
        $y2 = $y + $blockH + 18;
        $p->text($m, $y2, 11, 'Detalle', [0,0,0], true);

        $tableY = $y2 + 12;
        $tableW = 595 - $m*2;
        $p->rect($m, $tableY, $tableW, 22, $brand2, [230,230,230], 1, true);
        $p->text($m+10, $tableY+15, 9, 'Cant.', [0,0,0], true);
        $p->text($m+60, $tableY+15, 9, 'Descripción', [0,0,0], true);
        $p->textRight($m+$tableW-160, $tableY+15, 9, 'P. Unit.', [0,0,0], true);
        $p->textRight($m+$tableW-10, $tableY+15, 9, 'Subtotal', [0,0,0], true);

        $rowY = $tableY + 22;
        $maxRows = 18;
        $shown = 0;

        // Si no hay ítems (o vienen vacíos/raros), mostramos un renglón mínimo.
        if (empty($items)) {
            // MVP: si no hay items fiscales, mostrar el pedido
            $desc = 'Pedido #' . (int)($cf['order_id'] ?? 0);
            $qty = 1;
            $unit = (int)($cf['imp_total'] ?? 0);
            $sub = $unit;

            $p->rect($m, $rowY, $tableW, 20, [255,255,255], [220,220,220], 1, true);
            $p->text($m+10, $rowY+14, 9, (string)$qty, [0,0,0]);
            $p->text($m+60, $rowY+14, 9, $desc, [0,0,0]);
            $p->textRight($m+$tableW-160, $rowY+14, 9, '$ ' . number_format($unit, 0, ',', '.'), [0,0,0]);
            $p->textRight($m+$tableW-10, $rowY+14, 9, '$ ' . number_format($sub, 0, ',', '.'), [0,0,0]);
            $shown = 1;
            $rowY += 20;
        } else {
            foreach ($items as $it) {
                if ($shown >= $maxRows) break;

                $qty = max(1, (int)($it['qty'] ?? 0));
                $name = trim((string)($it['name'] ?? ''));
                if ($name === '') {
                    $pid = (int)($it['product_id'] ?? 0);
                    $name = $pid > 0 ? ('Producto #' . $pid) : 'Producto';
                }
                $desc = $name;

                $variantTitle = trim((string)($it['variant_title'] ?? ''));
                if ($variantTitle !== '') {
                    $desc .= ' — ' . $variantTitle;
                }

                $sku = trim((string)($it['sku'] ?? ''));
                if ($sku !== '') {
                    $desc .= ' (' . $sku . ')';
                }

                $unit = (float)($it['price'] ?? 0);
                $sub = (float)($it['subtotal'] ?? ($qty * $unit));

                $p->rect($m, $rowY, $tableW, 20, [255,255,255], [220,220,220], 1, true);
                $p->text($m+10, $rowY+14, 9, (string)$qty, [0,0,0]);
                $p->text($m+60, $rowY+14, 9, $desc, [0,0,0]);
                $p->textRight($m+$tableW-160, $rowY+14, 9, '$ ' . number_format($unit, 0, ',', '.'), [0,0,0]);
                $p->textRight($m+$tableW-10, $rowY+14, 9, '$ ' . number_format($sub, 0, ',', '.'), [0,0,0]);

                $shown++;
                $rowY += 20;
            }

            if (count($items) > $shown) {
                $more = count($items) - $shown;
                $p->text($m+10, $rowY+14, 9, '... y ' . $more . ' ítem(s) más', [0,0,0]);
                $rowY += 20;
            }
        }

        // Si por algún motivo no se dibujó nada, forzamos un renglón mínimo.
        if ($shown <= 0) {
            $desc = 'Pedido #' . (int)($cf['order_id'] ?? 0);
            $unit = (int)($cf['imp_total'] ?? 0);
            $p->rect($m, $rowY, $tableW, 20, [255,255,255], [220,220,220], 1, true);
            $p->text($m+10, $rowY+14, 9, '1', [0,0,0]);
            $p->text($m+60, $rowY+14, 9, $desc, [0,0,0]);
            $p->textRight($m+$tableW-160, $rowY+14, 9, '$ ' . number_format($unit, 0, ',', '.'), [0,0,0]);
            $p->textRight($m+$tableW-10, $rowY+14, 9, '$ ' . number_format($unit, 0, ',', '.'), [0,0,0]);
            $rowY += 20;
        }

        // Totales
        $totY = $rowY + 18;
        $boxW = 260;
        $bx = 595 - $m - $boxW;
        $p->rect($bx, $totY, $boxW, 78, [255,255,255], [230,230,230], 1, true);
        $p->text($bx+12, $totY+18, 10, 'Totales', [0,0,0], true);

        $cbteTipo = (int)($cf['cbte_tipo'] ?? 0);
        $impNeto = (int)($cf['imp_neto'] ?? 0);
        $impIva = (int)($cf['imp_iva'] ?? 0);
        $impTotal = (int)($cf['imp_total'] ?? 0);

        $ivaText = '$ ' . number_format($impIva, 0, ',', '.');
        if (in_array($cbteTipo, [11,6], true)) {
            // Factura C (11) y Factura B (6): IVA no discriminado en esta etapa (UX simple).
            $impNeto = $impTotal;
            $ivaText = 'No discriminado';
        }

        $p->text($bx+12, $totY+38, 9, 'Neto:');
        $p->textRight($bx+$boxW-12, $totY+38, 9, '$ ' . number_format($impNeto, 0, ',', '.'));
        $p->text($bx+12, $totY+52, 9, 'IVA:');
        $p->textRight($bx+$boxW-12, $totY+52, 9, $ivaText);
        $p->text($bx+12, $totY+68, 10, 'Total:', [0,0,0], true);
        $p->textRight($bx+$boxW-12, $totY+68, 10, '$ ' . number_format($impTotal, 0, ',', '.'), [0,0,0], true);

        // CAE
        $cae = (string)($cf['cae'] ?? '');
        $caeVto = (string)($cf['cae_vto'] ?? '');
        $footY = 842 - 58;
        if ($cae !== '') {
            $p->text($m, $footY, 9, 'CAE: ' . $cae, [0,0,0], true);
        }
        if ($caeVto !== '') {
            $p->text($m, $footY + 14, 9, 'Vencimiento CAE: ' . $caeVto);
        }

        // Pequeña marca de motor para depurar fácilmente (no afecta validez fiscal)
        $p->textRight(595 - $m, $footY, 8, 'Emitido desde Mi Tienda Pesca • PDF: simplepdf', [60,60,60]);

        return ['engine' => 'simplepdf', 'bytes' => $p->output()];
    }

    /**
     * Render con Dompdf + QR (si las dependencias están instaladas).
     * Si falla por cualquier motivo, devolver string vacío para fallback.
     */
    private function renderPdfWithDompdf(string $tipoLabel, string $numeroFmt, array $cf, ?array $order, array $items, array $fiscal): string
    {
        $dompdfClass = 'Dompdf' . chr(92) . 'Dompdf';
        $optionsClass = 'Dompdf' . chr(92) . 'Options';
        if (!class_exists($dompdfClass)) return '';

        try {
            $orderId = (int)($cf['order_id'] ?? 0);

            $fechaRaw = (string)($cf['issued_at'] ?? $cf['created_at'] ?? '');
            $fecha = $fechaRaw ? date('Y-m-d', strtotime($fechaRaw)) : date('Y-m-d');

            $fiscalOut = [
                'nombre' => (string)($fiscal['nombre'] ?? 'MI TIENDA PESCA'),
                'cuit' => (string)($fiscal['cuit'] ?? ''),
                'domicilio' => (string)($fiscal['domicilio'] ?? ''),
                'regime_label' => $this->regimeLabel((string)($fiscal['regime'] ?? '')),
            ];

            $receptor = [
                'nombre' => (string)($order['user_name'] ?? $order['shipping_name'] ?? '—'),
                'email' => (string)($order['user_email'] ?? ''),
                'doc_tipo' => (string)($cf['doc_tipo'] ?? ''),
                'doc_nro' => (string)($cf['doc_nro'] ?? ''),
                'tax_condition' => $this->customerTaxConditionLabel((string)($order['customer_tax_condition'] ?? '')),
                'domicilio' => $this->formatShippingAddress($order),
            ];

            $fmt = fn($n) => '$ ' . number_format((float)$n, 0, ',', '.');

            $itemsOut = [];
            foreach (($items ?: []) as $it) {
                $qty = max(1, (int)($it['qty'] ?? 1));
                $name = trim((string)($it['name'] ?? ''));
                if ($name === '') {
                    $pid = (int)($it['product_id'] ?? 0);
                    $name = $pid > 0 ? ('Producto #' . $pid) : 'Producto';
                }
                $itemsOut[] = [
                    'qty' => $qty,
                    'name' => $name,
                    'variant_title' => (string)($it['variant_title'] ?? ''),
                    'sku' => (string)($it['sku'] ?? ''),
                    'u_medida' => 'unid.',
                    'bonif_pct' => '0,00',
                    'bonif_imp_fmt' => $fmt(0),
                    'price_fmt' => $fmt((float)($it['price'] ?? 0)),
                    'subtotal_fmt' => $fmt((float)($it['subtotal'] ?? ($qty * (float)($it['price'] ?? 0)))),
                ];
            }

            // Agregamos Envío/Descuento como renglones para que el PDF cierre con el total real.
            $shipping = (int)($order['shipping'] ?? 0);
            $discount = (int)($order['discount'] ?? 0);
            if ($shipping > 0) {
                $itemsOut[] = [
                    'qty' => 1,
                    'name' => 'Envío',
                    'variant_title' => '',
                    'sku' => '',
                    'u_medida' => 'serv.',
                    'bonif_pct' => '0,00',
                    'bonif_imp_fmt' => $fmt(0),
                    'price_fmt' => $fmt($shipping),
                    'subtotal_fmt' => $fmt($shipping),
                ];
            }
            if ($discount > 0) {
                $itemsOut[] = [
                    'qty' => 1,
                    'name' => 'Descuento',
                    'variant_title' => '',
                    'sku' => '',
                    'u_medida' => 'serv.',
                    'bonif_pct' => '0,00',
                    'bonif_imp_fmt' => $fmt($discount),
                    'price_fmt' => $fmt(-$discount),
                    'subtotal_fmt' => $fmt(-$discount),
                ];
            }

            $cbteTipo = (int)($cf['cbte_tipo'] ?? 0);
            $impTotal = (int)($cf['imp_total'] ?? 0);
            $impNeto  = (int)($cf['imp_neto'] ?? 0);
            $impIva   = (int)($cf['imp_iva'] ?? 0);

            $cbteLetter = $this->cbteLetter($cbteTipo, $tipoLabel);
            $cbteCod = $this->cbteCode($cbteTipo);
            $pto = (int)($cf['pto_vta'] ?? 0);
            $nro = (int)($cf['cbte_nro'] ?? 0);

            $condVenta = $this->saleConditionLabel((string)($order['payment_method'] ?? ''));

            $ivaLabel = $fmt($impIva);
            if (in_array($cbteTipo, [11,6], true)) {
                // Factura C (11) y Factura B (6): IVA no discriminado en esta etapa (UX simple).
                $impNeto = $impTotal;
                $ivaLabel = 'No discriminado';
            }

            $totales = [
                'neto' => $fmt($impNeto),
                'iva' => $fmt($impIva),
                'iva_label' => $ivaLabel,
                'otros_tributos' => $fmt(0),
                'total' => $fmt($impTotal),
            ];

            $cae = (string)($cf['cae'] ?? '');
            $caeVto = (string)($cf['cae_vto'] ?? '');

	            // QR: preferimos PNG (si GD está disponible) porque Dompdf lo renderiza de forma consistente.
	            // Si no hay GD, caemos a SVG.
	            $qrUrl = $this->buildArcaQrUrl($cf, $fiscalOut, $fecha);
	            $qrUrlShort = $this->shortUrlForPdf($qrUrl);
	            $qrDataUri = $this->buildQrDataUri($cf, $fiscalOut, $fecha);
            $logoDataUri = $this->loadBrandLogoDataUri();

            $html = $this->renderHtmlView('pdf/factura_c', [
                'tipoLabel' => $tipoLabel,
                'numeroFmt' => $numeroFmt,
                'fecha' => $fecha,
                'orderId' => $orderId,
                'cbte' => [
                    'tipo' => $cbteTipo,
                    'letter' => $cbteLetter,
                    'cod' => $cbteCod,
                    'pto' => $pto,
                    'nro' => $nro,
                    'pto5' => $pto > 0 ? str_pad((string)$pto, 5, '0', STR_PAD_LEFT) : '',
                    'nro8' => $nro > 0 ? str_pad((string)$nro, 8, '0', STR_PAD_LEFT) : '',
                ],
                'condVenta' => $condVenta,
                'fiscal' => $fiscalOut,
                'receptor' => $receptor,
                'items' => $itemsOut,
                'totales' => $totales,
                'cae' => $cae,
                'caeVto' => $caeVto,
                'qrDataUri' => $qrDataUri,
                'qrUrlShort' => $qrUrlShort,
                'qrUrl' => $qrUrl,
                'logoDataUri' => $logoDataUri,
                'pdfEngine' => 'dompdf',
            ]);

            // Dompdf espera un objeto Options (no array). Si pasamos array, puede tirar TypeError y caer al fallback.
            $opts = null;
            if (class_exists($optionsClass)) {
                $opts = new $optionsClass();
                // No necesitamos remotos; todo va inline/base64
                if (method_exists($opts, 'setIsRemoteEnabled')) {
                    $opts->setIsRemoteEnabled(false);
                }
                // Habilitar <script type="text/php"> (números de página, etc.)
                if (method_exists($opts, 'setIsPhpEnabled')) {
                    $opts->setIsPhpEnabled(true);
                }
                // Evitar warnings por fuentes
                if (method_exists($opts, 'setDefaultFont')) {
                    $opts->setDefaultFont('Helvetica');
                }
            }

            $dompdf = $opts ? new $dompdfClass($opts) : new $dompdfClass();
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            return (string)$dompdf->output();
        } catch (\Throwable $e) {
            $this->logPdfError('dompdf', $e);
            return '';
        }
    }

    private function logPdfError(string $engine, \Throwable $e): void
    {
        // Log simple (sin depender de Logger global). No exponemos el error al usuario final.
        try {
            $dir = base_path('storage/logs');
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $file = rtrim($dir, '/\\') . '/pdf.log';
            $line = sprintf(
                "[%s] engine=%s error=%s in %s:%d\n",
                date('Y-m-d H:i:s'),
                $engine,
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );
            @file_put_contents($file, $line, FILE_APPEND);
        } catch (\Throwable $ignore) {
            // no-op
        }
    }

    private function regimeLabel(string $regime): string
    {
        $r = strtolower(trim($regime));
        return match ($r) {
            'monotributo', 'mono', 'monotributista' => 'Monotributo',
            'responsable_inscripto', 'ri', 'resp_insc', 'inscripto' => 'Responsable Inscripto',
            'exento' => 'Exento',
            default => ($regime !== '' ? $regime : '—'),
        };
    }

    private function cbteLetter(int $cbteTipo, string $tipoLabel): string
    {
        // Mapeo estándar WSFE
        return match ($cbteTipo) {
            1 => 'A',
            6, 8 => 'B',
            11, 13 => 'C',
            default => (preg_match('/\b([ABC])\b/i', $tipoLabel, $m) ? strtoupper($m[1]) : '—'),
        };
    }

    private function cbteCode(int $cbteTipo): string
    {
        // Código “COD. XXX” que suele imprimirse en comprobantes.
        return str_pad((string)$cbteTipo, 3, '0', STR_PAD_LEFT);
    }

    private function saleConditionLabel(string $paymentMethod): string
    {
        $m = strtolower(trim($paymentMethod));
        return match ($m) {
            'tarjeta_credito' => 'Tarjeta credito',
            'tarjeta_debito' => 'Tarjeta debito',
            'mercadopago', 'mp' => 'Mercado Pago',
            'transfer', 'transferencia', 'bank_transfer' => 'Transferencia',
            'cash', 'efectivo' => 'Efectivo',
            default => ($paymentMethod !== '' ? $paymentMethod : '—'),
        };
    }

    private function customerTaxConditionLabel(string $cond): string
    {
        $c = strtolower(trim($cond));
        return match ($c) {
            'consumidor_final', 'cf', 'final' => 'Consumidor Final',
            'responsable_inscripto', 'ri', 'resp_insc', 'inscripto' => 'IVA Responsable Inscripto',
            'monotributo', 'mono', 'monotributista' => 'Monotributo',
            'exento' => 'Exento',
            'sujeto_exento' => 'IVA Sujeto Exento',
            default => ($cond !== '' ? $cond : '—'),
        };
    }

    private function formatShippingAddress(?array $order): string
    {
        if (!$order) return '';
        $street = trim((string)($order['shipping_street'] ?? ''));
        $num = trim((string)($order['shipping_number'] ?? ''));
        $floor = trim((string)($order['shipping_floor'] ?? ''));
        $apt = trim((string)($order['shipping_apartment'] ?? ''));
        $city = trim((string)($order['shipping_city'] ?? ''));
        $state = trim((string)($order['shipping_state'] ?? ''));
        $zip = trim((string)($order['shipping_zip'] ?? ''));

        $line1 = trim($street . ' ' . $num);
        $extras = [];
        if ($floor !== '') $extras[] = 'Piso ' . $floor;
        if ($apt !== '') $extras[] = 'Dpto ' . $apt;
        if (!empty($extras)) $line1 = trim($line1 . ' (' . implode(' ', $extras) . ')');

        $line2Parts = array_filter([$city, $state, $zip], fn($v) => trim((string)$v) !== '');
        $line2 = implode(', ', $line2Parts);

        return trim($line1 . ($line2 !== '' ? ' — ' . $line2 : ''));
    }

    private function shortUrlForPdf(string $url, int $max = 42): string
    {
        $u = trim($url);
        if ($u === '') return '';
        if (strlen($u) <= $max) return $u;
        return substr($u, 0, $max - 3) . '...';
    }

    /** Renderiza una vista de app/Views a string (sin layout). */
    private function renderHtmlView(string $view, array $vars = []): string
    {
        $file = base_path('app/Views/pages/' . $view . '.php');
        if (!is_file($file)) {
            // permite ubicar la vista fuera de pages
            $file = base_path('app/Views/' . $view . '.php');
        }
        if (!is_file($file)) return '<html><body>Error: vista PDF no encontrada.</body></html>';
        if (!empty($vars)) extract($vars, EXTR_SKIP);
        ob_start();
        include $file;
        return (string)ob_get_clean();
    }

    /**
     * Genera el QR de constancia según especificación ARCA.
     * Preferimos PNG (si GD está disponible) porque Dompdf lo renderiza de forma consistente.
     * Si no hay GD, usamos SVG como fallback.
     */
    private function buildQrDataUri(array $cf, array $fiscal, string $fecha): ?string
    {
        $qrCodeClass = 'Endroid' . chr(92) . 'QrCode' . chr(92) . 'QrCode';
        if (!class_exists($qrCodeClass)) return null;

        $url = $this->buildArcaQrUrl($cf, $fiscal, $fecha);
        if ($url === '') return null;

        // Helper: crea un QrCode compatible con distintas versiones de endroid/qr-code.
        $makeQr = function (string $text, int $size) use ($qrCodeClass) {
            // v4+/v5/v6
            if (method_exists($qrCodeClass, 'create')) {
                $qr = $qrCodeClass::create($text);
            } else {
                // v3
                $qr = new $qrCodeClass($text);
                if (method_exists($qr, 'setText')) {
                    $qr->setText($text);
                }
            }

            if (method_exists($qr, 'setSize')) {
                $qr->setSize($size);
            }
            // v4+ usa setMargin; v3 también suele tenerlo. Si no existe, ignoramos.
            if (method_exists($qr, 'setMargin')) {
                $qr->setMargin(0);
            }
            // v3 a veces expone setPadding en lugar de setMargin
            if (method_exists($qr, 'setPadding')) {
                $qr->setPadding(0);
            }
            return $qr;
        };

        // Helper: escribe QR a string compatible (writeString en v3 / write()->getString() en v4+).
        $writeQrToString = function ($writer, $qr): string {
            if (method_exists($writer, 'writeString')) {
                return (string)$writer->writeString($qr);
            }
            $res = $writer->write($qr);
            if (is_object($res) && method_exists($res, 'getString')) {
                return (string)$res->getString();
            }
            // fallback súper defensivo
            return (string)$res;
        };

        // 1) PNG (requiere GD)
        $pngWriterClass = 'Endroid' . chr(92) . 'QrCode' . chr(92) . 'Writer' . chr(92) . 'PngWriter';
        if (extension_loaded('gd') && class_exists($pngWriterClass)) {
            try {
                $qr = $makeQr($url, 120);
                $writer = new $pngWriterClass();
                $bin = $writeQrToString($writer, $qr);
                if ($bin !== '') {
                    return 'data:image/png;base64,' . base64_encode($bin);
                }
            } catch (\Throwable $e) {
                $this->logPdfError('qr_png', $e);
                // seguimos a SVG
            }
        }

        // 2) SVG (fallback)
        $svgWriterClass = 'Endroid' . chr(92) . 'QrCode' . chr(92) . 'Writer' . chr(92) . 'SvgWriter';
        if (!class_exists($svgWriterClass)) return null;

        try {
            $qr = $makeQr($url, 120);
            $writer = new $svgWriterClass();
            $svg = $writeQrToString($writer, $qr);
            return $svg !== '' ? ('data:image/svg+xml;base64,' . base64_encode($svg)) : null;
        } catch (\Throwable $e) {
            $this->logPdfError('qr_svg', $e);
            return null;
        }
    }

    private function buildArcaQrUrl(array $cf, array $fiscal, string $fecha): string
    {
        $cuitDigits = preg_replace('/\D+/', '', (string)($fiscal['cuit'] ?? ''));
        if ($cuitDigits === '') return '';
        $payload = [
            'ver' => 1,
            'fecha' => $fecha,
            'cuit' => (int)$cuitDigits,
            'ptoVta' => (int)($cf['pto_vta'] ?? 0),
            'tipoCmp' => (int)($cf['cbte_tipo'] ?? 0),
            'nroCmp' => (int)($cf['cbte_nro'] ?? 0),
            'importe' => (float)($cf['imp_total'] ?? 0),
            'moneda' => (string)($cf['moneda_id'] ?? 'PES'),
            'ctz' => (float)($cf['moneda_ctz'] ?? 1),
            'tipoCodAut' => 'E',
            'codAut' => (string)($cf['cae'] ?? ''),
        ];

        $tipoDocRec = (int)($cf['doc_tipo'] ?? 0);
        $nroDocRec  = (int)($cf['doc_nro'] ?? 0);
        if ($tipoDocRec > 0 && $nroDocRec > 0) {
            $payload['tipoDocRec'] = $tipoDocRec;
            $payload['nroDocRec'] = $nroDocRec;
        }

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if (!$json) return '';
        $b64 = base64_encode($json);
        return 'https://www.arca.gob.ar/fe/qr/?p=' . urlencode($b64);
    }

    private function loadBrandLogoDataUri(): ?string
    {
        $candidates = [
            base_path('public_html/assets/img/branding/miPescaYaventura-leyenda.png'),
        ];
        foreach ($candidates as $path) {
            if (is_file($path)) {
                $bin = file_get_contents($path);
                if ($bin === false || $bin === '') continue;
                return 'data:image/png;base64,' . base64_encode($bin);
            }
        }
        return null;
    }
}

