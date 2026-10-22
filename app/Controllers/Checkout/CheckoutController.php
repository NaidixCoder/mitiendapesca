<?php
namespace App\Controllers\Checkout;

use App\Repositories\Account\AddressRepository;
use App\Repositories\Account\ProfileRepository;
use App\Services\Cart\CartReadService;
use App\Services\Cart\CartSelectionService;
use App\Services\Cart\CartSignatureService;
use App\Services\Cart\CartWriteService;
use App\Services\Checkout\CheckoutFlowService;
use App\Services\Checkout\CheckoutOrderService;
use App\Repositories\Settings\AppSettingsRepository;
use App\Repositories\Users\UserRepository;
use App\Support\Security\CheckoutFiscal;
use App\Services\Pricing\CommercialPriceService;
use App\Support\Format\Tax;
use App\Support\Geo\ProvinciasAR;
use App\Repositories\Users\UserFiscalProfileRepository;

final class CheckoutController
{
    public function index(): void
    {
        auth_required('/checkout');

        $flowSvc = new CheckoutFlowService(db());
        $cartReadSvc = new CartReadService(db());
        $cartWriteSvc = new CartWriteService(db());
        $selectionSvc = new CartSelectionService();

        // Paso actual del wizard (server-driven via ?step=1..4)
        $currentStep = $flowSvc->resolveCurrentStep((int)($_GET['step'] ?? 1));


        $uid = (int)($_SESSION['uid'] ?? 0);
        if ($uid <= 0) { redirect('/login'); return; }

        $profilePhone = '';
        try {
            $profileRepo = new ProfileRepository(db());
            $profile = $profileRepo->findByUserId($uid);
            $profilePhone = trim((string)($profile['phone'] ?? ''));
        } catch (\Throwable $e) {
            $profilePhone = '';
        }

        $emailVerified = (int)($_SESSION['email_verified'] ?? 0);
        try {
            $userRepo = new UserRepository(db());
            $user = $userRepo->findById($uid);
            if (is_array($user)) {
                $emailVerified = (int)($user['email_verified'] ?? 0);
                $_SESSION['email_verified'] = $emailVerified;
            }
        } catch (\Throwable $e) {
            $emailVerified = (int)($_SESSION['email_verified'] ?? 0);
        }

        $rawCart = $cartReadSvc->getCartView();
        // No borrar ítems ocultos: conservarlos, pero forzar revisión del carrito
        // antes de continuar. La validación definitiva ocurre al crear el pedido.
        foreach ((array)($rawCart['items'] ?? []) as $rawItem) {
            if (is_array($rawItem) && array_key_exists('is_publicly_available',$rawItem)
                && empty($rawItem['is_publicly_available'])) {
                flash('warn', 'Hay artículos no disponibles. Quitalos del carrito antes de continuar con la compra.');
                redirect('/carrito?checkout_cambio=1');
                return;
            }
        }

        // Guardrail: validar cambios de stock sobre el carrito crudo (no solo seleccionados).
        // Si alguna linea quedo fuera de stock o con qty mayor al stock, ajustamos y forzamos
        // volver a carrito para revision explicita.
        if (!empty($rawCart['items']) && is_array($rawCart['items'])) {
            $inv = new \App\Repositories\Catalog\InventoryRepository(db());
            $adjustRaw = [];
            foreach ($rawCart['items'] as $it) {
                if (!is_array($it)) {
                    continue;
                }
                $pid = (int)($it['product_id'] ?? 0);
                $vid = (int)($it['variant_id'] ?? 0);
                $qty = max(1, (int)($it['qty'] ?? 1));
                if ($pid <= 0) {
                    continue;
                }

                $stock = ($vid > 0) ? $inv->getVariantStock($vid) : $inv->getProductStock($pid);
                $stock = ($stock === null) ? 0 : (int)$stock;
                if ($stock < $qty) {
                    $adjustRaw[$pid . ':' . $vid] = max(0, $stock);
                }
            }

            if (!empty($adjustRaw)) {
                $cartWriteSvc->bulkUpdate($adjustRaw);
                redirect('/carrito?checkout_cambio=1');
                return;
            }
        }

        $cart = $selectionSvc->apply($rawCart);

        $rawItemsCount = count((array)($rawCart['items'] ?? []));
        $selectedItemsCount = count((array)($cart['items'] ?? []));
        if ($rawItemsCount > 0 && $selectedItemsCount === 0) {
            redirect('/carrito?checkout_cambio=1');
            return;
        }

        // Firma del carrito: debe coincidir con la firma usada por el modulo de Envios.
        // IMPORTANTE: no incluir precio/nombre u otros campos que puedan variar por formateo,
        // descuentos, redondeos, etc. Solo IDs + cantidades.
        $cartSig = CartSignatureService::build((array)($cart['items'] ?? []));

        $freeMinAmount = $flowSvc->resolveFreeMinAmount();
        $invalidRedirect = $flowSvc->invalidateSelectionIfCartChanged($currentStep, $cart, $cartSig, $freeMinAmount);
        if ($invalidRedirect !== null) {
            redirect($invalidRedirect);
            return;
        }

        $paymentRedirect = $flowSvc->ensurePaymentSelectionForStep($currentStep);
        if ($paymentRedirect !== null) {
            redirect($paymentRedirect);
            return;
        }

        // Validacion rapida de stock en cada paso del checkout (sin locks): si el carrito cambio, volvemos al carrito.
        if (!empty($cart['items'])) {
            $inv = new \App\Repositories\Catalog\InventoryRepository(db());
            $adjust = [];
            foreach (($cart['items'] ?? []) as $it) {
                $pid = (int)($it['product_id'] ?? 0);
                $vid = (int)($it['variant_id'] ?? 0);
                $qty = max(1, (int)($it['qty'] ?? 1));
                $stock = ($vid > 0) ? $inv->getVariantStock($vid) : $inv->getProductStock($pid);
                $stock = ($stock === null) ? 0 : (int)$stock;
                if ($stock < $qty) {
                    $adjust[$pid.':'.$vid] = max(0, $stock);
                }
            }
            if (!empty($adjust)) {
                $cartWriteSvc->bulkUpdate($adjust);
                redirect('/carrito?checkout_cambio=1');
                return;
            }
        }


        // Confirmacion de transferencia: puede venir con el carrito vacio (porque ya se creo el pedido)
        $transferOk = isset($_GET['transferencia']) && (string)($_GET['transferencia'] ?? '') === '1';
        $transferOrderId = (int)($_GET['pedido'] ?? 0);

        if (empty($cart['items']) && !($transferOk && $transferOrderId > 0)) {
            // Recovery UX (Mercado Pago): si el usuario refresco (F5) mientras el pago se procesaba,
            // podemos haber creado el pedido pero el se quedo en /checkout sin feedback.
            $lastOid = (int)($_SESSION['checkout_last_order_id'] ?? 0);
            $lastTs  = (int)($_SESSION['checkout_last_order_ts'] ?? 0);

            if ($lastOid > 0 && $lastTs > 0 && (time() - $lastTs) <= 15 * 60) {
                unset($_SESSION['checkout_last_order_id'], $_SESSION['checkout_last_order_ts']);
                flash('ok', 'Tu pedido ya fue registrado. Gracias por tu compra.');
                redirect(url('/cuenta/pedidos/ver').'?id='.$lastOid.'&ok=1');
                return;
            }

            flash('warn', 'Tu carrito esta vacio.');
            redirect('/productos');
            return;
        }

        $addrRepo = new AddressRepository(db());
        $addresses = $addrRepo->listByUser($uid);
        $hasAddresses = !empty($addresses);
        if (!$hasAddresses) {
            if ($currentStep > 1) {
                redirect('/checkout');
                return;
            }
            $currentStep = 1;
        }
        $default = null;
        foreach ($addresses as $a) { if (!empty($a['is_default'])) { $default = (int)$a['id']; break; } }

        // Mercado Pago Bricks (solo checkout)
        $mpPublicKey = (string)env('MP_PUBLIC_KEY', '');
        $extraHead = "\n".
            '<script src="https://sdk.mercadopago.com/js/v2"></script>' . "\n" .
            '<meta name="mp-public-key" content="' . htmlspecialchars($mpPublicKey, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">' . "\n" .
            '<meta name="mp-payer-email" content="' . htmlspecialchars((string)($_SESSION['email'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">' . "\n";

        $bank = [
            'name'  => (string)env('BANK_NAME',''),
            'alias' => (string)env('BANK_ALIAS',''),
            'cbu'   => (string)env('BANK_CBU',''),
            'owner' => (string)env('BANK_OWNER',''),
            'cuit'  => (string)env('BANK_CUIT',''),
        ];
        $transferReservationHours = 24;
        try {
            $settingsRepo = new AppSettingsRepository(db());
            $transferReservationHours = (int)($settingsRepo->get('payments_transfer_reservation_hours', '24') ?? '24');
        } catch (\Throwable $e) {
            $transferReservationHours = 24;
        }
        if ($transferReservationHours < 1) $transferReservationHours = 24;
        if ($transferReservationHours > 720) $transferReservationHours = 720;


        // Analiticas: visitas al checkout
        try {
            if (class_exists(\App\Services\Analytics\AnalyticsService::class)) {
                (new \App\Services\Analytics\AnalyticsService(db()))->trackCheckoutView();
            }
        } catch (\Throwable $e) { /* no bloqueante */ }

        // Totales neto/IVA para UI (regla: Monotributo => no discrimina IVA)

        // Regimen fiscal del vendedor (Ajustes)
        $sellerTaxRegime = 'monotributo';
        try {
            $settingsRepo = new AppSettingsRepository(db());
            $sellerTaxRegime = strtolower(trim((string)$settingsRepo->get('fiscal_seller_tax_regime', 'monotributo')));
            if ($sellerTaxRegime === '') $sellerTaxRegime = 'monotributo';
        } catch (\Throwable $e) { /* no bloqueante */ }

        $taxTotals = ['total_net' => (int)($cart['total'] ?? 0), 'total_vat' => 0];
        try {
            $regime = $sellerTaxRegime;

            $subtotal = (int)($cart['subtotal'] ?? 0);
            $shipping = (int)($cart['shipping'] ?? 0);
            $discount = (int)($cart['discount'] ?? 0);
            $total = max(0, $subtotal + $shipping - $discount);

            if ($regime === 'responsable_inscripto') {
                // calculamos IVA desde productos del carrito (solo sobre lineas de producto)
                $productIds = [];
                foreach (($cart['items'] ?? []) as $it) {
                    $pid = (int)($it['product_id'] ?? 0);
                    if ($pid > 0) $productIds[$pid] = true;
                }
                $productIds = array_keys($productIds);

                $vatRateMap = [];
                if (!empty($productIds)) {
                    $ph = implode(',', array_fill(0, count($productIds), '?'));
                    $st = db()->prepare("SELECT id, iva_rate FROM products WHERE id IN ($ph)");
                    $st->execute($productIds);
                    foreach ($st->fetchAll(\PDO::FETCH_ASSOC) as $r) {
                        $pid = (int)($r['id'] ?? 0);
                        $rate = (float)($r['iva_rate'] ?? 0);
                        $rate = ($rate > 1.0) ? ($rate / 100.0) : $rate;
                        if ($rate < 0.0) $rate = 0.0;
                        $vatRateMap[$pid] = $rate;
                    }
                }

                $orderNet = 0;
                $orderVat = 0;
                foreach (($cart['items'] ?? []) as $it) {
                    $pid = (int)($it['product_id'] ?? 0);
                    $qty = max(0, (int)($it['qty'] ?? 0));
                    $unitTotal = max(0, (int)($it['price'] ?? 0));
                    $lineTotal = $unitTotal * $qty;
                    $rate = (float)($vatRateMap[$pid] ?? 0.0);
                    $lineSplit = Tax::splitTotal($lineTotal, $rate);
                    $orderNet += (int)$lineSplit['net'];
                    $orderVat += (int)$lineSplit['vat'];
                }
                // shipping/discount no discrimina IVA en MVP; se asume neto.
                $orderNet += max(0, $shipping - $discount);
                $taxTotals = ['total_net' => max(0, $orderNet), 'total_vat' => max(0, $orderVat)];
            } else {
                // Monotributo/Exento: no se discrimina IVA
                $taxTotals = ['total_net' => $total, 'total_vat' => 0];
            }
        } catch (\Throwable $e) {
            // no bloqueante
        }

        // Prefill fiscal desde Mi Cuenta (solo si el checkout todavia no guardo nada en sesion)
        try {
            $fRepo = new UserFiscalProfileRepository(db());
            if ($uid > 0 && $fRepo->tableExists()) {
                $row = $fRepo->findByUserId($uid);
                if (is_array($row) && !empty($row)) {
                    CheckoutFiscal::prefillFromUserProfile($row);
                }
            }
        } catch (\Throwable $e) { /* no bloqueante */ }

        // Envios (FIX516): snapshot actual de seleccion (si existe)

        // Snapshot de envio seleccionado (se guarda por AJAX en /checkout/envio/seleccionar)
        [$hasShipSnap, $shipSnap] = $flowSvc->normalizeShippingSnapshot();

        // Si hay snapshot, aplicamos el costo al carrito (para resumen + total).
        $flowSvc->applyShippingToCart($cart, $shipSnap);

        // Reglas checkout (FIX523b):
        // - Para llegar al Paso 3 (Confirmacion/Pago) debe existir un envio seleccionado.
        // - Si aplica Envio Gratis por umbral, auto-seleccionamos envio gratis (cost=0) para no friccionar.
        $shippingStepRedirect = $flowSvc->enforceStep3Shipping($currentStep, $cart, $hasShipSnap, $freeMinAmount, $cartSig);
        if ($shippingStepRedirect !== null) {
            redirect($shippingStepRedirect);
            return;
        }

        // Flags para UI/Stepper
        $hasShipSnapshot = $hasShipSnap;
        $eligibleForFreeShipping = ($freeMinAmount > 0 && (int)($cart['subtotal'] ?? 0) >= $freeMinAmount);

        // Stepper: paso maximo habilitado
        $stepperMax = $hasAddresses ? 2 : 1;
        if ($hasAddresses && ($hasShipSnapshot || $eligibleForFreeShipping)) {
            $stepperMax = 3;
        }
        $selectedPaymentMethod = (string)($_SESSION['checkout_payment']['method'] ?? '');
        $pricing = new CommercialPriceService(new AppSettingsRepository(db()));
        $effectiveCheckoutCart = $pricing->buildCheckoutCart(
            $cart,
            $selectedPaymentMethod !== '' ? $selectedPaymentMethod : 'transferencia'
        );
        $listPriceCheckoutCart = $pricing->buildCheckoutCart($cart, 'tarjeta_credito');
        $transferTotal = (int)($pricing->buildCheckoutCart($cart, 'transferencia')['total'] ?? 0);
        $creditTotal = (int)($pricing->buildCheckoutCart($cart, 'tarjeta_credito')['total'] ?? 0);
        $debitTotal = (int)($pricing->buildCheckoutCart($cart, 'tarjeta_debito')['total'] ?? 0);
        $transferDiscount = ($creditTotal > 0 && $creditTotal > $transferTotal)
            ? (int)round((1 - ($transferTotal / $creditTotal)) * 100)
            : 0;
        $debitDiscount = (int)round($pricing->debitDiscountPercent());
        $paymentOptionCards = [
            'transferencia' => [
                'method' => 'transferencia',
                'title' => 'Transferencia',
                'subtitle' => 'CBU / Alias bancario',
                'badge' => $transferDiscount > 0 ? ($transferDiscount . '% OFF') : '',
                'badgeVariant' => 'solid',
                'icon' => 'bank-solid',
                'total' => $transferTotal,
            ],
            'tarjeta_credito' => [
                'method' => 'tarjeta_credito',
                'title' => 'Tarjeta credito',
                'subtitle' => $pricing->installmentsCount() . ' cuotas sin interes',
                'badge' => '',
                'badgeVariant' => '',
                'icon' => 'card-credit-solid',
                'total' => $creditTotal,
            ],
            'tarjeta_debito' => [
                'method' => 'tarjeta_debito',
                'title' => 'Tarjeta debito',
                'subtitle' => 'Pago inmediato con debito',
                'badge' => $debitDiscount > 0 ? ($debitDiscount . '% OFF') : '',
                'badgeVariant' => 'ghost',
                'icon' => 'card-debit-solid',
                'total' => $debitTotal,
            ],
        ];
        if ($hasAddresses && $selectedPaymentMethod !== '' && in_array($selectedPaymentMethod, ['tarjeta_credito','tarjeta_debito','transferencia'], true)) {
            $stepperMax = 4;
        }

        // Permisos de navegación del stepper (server-driven)
        $canStep2 = $hasAddresses;
        $canStep3 = ($hasAddresses && ($hasShipSnapshot || $eligibleForFreeShipping));
        $canStep4 = ($canStep3 && $selectedPaymentMethod !== '' && in_array($selectedPaymentMethod, ['tarjeta_credito','tarjeta_debito','transferencia'], true));

        // Datos de envio para Step2 (inputs hidden + estado boton)
        $shippingMethod = '';
        $shippingAgencyId = '';
        if ($hasShipSnapshot && is_array($shipSnap)) {
            $mode = strtolower(trim((string)($shipSnap['mode'] ?? '')));
            if ($mode === 'free') {
                $shippingMethod = 'D|FREE';
            } else {
                $m = strtoupper($mode);
                $code = trim((string)($shipSnap['service_code'] ?? ''));
                if ($m !== '' && $code !== '') {
                    $shippingMethod = $m.'|'.$code;
                }
            }
            $shippingAgencyId = (string)($shipSnap['agency_id'] ?? '');
        }

        // Direccion seleccionada: priorizamos la preferencia explicita del usuario.
        $selectedAddressId = (int)($default ?? 0);
        $sessionSelectedAddressId = (int)($_SESSION['checkout_address_selected_id'] ?? 0);
        if ($sessionSelectedAddressId > 0) {
            $sessionAddressIsValid = false;
            foreach ($addresses as $a) {
                if ((int)($a['id'] ?? 0) === $sessionSelectedAddressId) {
                    $selectedAddressId = $sessionSelectedAddressId;
                    $sessionAddressIsValid = true;
                    break;
                }
            }
            if (!$sessionAddressIsValid) {
                unset($_SESSION['checkout_address_selected_id']);
            }
        } elseif ($hasShipSnap && is_array($shipSnap)) {
            $snapAddrId = (int)($shipSnap['address_id'] ?? 0);
            if ($snapAddrId > 0) {
                foreach ($addresses as $a) {
                    if ((int)($a['id'] ?? 0) === $snapAddrId) {
                        $selectedAddressId = $snapAddrId;
                        break;
                    }
                }
            }
        }

        $moneyAr = static function (int $value): string {
            return '$ '.number_format((float)$value, 0, ',', '.');
        };
        $addressById = [];
        foreach ($addresses as $addrRow) {
            $addrId = (int)($addrRow['id'] ?? 0);
            if ($addrId > 0) {
                $addressById[$addrId] = $addrRow;
            }
        }
        $formatAddressLines = static function (array $address): array {
            $line1Parts = [];
            $street = trim((string)($address['street'] ?? ''));
            $number = trim((string)($address['number'] ?? ''));
            if ($street !== '') $line1Parts[] = $street;
            if ($number !== '') $line1Parts[] = $number;
            $line1 = trim(implode(' ', $line1Parts));

            $line1Extra = [];
            $floor = trim((string)($address['floor'] ?? ''));
            $apartment = trim((string)($address['apartment'] ?? ''));
            if ($floor !== '') $line1Extra[] = 'Piso '.$floor;
            if ($apartment !== '') $line1Extra[] = 'Depto '.$apartment;
            if ($line1 !== '' && !empty($line1Extra)) {
                $line1 .= ' - '.implode(' ', $line1Extra);
            } elseif ($line1 === '' && !empty($line1Extra)) {
                $line1 = implode(' ', $line1Extra);
            }

            $city = trim((string)($address['city'] ?? ''));
            $state = trim((string)($address['state'] ?? ''));
            $zip = trim((string)($address['zip'] ?? ''));
            $line2Parts = [];
            if ($city !== '') $line2Parts[] = $city;
            if ($state !== '') $line2Parts[] = $state;
            if ($zip !== '') $line2Parts[] = 'CP '.$zip;
            $line2 = implode(' - ', $line2Parts);

            $lines = [];
            if ($line1 !== '') $lines[] = $line1;
            if ($line2 !== '') $lines[] = $line2;
            return $lines;
        };

        $shippingSummary = [
            'label_tipo' => 'Sin envio confirmado',
            'costo_formateado' => 'Pendiente',
            'servicio' => '',
            'destino_lineas' => [],
            'updated_at' => '',
            'editable_links' => [
                'address' => base_url('/checkout?step=1'),
                'shipping' => base_url('/checkout?step=2'),
                'payment' => base_url('/checkout?step=3'),
            ],
        ];
        if ($hasShipSnap && is_array($shipSnap)) {
            $snapMode = strtolower(trim((string)($shipSnap['mode'] ?? '')));
            if ($snapMode === 's') {
                $shippingSummary['label_tipo'] = 'Retiro en sucursal';
            } elseif ($snapMode === 'l') {
                $shippingSummary['label_tipo'] = 'Retiro en el local';
            } else {
                $shippingSummary['label_tipo'] = 'A domicilio';
            }

            $snapCost = max(0, (int)($shipSnap['cost'] ?? 0));
            $shippingSummary['costo_formateado'] = ($snapCost === 0) ? 'Gratis' : $moneyAr($snapCost);
            $shippingSummary['servicio'] = trim((string)($shipSnap['service_name'] ?? $shipSnap['label'] ?? ''));

            if ($snapMode === 's') {
                $agencyName = trim((string)($shipSnap['agency_name'] ?? 'Sucursal'));
                $agencyAddress = trim((string)($shipSnap['agency_address'] ?? ''));
                if ($agencyName !== '') $shippingSummary['destino_lineas'][] = $agencyName;
                if ($agencyAddress !== '') $shippingSummary['destino_lineas'][] = $agencyAddress;
            } elseif ($snapMode === 'l') {
                $localAddress = trim((string)($shipSnap['agency_address'] ?? $shipSnap['local_address'] ?? ''));
                $localNotes = trim((string)($shipSnap['agency_name'] ?? $shipSnap['local_notes'] ?? ''));
                if ($localAddress !== '') $shippingSummary['destino_lineas'][] = $localAddress;
                if ($localNotes !== '') $shippingSummary['destino_lineas'][] = $localNotes;
            } else {
                $snapAddressId = (int)($shipSnap['address_id'] ?? 0);
                if ($snapAddressId <= 0) {
                    $snapAddressId = $selectedAddressId;
                }
                $address = ($snapAddressId > 0 && isset($addressById[$snapAddressId])) ? (array)$addressById[$snapAddressId] : [];
                if (!empty($address)) {
                    $shippingSummary['destino_lineas'] = $formatAddressLines($address);
                }
            }

            $updatedAtRaw = trim((string)($shipSnap['updated_at'] ?? ''));
            if ($updatedAtRaw !== '') {
                $updatedAtTs = strtotime($updatedAtRaw);
                if ($updatedAtTs !== false) {
                    $shippingSummary['updated_at'] = date('d/m/Y H:i', $updatedAtTs);
                }
            }
        }

        $paymentSummary = [
            'method_key' => $selectedPaymentMethod,
            'method_label' => 'Pendiente',
        ];
        $paymentLabels = [
            'tarjeta_credito' => 'Tarjeta credito',
            'tarjeta_debito' => 'Tarjeta debito',
            'transferencia' => 'Transferencia bancaria',
        ];
        if ($selectedPaymentMethod !== '') {
            $paymentSummary['method_total_formatted'] = '$ '.number_format((float)((int)($effectiveCheckoutCart['total'] ?? 0)), 0, ',', '.');
        }
        if (isset($paymentLabels[$selectedPaymentMethod])) {
            $paymentSummary['method_label'] = $paymentLabels[$selectedPaymentMethod];
        }

        $summarySubtotal = (int)($listPriceCheckoutCart['subtotal'] ?? ($effectiveCheckoutCart['subtotal'] ?? 0));
        $summaryShipping = (int)($effectiveCheckoutCart['shipping'] ?? ($cart['shipping'] ?? 0));
        $effectiveSubtotal = (int)($effectiveCheckoutCart['subtotal'] ?? 0);
        $summaryDiscount = max(0, $summarySubtotal - $effectiveSubtotal);
        $summaryTotal = max(0, $summarySubtotal + $summaryShipping - $summaryDiscount);

        render_page('checkout/index', [
            'pageId'     => 'checkout',
            'cart'       => $cart,
            'cartSig'    => $cartSig,
            'currentStep'=> $currentStep,
            'stepperMax' => $stepperMax,
            'canStep2' => $canStep2,
            'canStep3' => $canStep3,
            'canStep4' => $canStep4,
            'selectedPaymentMethod' => $selectedPaymentMethod,
            'effectiveCheckoutCart' => $effectiveCheckoutCart,
            'summarySubtotal' => $summarySubtotal,
            'summaryShipping' => $summaryShipping,
            'summaryDiscount' => $summaryDiscount,
            'summaryTotal' => $summaryTotal,
            'paymentOptionCards' => $paymentOptionCards,
            'addresses'  => $addresses,
            'defaultId'  => $default,
            'selectedAddressId' => $selectedAddressId,
            'profilePhone' => $profilePhone,
            'bank'       => $bank,
            'freeMinAmount' => $freeMinAmount,
            'hasShipSnapshot' => $hasShipSnapshot,
            'shippingMethod' => $shippingMethod,
            'shippingAgencyId' => $shippingAgencyId,
            'shippingSummary' => $shippingSummary,
            'paymentSummary' => $paymentSummary,
            'transferReservationHours' => $transferReservationHours,
            'emailVerified' => $emailVerified,
            'fiscal'     => CheckoutFiscal::getSession(),
            'taxTotals'     => $taxTotals,
            'sellerTaxRegime' => $sellerTaxRegime,
            'provinces' => ProvinciasAR::all(),
            'extraHead'     => $extraHead,
        ]);
    }

    public function confirm(): void
    {
        // Flujo antiguo (pedido sin pago) deshabilitado.
        // El pedido se crea unicamente cuando el webhook confirma el pago.
        auth_required('/checkout');
        flash('warn', 'Para finalizar tu compra, usa el boton "Pagar".');
        redirect('/checkout');
    }

    public function selectAddress(): void
    {
        auth_required('/checkout');
        verify_csrf_or_abort();

        header('Content-Type: application/json; charset=utf-8');

        $uid = (int)($_SESSION['uid'] ?? 0);
        if ($uid <= 0) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'unauthorized'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $body = json_decode(file_get_contents('php://input') ?: 'null', true);
        $addressId = (int)($body['address_id'] ?? ($_POST['address_id'] ?? 0));
        if ($addressId <= 0) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Selecciona una direccion valida.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $repo = new AddressRepository(db());
            $address = $repo->findForUser($addressId, $uid);
            if (!is_array($address)) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'error' => 'Direccion no encontrada.'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $_SESSION['checkout_address_selected_id'] = $addressId;
            unset($_SESSION['checkout_shipping'], $_SESSION['checkout_shipping_calc'], $_SESSION['checkout_payment']);

            echo json_encode([
                'ok' => true,
                'address_id' => $addressId,
                'reset_shipping' => true,
                'reset_payment' => true,
            ], JSON_UNESCAPED_UNICODE);
            return;
        } catch (\Throwable $e) {
            if (function_exists('log_error')) {
                log_error('Checkout selectAddress: ' . $e->getMessage());
            }
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'No se pudo actualizar la direccion.'], JSON_UNESCAPED_UNICODE);
            return;
        }
    }

    public function transferencia(): void
    {
        auth_required('/checkout');
        verify_csrf_or_abort();
        $cartReadSvc = new CartReadService(db());
        $selectionSvc = new CartSelectionService();

        $uid = (int)($_SESSION['uid'] ?? 0);
        if ($uid <= 0) { redirect('/login'); return; }

        try {
            $userRepo = new UserRepository(db());
            $user = $userRepo->findById($uid);
            $emailVerified = (int)($user['email_verified'] ?? 0);
            $_SESSION['email_verified'] = $emailVerified;
            if ($emailVerified !== 1) {
                flash('error', 'Necesitamos verificar tu email antes de reservar stock por transferencia.');
                redirect('/checkout?step=4&verify_email=1');
                return;
            }
        } catch (\Throwable $e) {
            flash('error', 'No pudimos validar tu email en este momento. Intenta nuevamente.');
            redirect('/checkout?step=4&verify_email=1');
            return;
        }

        $addressId = (int)($_POST['address_id'] ?? 0);
        if ($addressId <= 0) {
            flash('error', 'Selecciona una direccion para continuar.');
            redirect('/checkout');
            return;
        }

        // Requerimos envio seleccionado antes de confirmar por transferencia (FIX523)
        $shipSnap = $_SESSION['checkout_shipping'] ?? null;
        $hasShip = is_array($shipSnap) && array_key_exists('cost', $shipSnap);
        if (!$hasShip) {
            $settingsRepo = new AppSettingsRepository(db());
            $freeMinAmount = (int)($settingsRepo->get('shipping_free_min_amount', '0') ?? 0);
            if ($freeMinAmount < 0) $freeMinAmount = 0;

            $cart = $selectionSvc->apply($cartReadSvc->getCartView());
            $sub = (int)($cart['subtotal'] ?? 0);

            if ($freeMinAmount > 0 && $sub >= $freeMinAmount) {
                $_SESSION['checkout_shipping'] = [
                    'mode' => 'free',
                    'service_code' => 'FREE',
                    'label' => 'Envio gratis',
                    'cost' => 0,
                    'cart_sig' => '',
                    'created_at' => time(),
                ];
            } else {
                flash('error', 'Para continuar, recalcula y confirma el envio.');
                redirect('/checkout?step=2');
                return;
            }
        }

        $svc = new CheckoutOrderService(db());
        [$ok, $msg, $orderId] = $svc->confirm($uid, $addressId, 'transferencia');
        if (!$ok || $orderId <= 0) {
            $m = (string)($msg ?? '');
            if (strpos($m, 'CART_CHANGED:') === 0) {
                flash('warn', trim(substr($m, strlen('CART_CHANGED:'))));
                redirect('/carrito?checkout_cambio=1');
                return;
            }
            flash('error', $m !== '' ? $m : 'No se pudo crear el pedido.');
            redirect('/checkout');
            return;
        }

        // Recovery UX: si el usuario refresca/corta el request justo al confirmar,
        // lo redirigimos al pedido al volver a /checkout (misma logica que MP).
        $_SESSION['checkout_last_order_id'] = (int)$orderId;
        $_SESSION['checkout_last_order_ts'] = time();

        // Analiticas: compra por transferencia (dedupe por order_id)
        try {
            if (class_exists(\App\Services\Analytics\AnalyticsService::class)) {
                (new \App\Services\Analytics\AnalyticsService(db()))->trackPurchase((int)$orderId, 'transferencia');
            }
        } catch (\Throwable $e) { /* no bloqueante */ }

        $selectionSvc->clear();

        flash('ok', 'Pedido creado. Queda pendiente de pago por transferencia.');
        redirect(url('/cuenta/pedidos/ver').'?id='.(int)$orderId.'&ok=1&metodo=transferencia');
    }
}


