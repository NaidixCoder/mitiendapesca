<?php

declare(strict_types=1);

namespace App\Services\Fiscal;

use App\Services\Billing\Arca\PadronValidationService;
use App\Repositories\Orders\OrderFiscalRequestRepository;
use App\Repositories\Users\UserFiscalProfileRepository;
use App\Support\Security\CheckoutFiscal;
use PDO;

final class FiscalRequestService
{
    private UserFiscalProfileRepository $profileRepo;
    private OrderFiscalRequestRepository $reqRepo;
    private PDO $db;

    public function __construct(UserFiscalProfileRepository $profileRepo, OrderFiscalRequestRepository $reqRepo, PDO $db)
    {
        $this->profileRepo = $profileRepo;
        $this->reqRepo = $reqRepo;
        $this->db = $db;
    }

    /**
     * @return array{ok:bool,status:string,message?:string}
     */
    public function requestFacturaA(int $userId, int $orderId, string $taxConditionRaw, string $cuitRaw): array
    {
        $taxCond = CheckoutFiscal::normalizeTaxCondition($taxConditionRaw);
        $digits = preg_replace('/\D+/', '', $cuitRaw);
        $digits = is_string($digits) ? $digits : '';

        if ($taxCond !== 'responsable_inscripto') {
            return ['ok' => false, 'status' => 'invalid', 'message' => 'Para solicitar Factura A necesitás seleccionar “Responsable inscripto”.'];
        }

        if ($digits === '' || strlen($digits) !== 11) {
            return ['ok' => false, 'status' => 'invalid', 'message' => 'Ingresá tu CUIT (11 dígitos).'];
        }

        if (!CheckoutFiscal::isValidCuit($digits)) {
            return ['ok' => false, 'status' => 'invalid', 'message' => 'CUIT inválido. Revisá los 11 dígitos.'];
        }

        // Guardamos perfil fiscal (sin validar padrón todavía)
        $this->profileRepo->upsert($userId, [
            'tax_condition' => $taxCond,
            'cuit' => $digits,
            'legal_name' => '',
            'validation_status' => 'pending',
            'validation_error' => '',
            'validated_at' => null,
        ]);

        // Guardamos solicitud por pedido
        $this->reqRepo->upsertForOrder($orderId, [
            'requested_type' => 'A',
            'status' => 'pending',
            'customer_tax_condition' => $taxCond,
            'customer_cuit' => $digits,
            'customer_legal_name' => '',
            'notes' => null,
        ]);

        return ['ok' => true, 'status' => 'pending'];
    }

    /**
     * Solicitud de Factura A + validación contra Padrón (Etapa 2).
     * @return array{ok:bool,status:string,message:string,legal_name:string}
     */
    public function requestFacturaAWithPadron(int $userId, int $orderId, string $taxCondition, string $cuit): array
    {
        $taxCondition = CheckoutFiscal::normalizeTaxCondition($taxCondition);
        $digits = preg_replace('/\D+/', '', (string)$cuit) ?? '';
        $digits = is_string($digits) ? $digits : '';

        // Reglas de validación local (NO persisten, el usuario debe corregir y reintentar)
        if ($taxCondition !== 'responsable_inscripto') {
            return [
                'ok' => false,
                'status' => 'invalid',
                'message' => 'Para solicitar Factura A la condición debe ser “Responsable inscripto”.',
            ];
        }

        if ($digits === '' || strlen($digits) !== 11 || !CheckoutFiscal::isValidCuit($digits)) {
            return [
                'ok' => false,
                'status' => 'invalid',
                'message' => 'CUIT inválido (dígito verificador).',
            ];
        }

        // Si el CUIT es el mismo que el emisor, es inválido (no se registra solicitud)
        try {
            $sellerCuit = (string)env('ARCA_CUIT', '');
            $sellerDigits = (string)preg_replace('/\D+/', '', $sellerCuit);
            if ($sellerDigits !== '' && $sellerDigits === $digits) {
                return [
                    'ok' => false,
                    'status' => 'invalid',
                    'message' => 'El CUIT ingresado corresponde al emisor (vendedor). Usá el CUIT del comprador.',
                ];
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // Intentamos validar contra Padrón. Si no se puede validar (servicio caído/no autorizado),
        // registramos la solicitud como pendiente para operativa (sin fricción para el comprador).
        $padron = new PadronValidationService($this->db);
        $padronRes = $padron->validate($digits);

        if (($padronRes['status'] ?? '') === 'valid') {
            // OK: registramos solicitud + perfil fiscal como "valid"
            $this->profileRepo->upsert($userId, [
                'tax_condition' => $taxCondition,
                'cuit' => $digits,
                'legal_name' => (string)($padronRes['legal_name'] ?? ''),
                'validation_status' => 'valid',
                'validation_error' => '',
            ]);

            $this->reqRepo->upsertForOrder($orderId, [
                'requested_type' => 'A',
                'status' => 'pending',
                'customer_tax_condition' => $taxCondition,
                'customer_cuit' => $digits,
                'customer_legal_name' => (string)($padronRes['legal_name'] ?? ''),
                'notes' => '',
            ]);

            return ['ok' => true, 'status' => 'pending', 'message' => 'Solicitud registrada. Vamos a validar y te avisaremos.'];
        }

        if (($padronRes['status'] ?? '') === 'invalid') {
            // Padrón respondió y NO corresponde (no RI, etc). El usuario debe corregir (NO persistimos).
            return [
                'ok' => false,
                'status' => 'invalid',
                'message' => (string)($padronRes['message'] ?? 'El CUIT no es válido para Factura A o no corresponde a Responsable Inscripto.'),
            ];
        }

        // Pending: no se pudo validar (servicio no disponible / no autorizado). Registramos como pendiente.
        $this->profileRepo->upsert($userId, [
            'tax_condition' => $taxCondition,
            'cuit' => $digits,
            'legal_name' => '',
            'validation_status' => 'pending',
            'validation_error' => (string)($padronRes['message'] ?? 'Validación pendiente (Padrón no disponible).'),
        ]);

        $this->reqRepo->upsertForOrder($orderId, [
            'requested_type' => 'A',
            'status' => 'pending',
            'customer_tax_condition' => $taxCondition,
            'customer_cuit' => $digits,
            'customer_legal_name' => '',
            'notes' => (string)($padronRes['message'] ?? ''),
        ]);

        return ['ok' => true, 'status' => 'pending', 'message' => 'Solicitud registrada. Validación pendiente.'];
    }

}

