<?php
namespace App\Controllers\Checkout;

use App\Services\Shipping\CheckoutShippingService;
use App\Services\Cart\CartReadService;
use App\Services\Cart\CartSelectionService;
use App\Services\Cart\CartSignatureService;
use App\Services\Audit\Audit;
use App\Repositories\Account\AddressRepository;

final class ShippingController
{
    public function calculate(): void
    {
        auth_required('/checkout');
        verify_csrf_or_abort();

        $uid = (int)($_SESSION['uid'] ?? 0);
        if ($uid <= 0) { json(['ok'=>false,'error'=>'unauthorized'], 401); }

        $raw = file_get_contents('php://input');
        $body = [];
        if (is_string($raw) && trim($raw) !== '') {
            $tmp = json_decode($raw, true);
            if (is_array($tmp)) $body = $tmp;
        }

        $addressId = (int)($body['address_id'] ?? ($_POST['address_id'] ?? 0));
        if ($addressId <= 0) {
            json(['ok'=>false,'error'=>'Seleccioná una dirección para calcular el envío.'], 422);
        }

        $svc = new CheckoutShippingService(db());
        [$ok, $msg, $data] = $svc->calculateOptions($uid, $addressId);

        if (!$ok) {
            json(['ok'=>false,'error'=>(string)$msg], 422);
        }

        $addressSummary = [];
        try {
            $address = (new AddressRepository(db()))->findForUser($addressId, $uid);
            if (is_array($address) && !empty($address)) {
                $line1 = trim(implode(' ', array_filter([
                    trim((string)($address['street'] ?? '')),
                    trim((string)($address['number'] ?? '')),
                ])));
                $line2 = trim(implode(' - ', array_filter([
                    trim((string)($address['city'] ?? '')),
                    trim((string)($address['state'] ?? '')),
                    (($zip = trim((string)($address['zip'] ?? ''))) !== '' ? 'CP ' . $zip : ''),
                ])));
                $addressSummary = [
                    'label' => trim((string)($address['label'] ?? '')),
                    'line1' => $line1,
                    'line2' => $line2,
                ];
            }
        } catch (\Throwable $e) {}

        // Persistimos un snapshot server-side (válido por TTL) para que el cliente NO pueda
        // modificar costo/servicio/paquete (seguridad-first).
        $calcId = bin2hex(random_bytes(16));
        $now = time();
        $ttl = 3600; // 1 hora

        $options = is_array($data['options'] ?? null) ? $data['options'] : [];
        $optionsMap = [];
        foreach ($options as $opt) {
            if (!is_array($opt)) continue;
            $mode = strtoupper(trim((string)($opt['mode'] ?? 'D')));
            if (!in_array($mode, ['D','S'], true)) $mode = 'D';
            $code = substr(trim((string)($opt['service_code'] ?? '')), 0, 40);
            if ($code === '') continue;

            $optionId = substr(trim((string)($opt['option_key'] ?? '')), 0, 64);
            if ($optionId === '') {
                $optionId = $mode.'|'.$code;
            }
            $name = substr(trim((string)($opt['service_name'] ?? '')), 0, 80);
            $cost = (int)($opt['cost'] ?? 0);
            if ($cost < 0) $cost = 0;

            $optionsMap[$optionId] = [
                'mode' => $mode,
                'service_code' => $code,
                'service_name' => $name,
                'cost' => $cost,
                'requires_agency' => ($mode === 'S'),
            ];
        }

        $agencies = is_array($data['agencies'] ?? null) ? $data['agencies'] : [];
        $agenciesMap = [];
		$pickAgencyId = function(array $a): string {
			// La API de Correo no es consistente con el nombre del campo ID.
			$candidates = [
				'agency_id', 'agencyId', 'AgencyId', 'AGENCY_ID',
				'id', 'Id', 'ID',
				'agencyCode', 'AgencyCode', 'code', 'Code',
				'sucursal_id', 'sucursalId', 'SucursalId', 'SUCURSAL_ID',
				'codigo', 'Codigo', 'CODIGO',
			];
			foreach ($candidates as $k) {
				if (!array_key_exists($k, $a)) continue;
				$v = trim((string)$a[$k]);
				if ($v !== '') return substr($v, 0, 40);
			}
			// Fallback: primer key que contenga "id".
			foreach ($a as $k => $v) {
				if (!is_scalar($v)) continue;
				if (stripos((string)$k, 'id') === false) continue;
				$vv = trim((string)$v);
				if ($vv !== '') return substr($vv, 0, 40);
			}
			return '';
		};

		$pickAgencyAddress = function(array $a): string {
				// MiCorreo: location.address.{streetName,streetNumber,locality,city,province,postalCode}
				if (isset($a['location']) && is_array($a['location']) && isset($a['location']['address']) && is_array($a['location']['address'])) {
					$addr = $a['location']['address'];
					$street = trim((string)($addr['streetName'] ?? ''));
					$number = trim((string)($addr['streetNumber'] ?? ''));
					$locality = trim((string)($addr['locality'] ?? ''));
					$city = trim((string)($addr['city'] ?? ''));
					$province = trim((string)($addr['province'] ?? ''));
					$postalCode = trim((string)($addr['postalCode'] ?? ''));

					$line1 = trim($street . ($number !== '' ? ' ' . $number : ''));
					$line2 = trim(($locality !== '' ? $locality : $city) . ($province !== '' ? ' · ' . $province : ''));
					$line = trim($line1 . ($line2 !== '' ? ' · ' . $line2 : ''));
					if ($postalCode !== '') {
						$line .= ' (' . $postalCode . ')';
					}
					return trim($line);
				}

			// La API puede devolver domicilio como string o como estructura (calle/nro/localidad/provincia/cp).
			$candidateKeys = [
				'address','Address','direccion','Dirección','direccion_sucursal','domicilio','Domicilio',
				'agencyAddress','agency_address','AgencyAddress',
				'street','Street','calle','Calle','dir','Dir','direccion1','direccion_1','address_line','addressLine',
			];
			foreach ($candidateKeys as $k) {
				if (!array_key_exists($k, $a)) continue;
				$v = $a[$k];
				if (is_string($v) || is_numeric($v)) {
					$s = trim((string)$v);
					if ($s !== '') return $s;
				}
				if (is_array($v)) {
					$parts = [];
					foreach (['street','calle','direccion','address','line1','address_line1'] as $kk) {
						if (!empty($v[$kk]) && is_scalar($v[$kk])) $parts[] = trim((string)$v[$kk]);
					}
					foreach (['number','nro','numero','num'] as $kk) {
						if (!empty($v[$kk]) && is_scalar($v[$kk])) $parts[] = trim((string)$v[$kk]);
					}
					foreach (['locality','localidad','city'] as $kk) {
						if (!empty($v[$kk]) && is_scalar($v[$kk])) $parts[] = trim((string)$v[$kk]);
					}
					foreach (['province','provincia','state'] as $kk) {
						if (!empty($v[$kk]) && is_scalar($v[$kk])) $parts[] = trim((string)$v[$kk]);
					}
					foreach (['zip','postal_code','cp','codigo_postal'] as $kk) {
						if (!empty($v[$kk]) && is_scalar($v[$kk])) $parts[] = trim((string)$v[$kk]);
					}
					$joined = trim(implode(' ', array_filter($parts)));
					if ($joined !== '') return $joined;
				}
			}
			// Fallback: combinación de campos sueltos.
			$street = trim((string)($a['street'] ?? $a['calle'] ?? $a['direccion'] ?? $a['AddressLine'] ?? ''));
			$num = trim((string)($a['number'] ?? $a['nro'] ?? $a['numero'] ?? ''));
			$loc = trim((string)($a['locality'] ?? $a['localidad'] ?? $a['city'] ?? ''));
			$prov = trim((string)($a['province'] ?? $a['provincia'] ?? $a['state'] ?? ''));
			$cp = trim((string)($a['zip'] ?? $a['postal_code'] ?? $a['cp'] ?? $a['codigo_postal'] ?? ''));
			$joined = trim(implode(' ', array_filter([$street, $num, $loc, $prov, $cp])));
			return $joined;
		};
		$pickAgencyLocality = function(array $a): string {
			$loc = trim((string)($a['locality'] ?? $a['localidad'] ?? $a['city'] ?? ''));
			$an = trim((string)($a['name'] ?? $a['agencyName'] ?? $a['agency_name'] ?? $a['nombre'] ?? ''));
			// Si la API ya trae una localidad "limpia", la usamos.
			if ($loc !== '') {
				// Algunos endpoints devuelven "FIRMAT GENERAL LOPEZ SANTA FE" (localidad + dpto + prov). Nos quedamos con el "ciudad".
				// Regla: si el nombre de agencia es sólo la ciudad (o empieza por la ciudad) la usamos como localidad.
				$cityFromName = trim(preg_split('/\s*[\-—]\s*/u', $an)[0] ?? '');
				if ($cityFromName !== '' && stripos($loc, $cityFromName) === 0) {
					return $cityFromName;
				}
				return $loc;
			}
			// Intento por address anidado
			foreach (['address', 'domicilio', 'direccion', 'Address', 'addressData', 'address_data'] as $k) {
				if (!isset($a[$k]) || !is_array($a[$k])) continue;
				$loc2 = trim((string)($a[$k]['locality'] ?? $a[$k]['localidad'] ?? $a[$k]['city'] ?? $a[$k]['town'] ?? ''));
				if ($loc2 !== '') {
					$cityFromName = trim(preg_split('/\s*[\-—]\s*/u', $an)[0] ?? '');
					if ($cityFromName !== '' && stripos($loc2, $cityFromName) === 0) {
						return $cityFromName;
					}
					return $loc2;
				}
			}
			// Último fallback: derivamos de "name" (ej: "FIRMAT - CENTRO DE ENCOMIENDAS" => "FIRMAT")
			$cityFromName = trim(preg_split('/\s*[\-—]\s*/u', $an)[0] ?? '');
			return $cityFromName;
		};

		// Normalizamos agencias para la vista (evita que el partial tenga que adivinar estructura de API)
		$agenciesNormalized = [];
        foreach ($agencies as $a) {
            if (!is_array($a)) continue;
			$aid = $pickAgencyId($a);
            if ($aid === '') continue;
			$an = substr(trim((string)($a['name'] ?? $a['agencyName'] ?? $a['agency_name'] ?? 'Sucursal')), 0, 120);
			$addr = substr(trim((string)$pickAgencyAddress($a)), 0, 160);
			$loc = substr(trim((string)$pickAgencyLocality($a)), 0, 80);

            $agenciesMap[$aid] = ['name' => $an, 'address' => $addr];
			$agenciesNormalized[] = [
				'id' => $aid,
				'name' => $an,
				'locality' => $loc,
				'address' => $addr,
			];
        }

		// Sobrescribimos agencias para el partial con formato estable
		$data['agencies'] = $agenciesNormalized;

        // Firma de carrito / dirección (source of truth)
        $cartSig = (string)($data['cart_sig'] ?? '');
        if ($cartSig === '') {
            // Backstop: calculamos la firma acá si el service no la trajo.
            $selectionSvc = new CartSelectionService();
            $cart = $selectionSvc->apply((new CartReadService(db()))->getCartView());
            $cartSig = CartSignatureService::build((array)($cart['items'] ?? []));
        }

        $_SESSION['checkout_shipping_calc'] = [
            'calculation_id' => $calcId,
            'user_id' => $uid,
            'created_at' => $now,
            'expires_at' => $now + $ttl,
            'address_id' => (int)($data['address_id'] ?? $addressId),
            'address_sig' => (string)($data['address_sig'] ?? ''),
            'cart_sig' => $cartSig,
            'package' => is_array($data['package'] ?? null) ? $data['package'] : null,
            'options' => $optionsMap,
            'agencies' => $agenciesMap,
        ];

        // Persistencia UX: si ya había una selección guardada y sigue siendo válida
        // para este carrito + dirección, NO la invalidamos al recalcular (reload/back/forward).
        $selectedOptionId = '';
        $selectedAgencyId = '';
        $selectedShippingMethod = '';
        if (isset($_SESSION['checkout_shipping']) && is_array($_SESSION['checkout_shipping'])) {
            $snap = (array)$_SESSION['checkout_shipping'];
            $snapCartSig = (string)($snap['cart_sig'] ?? '');
            $snapAddrId  = (int)($snap['address_id'] ?? 0);

            // Regla: si el carrito cambió o la dirección cambió, se invalida la selección.
            $sameCart = ($snapCartSig !== '' && $snapCartSig === $cartSig);
            $sameAddr = ($snapAddrId > 0) ? ($snapAddrId === (int)($data['address_id'] ?? $addressId)) : true;

            if ($sameCart && $sameAddr) {
                $savedOptionKey = substr(trim((string)($snap['option_key'] ?? '')), 0, 64);
                $mode = strtoupper(trim((string)($snap['mode'] ?? '')));
                $service = trim((string)($snap['service_code'] ?? ''));
                if ($savedOptionKey !== '' && array_key_exists($savedOptionKey, $optionsMap)) {
                    $selectedOptionId = $savedOptionKey;
                    $optSaved = (array)($optionsMap[$savedOptionKey] ?? []);
                    $selectedShippingMethod = strtoupper((string)($optSaved['mode'] ?? 'D')).'|'.(string)($optSaved['service_code'] ?? '');
                    if (!empty($snap['agency_id'])) {
                        $selectedAgencyId = substr(trim((string)$snap['agency_id']), 0, 40);
                    }
                } elseif (in_array($mode, ['D','S'], true) && $service !== '') {
                    $selectedOptionId = $mode.'|'.$service;
                    $selectedShippingMethod = $selectedOptionId;
                    if (!empty($snap['agency_id'])) {
                        $selectedAgencyId = substr(trim((string)$snap['agency_id']), 0, 40);
                    }
                    // Si la opción ya no está en el cálculo actual, invalidamos.
                    if (!array_key_exists($selectedOptionId, $optionsMap)) {
                        unset($_SESSION['checkout_shipping']);
                        unset($_SESSION['checkout_shipping_calc']);
                        $selectedOptionId = '';
                        $selectedAgencyId = '';
                    }
                } else {
                    // free/otros: el paso 2 igual se recalcula, pero no intentamos preseleccionar.
                }
            } else {
                unset($_SESSION['checkout_shipping']);
                unset($_SESSION['checkout_shipping_calc']);
            }
        }

        // Render server-side para evitar armar HTML en JS
        ob_start();
        // Pasamos selección previa (si existe) para que el HTML salga pre-marcado.
        $data['selected_option_id'] = $selectedOptionId;
        $data['selected_agency_id'] = $selectedAgencyId;
        $data['address_summary'] = $addressSummary;
        $data['pickup'] = $this->buildPickupViewData(
            is_array($data['agencies'] ?? null) ? $data['agencies'] : [],
            is_array($data['recommendedAgency'] ?? null) ? $data['recommendedAgency'] : null,
            (string)($data['address_city'] ?? ''),
            $selectedOptionId,
            $selectedAgencyId
        );

        section('/partials/checkout/shipping_options', $data);
        $html = (string)ob_get_clean();

        json([
            'ok' => true,
            'calculation_id' => $calcId,
            'html' => $html,
            'package' => $data['package'] ?? null,
            'package_missing_items' => (int)($data['package_missing_items'] ?? 0),
            'cart_sig' => (string)$cartSig,
            'selected_option_id' => $selectedOptionId,
            'selected_agency_id' => $selectedAgencyId,
            'shipping_rate_key' => $selectedOptionId,
            'shipping_method' => $selectedShippingMethod,
            'shipping_agency_id' => $selectedAgencyId,
            'data' => [
                'free' => (bool)($data['free'] ?? false),
                'free_min_amount' => (int)($data['free_min_amount'] ?? 0),
                'subtotal' => (int)($data['subtotal'] ?? 0),
            ],
        ]);
    }

    public function select(): void
    {
        auth_required('/checkout');
        verify_csrf_or_abort();

        $uid = (int)($_SESSION['uid'] ?? 0);
        if ($uid <= 0) { json(['ok'=>false,'error'=>'unauthorized'], 401); }

        $raw = file_get_contents('php://input');
        $body = [];
        if (is_string($raw) && trim($raw) !== '') {
            $tmp = json_decode($raw, true);
            if (is_array($tmp)) $body = $tmp;
        }

        $calculationId = substr(trim((string)($body['calculation_id'] ?? '')), 0, 64);
        $optionId = substr(trim((string)($body['option_id'] ?? '')), 0, 80);
        $agencyId = substr(trim((string)($body['agency_id'] ?? '')), 0, 40);

        if ($calculationId === '' || $optionId === '') {
            json(['ok'=>false,'error'=>'Selección inválida.'], 422);
        }

        $calc = is_array($_SESSION['checkout_shipping_calc'] ?? null) ? $_SESSION['checkout_shipping_calc'] : null;
        if (!$calc || ($calc['calculation_id'] ?? '') !== $calculationId) {
            Audit::log('checkout_shipping_select_invalid', 'checkout', null, [
                'reason' => 'calc_invalid_or_mismatch',
                'calc_id' => substr($calculationId, -8),
                'option_id' => $optionId,
                'agency_id' => $agencyId,
            ]);
            json(['ok'=>false,'error'=>'La cotización expiró o es inválida. Volvé a calcular el envío.'], 409);
        }

        if ((int)($calc['user_id'] ?? 0) !== $uid) {
            Audit::log('checkout_shipping_select_invalid', 'checkout', null, [
                'reason' => 'user_mismatch',
                'calc_id' => substr($calculationId, -8),
            ]);
            json(['ok'=>false,'error'=>'Cotización inválida.'], 403);
        }

        $now = time();
        if ($now > (int)($calc['expires_at'] ?? 0)) {
            unset($_SESSION['checkout_shipping_calc']);
            unset($_SESSION['checkout_shipping']);
            Audit::log('checkout_shipping_select_expired', 'checkout', null, [
                'reason' => 'calc_expired',
                'calc_id' => substr($calculationId, -8),
            ]);
            json(['ok'=>false,'error'=>'La cotización expiró. Volvé a calcular el envío.'], 409);
        }

        // Validación cart_sig: si cambió el carrito, invalidamos.
        $selectionSvc = new CartSelectionService();
        $cart = $selectionSvc->apply((new CartReadService(db()))->getCartView());
        $currentSig = CartSignatureService::build((array)($cart['items'] ?? []));
        if ($currentSig !== (string)($calc['cart_sig'] ?? '')) {
            unset($_SESSION['checkout_shipping_calc']);
            unset($_SESSION['checkout_shipping']);
            Audit::log('checkout_shipping_select_mismatch', 'checkout', null, [
                'reason' => 'cart_sig_mismatch',
                'calc_id' => substr($calculationId, -8),
                'cart_sig_old' => substr((string)($calc['cart_sig'] ?? ''), -8),
                'cart_sig_new' => substr($currentSig, -8),
            ]);
            json(['ok'=>false,'error'=>'Tu carrito cambió. Volvé a calcular el envío.'], 409);
        }

        $options = is_array($calc['options'] ?? null) ? $calc['options'] : [];
        $opt = is_array($options[$optionId] ?? null) ? $options[$optionId] : null;
        if (!$opt) {
            Audit::log('checkout_shipping_select_invalid', 'checkout', null, [
                'reason' => 'option_not_in_calc',
                'calc_id' => substr($calculationId, -8),
                'option_id' => $optionId,
            ]);
            json(['ok'=>false,'error'=>'Opción de envío inválida. Volvé a calcular el envío.'], 422);
        }

        $mode = (string)($opt['mode'] ?? 'D');
        $serviceCode = (string)($opt['service_code'] ?? '');
        $serviceName = (string)($opt['service_name'] ?? '');
        $cost = (int)($opt['cost'] ?? 0);
        if ($cost < 0) $cost = 0;

        $agencyName = '';
        $agencyAddress = '';
        if (!empty($opt['requires_agency'])) {
            if ($agencyId === '') {
                Audit::log('checkout_shipping_select_invalid', 'checkout', null, [
                    'reason' => 'agency_missing',
                    'calc_id' => substr($calculationId, -8),
                    'option_id' => $optionId,
                ]);
                json(['ok'=>false,'error'=>'Elegí una sucursal para retiro.'], 422);
            }
            $agMap = is_array($calc['agencies'] ?? null) ? $calc['agencies'] : [];
            $ag = is_array($agMap[$agencyId] ?? null) ? $agMap[$agencyId] : null;
            if (!$ag) {
                Audit::log('checkout_shipping_select_invalid', 'checkout', null, [
                    'reason' => 'agency_invalid',
                    'calc_id' => substr($calculationId, -8),
                    'option_id' => $optionId,
                    'agency_id' => $agencyId,
                ]);
                json(['ok'=>false,'error'=>'Sucursal inválida. Volvé a calcular el envío.'], 422);
            }
            $agencyName = (string)($ag['name'] ?? 'Sucursal');
            $agencyAddress = (string)($ag['address'] ?? '');
        } else {
            $agencyId = '';
        }

        $_SESSION['checkout_shipping'] = [
            'carrier' => ($mode === 'L' ? 'local_pickup' : 'correo_argentino'),
            'mode' => $mode,
            'service_code' => $serviceCode,
            'service_name' => $serviceName,
            'cost' => $cost,
            'option_key' => $optionId,
            'address_id' => (int)($calc['address_id'] ?? 0),
            'agency_id' => $agencyId,
            'agency_name' => $agencyName,
            'agency_address' => $agencyAddress,
            'local_notes' => (string)($opt['meta']['local_notes'] ?? ''),
            'local_address' => (string)($opt['meta']['local_address'] ?? ''),
            'package' => is_array($calc['package'] ?? null) ? $calc['package'] : null,
            'cart_sig' => (string)($calc['cart_sig'] ?? ''),
            'calculation_id' => $calculationId,
            'updated_at' => gmdate('c'),
        ];

        json([
            'ok' => true,
            'shipping_rate_key' => $optionId,
            'shipping_method' => $mode.'|'.$serviceCode,
            'shipping_agency_id' => $agencyId,
            'cost' => $cost,
        ]);
    }
    private function normalizeAgencyLocality(string $value): string
    {
        $value = str_replace(["\xC2\xA0", "\t", "\r", "\n"], ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        $value = trim($value);
        if ($value === '') return '';

        return function_exists('mb_strtoupper')
            ? mb_strtoupper($value, 'UTF-8')
            : strtoupper($value);
    }

    private function resolveRecommendedAgencyId(array $agencies, ?array $recommended): string
    {
        if (!$recommended) return '';

        $recId = trim((string)($recommended['id'] ?? $recommended['agencyId'] ?? $recommended['agency_id'] ?? ''));
        if ($recId !== '') return $recId;

        $recName = trim((string)($recommended['name'] ?? $recommended['agencyName'] ?? $recommended['agency_name'] ?? ''));
        if ($recName === '') return '';

        $normalize = static function (string $value): string {
            $value = trim($value);
            return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
        };

        $target = $normalize($recName);
        foreach ($agencies as $agency) {
            if (!is_array($agency)) continue;
            $agencyId = trim((string)($agency['id'] ?? ''));
            $agencyName = trim((string)($agency['name'] ?? ''));
            if ($agencyId === '' || $agencyName === '') continue;

            $current = $normalize($agencyName);
            if ($current === $target || str_contains($current, $target) || str_contains($target, $current)) {
                return $agencyId;
            }
        }

        return '';
    }

    private function buildPickupViewData(array $agencies, ?array $recommended, string $addressCity, string $selectedOptionId, string $selectedAgencyId): array
    {
        $currentSelectedAgencyId = str_starts_with($selectedOptionId, 'S|') ? trim($selectedAgencyId) : '';
        $recommendedId = $this->resolveRecommendedAgencyId($agencies, $recommended);

        $localitiesMap = [];
        foreach ($agencies as $agency) {
            if (!is_array($agency)) continue;
            $loc = $this->normalizeAgencyLocality((string)($agency['locality'] ?? ''));
            if ($loc === '') continue;
            $localitiesMap[$loc] = true;
        }

        $localities = array_keys($localitiesMap);
        sort($localities, SORT_NATURAL | SORT_FLAG_CASE);

        $selectedLocality = '';
        if ($currentSelectedAgencyId !== '') {
            foreach ($agencies as $agency) {
                if (!is_array($agency)) continue;
                if (trim((string)($agency['id'] ?? '')) !== $currentSelectedAgencyId) continue;
                $candidate = $this->normalizeAgencyLocality((string)($agency['locality'] ?? ''));
                if ($candidate !== '' && in_array($candidate, $localities, true)) {
                    $selectedLocality = $candidate;
                }
                break;
            }
        }

        if ($selectedLocality === '' && trim($addressCity) !== '') {
            $candidate = $this->normalizeAgencyLocality($addressCity);
            if (in_array($candidate, $localities, true)) {
                $selectedLocality = $candidate;
            }
        }

        $agencyOptions = [];
        foreach ($agencies as $agency) {
            if (!is_array($agency)) continue;
            $agencyId = trim((string)($agency['id'] ?? ''));
            if ($agencyId === '') continue;
            $agencyOptions[] = [
                'id' => $agencyId,
                'name' => (string)($agency['name'] ?? 'Sucursal'),
                'address' => (string)($agency['address'] ?? ''),
                'locality' => $this->normalizeAgencyLocality((string)($agency['locality'] ?? '')),
                'selected' => ($currentSelectedAgencyId !== '' && $agencyId === $currentSelectedAgencyId)
                    || ($currentSelectedAgencyId === '' && $recommendedId !== '' && $agencyId === $recommendedId),
            ];
        }

        $message = 'Elegi una sucursal para retiro.';
        if ($recommended) {
            $recName = trim((string)($recommended['name'] ?? $recommended['agencyName'] ?? $recommended['agency_name'] ?? 'Sucursal'));
            $message = 'Sucursal sugerida: ' . $recName;
        } elseif (trim($addressCity) !== '') {
            $message = 'No encontramos sucursales en tu localidad (' . trim($addressCity) . '). Elegi una cercana de la lista.';
        }

        return [
            'message' => $message,
            'localities' => array_map(
                fn(string $loc): array => ['value' => $loc, 'label' => $loc, 'selected' => ($selectedLocality === $loc)],
                $localities
            ),
            'agencies' => $agencyOptions,
        ];
    }
}



