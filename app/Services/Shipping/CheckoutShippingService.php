<?php
namespace App\Services\Shipping;

use App\Repositories\Account\AddressRepository;
use App\Repositories\Settings\AppSettingsRepository;
use App\Services\Cart\CartReadService;
use App\Services\Cart\CartSelectionService;
use App\Services\Cart\CartSignatureService;
use App\Services\Shipping\CorreoArgentino\CorreoClient;
use App\Services\Shipping\CorreoArgentino\CorreoConfig;
use App\Services\Shipping\Support\ProvinceCodeMapper;
use App\Support\Geo\AddressGeoValidator;
use PDO;

final class CheckoutShippingService
{
    public function __construct(private PDO $db) {}

    /**
     * Firma determinÃ­stica de la direcciÃ³n para invalidar cotizaciones cuando cambia el destino.
     * IMPORTANT: no incluye campos â€œdecorativosâ€ (label/notes) ni datos personales irrelevantes.
     *
     * @param array<string,mixed> $addr
     */
    private function buildAddressSignature(array $addr): string
    {
        $parts = [
            (string)($addr['zip'] ?? ''),
            (string)($addr['state'] ?? ''),
            (string)($addr['city'] ?? ''),
            (string)($addr['street'] ?? ''),
            (string)($addr['number'] ?? ''),
            (string)($addr['floor'] ?? ''),
            (string)($addr['apartment'] ?? ''),
        ];
        $parts = array_map(static fn($v) => trim(mb_strtolower((string)$v, 'UTF-8')), $parts);
        $payload = json_encode($parts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return sha1((string)$payload);
    }

    /**
     * Calcula dimensiones/peso para cotizaciÃ³n.
     *
     * Reglas:
     * - Soporta productos simples y con variantes.
     * - Para variantes, si la variante tiene ship_* configurado, prevalece.
     * - Si faltan datos, NO se bloquea el checkout: se usan los defaults.
     *
     * @param array<int,array<string,mixed>> $items
     * @param array{weight:int,height:int,width:int,length:int} $defaults
     * @return array{dims:array{weight:int,height:int,width:int,length:int},missing_count:int}
     */
    private function calculatePackageFromCartItems(array $items, array $defaults): array
    {
        $dims = [
            'weight' => 0,
            'height' => 0,
            'width'  => 0,
            'length' => 0,
        ];
        $missingCount = 0;

        $productIds = [];
        $variantIds = [];
        foreach ($items as $it) {
            $pid = (int)($it['product_id'] ?? $it['id'] ?? 0);
            if ($pid > 0) $productIds[] = $pid;
            $vid = (int)($it['variant_id'] ?? 0);
            if ($vid > 0) $variantIds[] = $vid;
        }
        $productIds = array_values(array_unique(array_filter($productIds, fn($v)=>$v>0)));
        $variantIds = array_values(array_unique(array_filter($variantIds, fn($v)=>$v>0)));

        // Productos
        $pmap = [];
        if ($productIds) {
            $ph = implode(',', array_fill(0, count($productIds), '?'));
            $sql = "SELECT id, ship_weight, ship_height, ship_width, ship_length FROM products WHERE id IN ($ph)";
            $st = $this->db->prepare($sql);
            $st->execute($productIds);
            $rows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            foreach ($rows as $r) {
                $pmap[(int)$r['id']] = $r;
            }
        }

        // Variantes (con ship_* por variante)
        $vmap = [];
        if ($variantIds) {
            $ph = implode(',', array_fill(0, count($variantIds), '?'));
            try {
                $sql = "SELECT id, product_id, ship_weight, ship_height, ship_width, ship_length FROM product_variants WHERE id IN ($ph)";
                $st = $this->db->prepare($sql);
                $st->execute($variantIds);
                $rows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                foreach ($rows as $r) {
                    $vmap[(int)$r['id']] = $r;
                }
            } catch (\Throwable $e) {
                // Compat: si la DB aÃºn no tiene columnas ship_* en variantes, ignoramos.
                $vmap = [];
            }
        }

        foreach ($items as $it) {
            $pid = (int)($it['product_id'] ?? $it['id'] ?? 0);
            if ($pid <= 0) continue;
            $qty = max(1, (int)($it['qty'] ?? $it['quantity'] ?? 1));
            $vid = (int)($it['variant_id'] ?? 0);
            $vid = ($vid > 0) ? $vid : 0;

            $p = $pmap[$pid] ?? null;
            $v = $vid ? ($vmap[$vid] ?? null) : null;

            $w = null; $h = null; $wi = null; $l = null;

            if ($v) {
                $w  = isset($v['ship_weight']) ? (int)$v['ship_weight'] : null;
                $h  = isset($v['ship_height']) ? (int)$v['ship_height'] : null;
                $wi = isset($v['ship_width'])  ? (int)$v['ship_width']  : null;
                $l  = isset($v['ship_length']) ? (int)$v['ship_length'] : null;
            }

            // Fallback a producto
            if (($w === null || $w <= 0) && $p && isset($p['ship_weight'])) $w = (int)$p['ship_weight'];
            if (($h === null || $h <= 0) && $p && isset($p['ship_height'])) $h = (int)$p['ship_height'];
            if (($wi === null || $wi <= 0) && $p && isset($p['ship_width']))  $wi = (int)$p['ship_width'];
            if (($l === null || $l <= 0) && $p && isset($p['ship_length'])) $l = (int)$p['ship_length'];

            $missingThis = false;

            if ($w === null || $w <= 0) { $w = (int)$defaults['weight']; $missingThis = true; }
            if ($h === null || $h <= 0) { $h = (int)$defaults['height']; $missingThis = true; }
            if ($wi === null || $wi <= 0) { $wi = (int)$defaults['width']; $missingThis = true; }
            if ($l === null || $l <= 0) { $l = (int)$defaults['length']; $missingThis = true; }
            if ($missingThis) $missingCount++;

            $dims['weight'] += ((int)$w) * $qty;
            $dims['height'] = max((int)$dims['height'], (int)$h);
            $dims['width']  = max((int)$dims['width'], (int)$wi);
            $dims['length'] = max((int)$dims['length'], (int)$l);
        }

        // Si quedÃ³ vacÃ­o por algÃºn motivo, aplicamos defaults.
        if ($dims['weight'] <= 0) $dims['weight'] = (int)$defaults['weight'];
        if ($dims['height'] <= 0) $dims['height'] = (int)$defaults['height'];
        if ($dims['width']  <= 0) $dims['width']  = (int)$defaults['width'];
        if ($dims['length'] <= 0) $dims['length'] = (int)$defaults['length'];

        return ['dims' => $dims, 'missing_count' => $missingCount];
    }

    /** @return array{0:bool,1:string,2:array<string,mixed>} */
    public function calculateOptions(int $userId, int $addressId): array
    {
        $cartSvc = new CartReadService($this->db);
        $selectionSvc = new CartSelectionService();
        $cart = $selectionSvc->apply($cartSvc->getCartView());
        $subtotal = (int)($cart['subtotal'] ?? 0);

        if ($subtotal <= 0 || empty($cart['items'])) {
            return [false, 'Tu carrito está vacÃ­o.', []];
        }

        // Firma determinÃ­stica del carrito: NO debe depender del orden de items.
        $cartSig = CartSignatureService::build((array)($cart['items'] ?? []));

        $addrRepo = new AddressRepository($this->db);
        $addr = $addrRepo->findForUser($addressId, $userId);
        if (!$addr) {
            return [false, 'DirecciÃ³n invÃ¡lida.', []];
        }

        // Firma de direcciÃ³n para invalidar cÃ¡lculos/selecciones cuando cambia el destino.
        $addressSig = $this->buildAddressSignature($addr);

        $zipDest = trim((string)($addr['zip'] ?? ''));
        // Puede venir null/vacÃ­a segÃºn el estado de la direcciÃ³n. Normalizamos a string.
        $cityDest = trim((string)($addr['city'] ?? ''));
        $stateDest = trim((string)($addr['state'] ?? ''));
        if ($zipDest === '') {
            return [false, 'La direcciÃ³n seleccionada no tiene cÃ³digo postal.', []];
        }

        if ($cityDest === '' || $stateDest === '') {
            return [false, 'La direccion seleccionada esta incompleta. Editala para continuar.', []];
        }

        $geo = AddressGeoValidator::validate($zipDest, $cityDest, $stateDest);
        if (!$geo['ok']) {
            $detail = trim(implode(' ', array_values($geo['errors'])));
            $message = 'La direccion seleccionada tiene datos inconsistentes.';
            if ($detail !== '') {
                $message .= ' ' . $detail;
            }
            $message .= ' Editala para continuar.';
            return [false, $message, []];
        }

        $settings = new AppSettingsRepository($this->db);

        // Origen (tu tienda) â€” requerido para /rates
        $zipOrigin = trim((string)($settings->get('shipping_origin_postal_code', '') ?? ''));
        if ($zipOrigin === '') {
            return [false, 'Falta configurar el cÃ³digo postal de origen en Ajustes â†’ EnvÃ­os.', []];
        }

        // EnvÃ­o gratis desde $X (0 = desactivado)
        $freeMin = (int)($settings->get('shipping_free_min_amount', '0') ?? 0);
        if ($freeMin < 0) $freeMin = 0;
        $isFree = ($freeMin > 0 && $subtotal >= $freeMin);
        $localPickupEnabled = ((string)($settings->get('shipping_local_pickup_enabled', '1') ?? '1') === '1');
        $localPickupLabel = trim((string)($settings->get('shipping_local_pickup_label', 'Retirar en el local') ?? 'Retirar en el local'));
        $localPickupAddress = trim((string)($settings->get('shipping_local_pickup_address', '') ?? ''));
        $localPickupNotes = trim((string)($settings->get('shipping_local_pickup_notes', '') ?? ''));
        $localPickupCost = (int)($settings->get('shipping_local_pickup_cost', '0') ?? 0);
        if ($localPickupCost < 0) $localPickupCost = 0;
        $localPickupMapsUrl = trim((string)($settings->get('shipping_local_pickup_maps_url', '') ?? ''));
        $localPickupPlaceId = trim((string)($settings->get('shipping_local_pickup_place_id', '') ?? ''));
        $localPickupMapLat = str_replace(',', '.', trim((string)($settings->get('shipping_local_pickup_map_lat', '') ?? '')));
        $localPickupMapLng = str_replace(',', '.', trim((string)($settings->get('shipping_local_pickup_map_lng', '') ?? '')));
        $localPickupMapZoom = (int)($settings->get('shipping_local_pickup_map_zoom', '17') ?? 17);
        if ($localPickupMapZoom < 1) $localPickupMapZoom = 17;
        if ($localPickupMapZoom > 21) $localPickupMapZoom = 21;
        if ($localPickupAddress === '') {
            $localPickupAddress = trim(implode(' - ', array_filter([
                trim(implode(' ', array_filter([
                    trim((string)($settings->get('shipping_sender_street', '') ?? '')),
                    trim((string)($settings->get('shipping_sender_number', '') ?? '')),
                ]))),
                trim((string)($settings->get('shipping_sender_city', '') ?? '')),
                trim((string)($settings->get('shipping_sender_state', '') ?? '')),
            ])));
        }

        // Dimensiones: se calculan por Ã­tems (producto/variante) con fallback a Ajustes (FIX519)
// NO se bloquea el checkout si faltan datos: se usan los defaults.
$defWeight = max(1, (int)($settings->get('shipping_default_weight', '1000') ?? 1000));
$defHeight = max(1, (int)($settings->get('shipping_default_height', '10') ?? 10));
$defWidth  = max(1, (int)($settings->get('shipping_default_width', '10') ?? 10));
$defLength = max(1, (int)($settings->get('shipping_default_length', '10') ?? 10));

$extraCm = max(0, (int)($settings->get('shipping_packaging_extra_cm', '0') ?? 0));
$extraWeightPctRaw = (string)($settings->get('shipping_packaging_extra_weight_pct', '0') ?? '0');
$extraWeightPctRaw = str_replace(',', '.', trim($extraWeightPctRaw));
$extraWeightPct = (float)$extraWeightPctRaw;
if ($extraWeightPct < 0) $extraWeightPct = 0;
if ($extraWeightPct > 100) $extraWeightPct = 100;

$calc = $this->calculatePackageFromCartItems($cart['items'], [
    'weight' => $defWeight,
    'height' => $defHeight,
    'width'  => $defWidth,
    'length' => $defLength,
]);

$dims = $calc['dims'];
$missingCount = (int)($calc['missing_count'] ?? 0);

// Packaging extras
$dims['height'] += $extraCm;
$dims['width']  += $extraCm;
$dims['length'] += $extraCm;
$dims['weight'] = (int)max(1, round($dims['weight'] * (1.0 + ($extraWeightPct / 100.0))));


        // Sucursales (para opciÃ³n retiro)

        $provinceCode = ProvinceCodeMapper::fromState($stateDest);
        $agencies = [];
        $recommendedAgency = null;

        $client = new CorreoClient($this->db);

        // Modo QA (apitest): si Correo no habilita /users/validate, no tendremos customerId.
        // Para no bloquear el flujo de checkout, devolvemos opciones simuladas (cerrable) y mantenemos snapshot.
        $cfg = (new CorreoConfig($this->db))->get();
        $isTestMode = ((string)($cfg['mode'] ?? 'test') === 'test');

        $customerId = trim((string)($cfg['customer_id'] ?? ''));
        if ($customerId === '') {
            try { $customerId = trim((string)($settings->get('correo_customer_id','') ?? '')); } catch (\Throwable $e) {}
        }



        if ($provinceCode !== '') {
            [$okAg, $agData] = $client->agencies($provinceCode);
            if ($okAg && is_array($agData)) {
                $agencies = $agData;
                if (!empty($agencies) && $cityDest !== '') {
                    $recommendedAgency = $this->recommendAgencyByCity($agencies, $cityDest);
                }
            }
        }

        // Opciones
        $options = [];
        $warnings = [];

        if ($isFree) {
            $options[] = [
                'mode' => 'D',
                'service_code' => 'free',
                'option_key' => 'D|free|0',
                'service_name' => 'EnvÃ­o gratis (a domicilio)',
                'cost' => 0,
                'meta' => ['free' => true],
            ];
            $options[] = [
                'mode' => 'S',
                'service_code' => 'free_pickup',
                'option_key' => 'S|free_pickup|0',
                'service_name' => 'EnvÃ­o gratis (retiro en sucursal)',
                'cost' => 0,
                'meta' => ['free' => true],
            ];

            if ($localPickupEnabled) {
                $options[] = [
                    'mode' => 'L',
                    'service_code' => 'local_pickup',
                    'option_key' => 'L|local_pickup|' . $localPickupCost,
                    'service_name' => ($localPickupLabel !== '' ? $localPickupLabel : 'Retirar en el local'),
                    'cost' => $localPickupCost,
                    'meta' => [
                        'local_pickup' => true,
                        'local_address' => $localPickupAddress,
                        'local_notes' => $localPickupNotes,
                        'local_maps_url' => $localPickupMapsUrl,
                        'local_place_id' => $localPickupPlaceId,
                        'local_map_lat' => $localPickupMapLat,
                        'local_map_lng' => $localPickupMapLng,
                        'local_map_zoom' => $localPickupMapZoom,
                    ],
                ];
            }

            return [true, 'OK', [
                'subtotal' => $subtotal,
                'free' => true,
                'free_min_amount' => $freeMin,
                'options' => $options,
                'warnings' => $warnings,
                'agencies' => $agencies,
                'recommendedAgency' => $recommendedAgency,
                'address_city' => $cityDest,
                'address_zip' => $zipDest,
                'package' => $dims,
                'package_missing_items' => $missingCount,
                'cart_sig' => $cartSig,
                'address_id' => $addressId,
                'address_sig' => $addressSig,
            ]];
        }

        if ($isTestMode && $customerId === '') {
            // Costos simulados (ARS) para QA. Se pueden afinar luego; por ahora destraba pruebas end-to-end.
            $simD = max(0, (int)($settings->get('shipping_qa_simulated_dom_cost', '6500') ?? 6500));
            $simS = max(0, (int)($settings->get('shipping_qa_simulated_pickup_cost', '4500') ?? 4500));
            $warnings[] = 'QA: Correo Argentino (apitest) no valida tu usuario, por eso la cotizaciÃ³n es simulada. En ProducciÃ³n se calcula real.';

            $options = [
                [
                    'mode' => 'D',
                    'service_code' => 'qa_dom',
                    'option_key' => 'D|qa_dom|'.$simD,
                    'service_name' => 'A domicilio (QA simulado)',
                    'cost' => $simD,
                    'meta' => ['simulated' => true],
                ],
                [
                    'mode' => 'S',
                    'service_code' => 'qa_suc',
                    'option_key' => 'S|qa_suc|'.$simS,
                    'service_name' => 'Retiro en sucursal (QA simulado)',
                    'cost' => $simS,
                    'meta' => ['simulated' => true],
                ],
            ];
            if ($localPickupEnabled) {
                $options[] = [
                    'mode' => 'L',
                    'service_code' => 'local_pickup',
                    'option_key' => 'L|local_pickup|' . $localPickupCost,
                    'service_name' => ($localPickupLabel !== '' ? $localPickupLabel : 'Retirar en el local'),
                    'cost' => $localPickupCost,
                    'meta' => [
                        'local_pickup' => true,
                        'local_address' => $localPickupAddress,
                        'local_notes' => $localPickupNotes,
                        'local_maps_url' => $localPickupMapsUrl,
                        'local_place_id' => $localPickupPlaceId,
                        'local_map_lat' => $localPickupMapLat,
                        'local_map_lng' => $localPickupMapLng,
                        'local_map_zoom' => $localPickupMapZoom,
                    ],
                ];
            }

            return [true, 'OK', [
                'subtotal' => $subtotal,
                'free' => false,
                'free_min_amount' => $freeMin,
                'options' => $options,
                'warnings' => $warnings,
                'agencies' => [],
                'recommendedAgency' => null,
                'address_city' => $cityDest,
                'address_zip' => $zipDest,
                'package' => $dims,
                'package_missing_items' => $missingCount,
                'cart_sig' => $cartSig,
                'address_id' => $addressId,
                'address_sig' => $addressSig,
            ]];
        }

        // Cotizamos domicilio + sucursal
        [$okD, $ratesD] = $client->rates([
            'postalCodeOrigin' => $zipOrigin,
            'postalCodeDestination' => $zipDest,
            'deliveredType' => 'D',
            'dimensions' => $dims,
        ]);

        [$okS, $ratesS] = $client->rates([
            'postalCodeOrigin' => $zipOrigin,
            'postalCodeDestination' => $zipDest,
            'deliveredType' => 'S',
            'dimensions' => $dims,
        ]);

        if (!$okD && !$okS) {
            return [false, 'No se pudo cotizar el envÃ­o. RevisÃ¡ Ajustes â†’ EnvÃ­os y la conexiÃ³n con Correo Argentino.', []];
        }

        $options = array_merge(
            $this->normalizeRates($ratesD, 'D'),
            $this->normalizeRates($ratesS, 'S')
        );

        if ($localPickupEnabled) {
            $options[] = [
                'mode' => 'L',
                'service_code' => 'local_pickup',
                'option_key' => substr(sha1('L|local_pickup|' . $localPickupCost . '|' . $localPickupLabel . '|' . $localPickupAddress . '|' . $localPickupNotes), 0, 20),
                'service_name' => ($localPickupLabel !== '' ? $localPickupLabel : 'Retirar en el local'),
                'cost' => $localPickupCost,
                'meta' => [
                    'local_pickup' => true,
                    'local_address' => $localPickupAddress,
                    'local_notes' => $localPickupNotes,
                    'local_maps_url' => $localPickupMapsUrl,
                    'local_place_id' => $localPickupPlaceId,
                    'local_map_lat' => $localPickupMapLat,
                    'local_map_lng' => $localPickupMapLng,
                    'local_map_zoom' => $localPickupMapZoom,
                ],
            ];
        }

        if (empty($options)) {
            return [false, 'No se encontraron opciones de envÃ­o para este destino.', []];
        }

        return [true, 'OK', [
            'subtotal' => $subtotal,
            'free' => false,
            'free_min_amount' => $freeMin,
            'options' => $options,
            'warnings' => $warnings,
            'agencies' => $agencies,
            'recommendedAgency' => $recommendedAgency,
            'address_city' => $cityDest,
            'address_zip' => $zipDest,
            'package' => $dims,
            'package_missing_items' => $missingCount,
            'cart_sig' => $cartSig,
            'address_id' => $addressId,
            'address_sig' => $addressSig,
        ]];
    }

    /** @param mixed $rates */
    private function normalizeRates($rates, string $mode): array
    {
        $out = [];

        // Soportamos mÃºltiples shapes de respuesta sin romper.
        // Caso A: { rates: [ ... ] }
        if (is_array($rates) && isset($rates['rates']) && is_array($rates['rates'])) {
            foreach ($rates['rates'] as $r) {
                if (!is_array($r)) continue;
                $out[] = $this->rateRow($r, $mode);
            }
            return array_values(array_filter($out));
        }

        // Caso B: array directo
        if (is_array($rates) && array_is_list($rates)) {
            foreach ($rates as $r) {
                if (!is_array($r)) continue;
                $out[] = $this->rateRow($r, $mode);
            }
            return array_values(array_filter($out));
        }

        // Caso C: single object
        if (is_array($rates)) {
            $row = $this->rateRow($rates, $mode);
            if ($row) return [$row];
        }

        return [];
    }

    /** @param array<string,mixed> $r */
    private function rateRow(array $r, string $mode): ?array
    {
        $cost = null;

        foreach (['price','amount','cost','total'] as $k) {
            if (isset($r[$k]) && is_numeric($r[$k])) { $cost = (int)$r[$k]; break; }
        }

        // Algunos responses traen nested: { service: { price: ... } }
        if ($cost === null && isset($r['service']) && is_array($r['service'])) {
            foreach (['price','amount','cost','total'] as $k) {
                if (isset($r['service'][$k]) && is_numeric($r['service'][$k])) { $cost = (int)$r['service'][$k]; break; }
            }
        }

        if ($cost === null) return null;
        if ($cost < 0) $cost = 0;

        $code = (string)($r['code'] ?? $r['serviceCode'] ?? $r['service_code'] ?? '');
        $name = (string)($r['name'] ?? $r['serviceName'] ?? $r['service_name'] ?? '');

        $productName = (string)($r['productName'] ?? $r['product_name'] ?? '');
        $productType = (string)($r['productType'] ?? $r['product_type'] ?? '');
        $dtMin = isset($r['deliveryTimeMin']) ? (int)$r['deliveryTimeMin'] : (isset($r['delivery_time_min']) ? (int)$r['delivery_time_min'] : null);
        $dtMax = isset($r['deliveryTimeMax']) ? (int)$r['deliveryTimeMax'] : (isset($r['delivery_time_max']) ? (int)$r['delivery_time_max'] : null);

        $code = substr(trim($code), 0, 40);
        $name = substr(trim($name), 0, 80);

        if ($name === '') {
            $name = ($mode === 'D') ? 'EnvÃ­o a domicilio' : 'Retiro en sucursal';
        }

        // Si la API trae productName (ej: "Correo Argentino Clasico/Expreso"), preferimos eso para UI.
        $genericD = ($name === 'EnvÃ­o a domicilio');
        $genericS = ($name === 'Retiro en sucursal');
        if (($genericD || $genericS) && trim($productName) !== '') {
            $name = trim($productName);
        }

        $serviceCode = ($code !== '' ? $code : strtolower($mode).'_std');
        $rateKeySeed = implode('|', [
            $mode,
            $serviceCode,
            $cost,
            trim((string)$productName),
            trim((string)$productType),
            ($dtMin !== null ? (string)$dtMin : ''),
            ($dtMax !== null ? (string)$dtMax : ''),
        ]);
        $optionKey = substr(sha1($rateKeySeed), 0, 20);

        return [
            'mode' => $mode,
            'service_code' => $serviceCode,
            'option_key' => $optionKey,
            'service_name' => $name,
            'cost' => $cost,
            'meta' => [
                'product_name' => ($productName !== '' ? $productName : null),
                'product_type' => ($productType !== '' ? $productType : null),
                'delivery_time_min' => ($dtMin !== null ? (int)$dtMin : null),
                'delivery_time_max' => ($dtMax !== null ? (int)$dtMax : null),
            ],
        ];
    }
    /**
     * Intenta recomendar una agencia (sucursal) en base a la localidad (city) de la direcciÃ³n.
     * - NO inventa "cercanas": solo matchea por texto.
     * - Devuelve null si no hay match (para que el select arranque vacÃ­o).
     */
    private function recommendAgencyByCity(array $agencies, ?string $city): ?array
    {
        $city = trim((string)($city ?? ''));
        if ($city === '') {
            return null;
        }

        $cityNorm = $this->normalizeText($city);
        if ($cityNorm === '') return null;

        foreach ($agencies as $ag) {
            if (!is_array($ag)) continue;

            $haystackParts = [];

            foreach (['agencyName','name','description','descripcion','nombre','city','locality','localidad','address','direccion'] as $k) {
                if (isset($ag[$k]) && is_string($ag[$k]) && trim($ag[$k]) !== '') {
                    $haystackParts[] = $ag[$k];
                }
            }

            // fallback: concatenar todos los valores string
            if (empty($haystackParts)) {
                foreach ($ag as $v) {
                    if (is_string($v) && trim($v) !== '') $haystackParts[] = $v;
                }
            }

            $haystack = $this->normalizeText(implode(' ', $haystackParts));
            if ($haystack !== '' && str_contains($haystack, $cityNorm)) {
                return $ag;
            }
        }

        return null;
    }

    private function normalizeText(string $s): string
    {
        $s = trim($s);
        if ($s === '') return '';
        $s = mb_strtolower($s, 'UTF-8');

        // quitar tildes / diacrÃ­ticos comunes
        $repl = [
            'Ã¡'=>'a','Ã '=>'a','Ã¤'=>'a','Ã¢'=>'a',
            'Ã©'=>'e','Ã¨'=>'e','Ã«'=>'e','Ãª'=>'e',
            'Ã­'=>'i','Ã¬'=>'i','Ã¯'=>'i','Ã®'=>'i',
            'Ã³'=>'o','Ã²'=>'o','Ã¶'=>'o','Ã´'=>'o',
            'Ãº'=>'u','Ã¹'=>'u','Ã¼'=>'u','Ã»'=>'u',
            'Ã±'=>'n',
        ];
        $s = strtr($s, $repl);

        // compactar espacios
        $s = preg_replace('/\s+/', ' ', $s);
        return $s ?? '';
    }

}
