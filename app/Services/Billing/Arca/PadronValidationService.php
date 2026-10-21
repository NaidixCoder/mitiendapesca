<?php

declare(strict_types=1);

namespace App\Services\Billing\Arca;

use App\Support\Security\CheckoutFiscal;
use PDO;

/**
 * Valida CUIT contra WS SR Padrón A5.
 *
 * Regla MVP:
 * - Si el servicio falla (SOAP/WSAA/etc.), no bloquea el checkout: status=pending.
 */
final class PadronValidationService
{
    public function __construct(private PDO $db) {}

    /**
     * @return array{status:string, legal_name:string, error:string}
     */
    /**
     * @param array<string,mixed> $opts Overrides opcionales (p.ej. timeouts)
     */
    public function validate(string $cuit, array $opts = []): array
    {
        $digits = preg_replace('/\D+/', '', (string)$cuit) ?? '';
        $digits = is_string($digits) ? $digits : '';

        if ($digits === '' || strlen($digits) !== 11 || !CheckoutFiscal::isValidCuit($digits)) {
            return [
                'status' => 'invalid',
                'legal_name' => '',
                'error' => 'CUIT inválido (dígito verificador).',
            ];
        }

        try {
            $cfgBase = (new ArcaConfig($this->db))->get();

            // Regla de negocio: el CUIT del comprador no puede ser el mismo que el CUIT del vendedor.
            $sellerDigits = preg_replace('/\D+/', '', (string)($cfgBase['cuit'] ?? '')) ?? '';
            $sellerDigits = is_string($sellerDigits) ? $sellerDigits : '';
            if ($sellerDigits !== '' && $digits === $sellerDigits) {
                return [
                    'status' => 'invalid',
                    'legal_name' => '',
                    'error' => 'El CUIT ingresado corresponde al emisor (vendedor). Usá el CUIT del comprador.',
                ];
            }

            // Overrides opcionales (usado en Admin para dar más tiempo)
            foreach (['padron_connection_timeout', 'padron_timeout'] as $k) {
                if (array_key_exists($k, $opts) && $opts[$k] !== null && $opts[$k] !== '') {
                    $cfgBase[$k] = $opts[$k];
                }
            }
            $wsaaCfg = $cfgBase;
            $wsaaCfg['service'] = 'ws_sr_padron_a5';

            $auth = (new WsaaService($wsaaCfg))->getToken();
            $out = (new PadronService($cfgBase))->getPersona([
                'token' => (string)$auth['token'],
                'sign'  => (string)$auth['sign'],
            ], $digits);

            if (!empty($out['ok']) && !empty($out['legal_name'])) {
                // Verificación de condición: debe ser Responsable Inscripto en IVA.
                if (!$this->isResponsableInscriptoIva($out['raw'] ?? null)) {
                    return [
                        'status' => 'invalid',
                        'legal_name' => '',
                        'error' => 'El CUIT no figura como Responsable Inscripto en IVA en el padrón.',
                    ];
                }
                return [
                    'status' => 'valid',
                    'legal_name' => (string)$out['legal_name'],
                    'error' => '',
                ];
            }

            $msg = (string)($out['error'] ?? 'No se encontró en padrón.');
            return [
                'status' => 'invalid',
                'legal_name' => '',
                'error' => $msg,
            ];
        } catch (\Throwable $e) {
            // Log (best-effort) para diagnosticar "pending" eterno (WSAA/SSL/SOAP/timeout/etc.)
            try {
                if (function_exists('log_error')) {
                    log_error('ARCA PADRON validate exception: ' . $e->getMessage() . ' | cuit=' . $digits);
                }
            } catch (\Throwable $ignore) {}
            // No bloqueante
            return [
                'status' => 'pending',
                'legal_name' => '',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Heurística: en la respuesta de padrón A5, buscamos el impuesto IVA (idImpuesto=30)
     * dentro de datosRegimenGeneral.impuesto.
     */
    private function isResponsableInscriptoIva(mixed $raw): bool
    {
        if (!is_object($raw)) return false;

        $pr = $raw->personaReturn ?? $raw->getPersonaReturn ?? null;
        if (!is_object($pr)) return false;

        $drg = $pr->datosRegimenGeneral ?? null;
        if (!is_object($drg)) return false;

        $imps = $drg->impuesto ?? null;
        if ($imps === null) return false;

        // Normalizamos a array de objetos
        $list = [];
        if (is_array($imps)) {
            $list = $imps;
        } elseif (is_object($imps)) {
            $list = [$imps];
        }

        foreach ($list as $imp) {
            if (!is_object($imp)) continue;
            $id = (string)($imp->idImpuesto ?? $imp->impuesto ?? '');
            if ($id === '') {
                // algunos bindings usan idImpuesto como float
                $id = (string)($imp->id ?? '');
            }

            $idNum = preg_replace('/\D+/', '', $id) ?? '';
            if ($idNum === '30') {
                // Si hay IVA presente, lo consideramos RI.
                // (En A5 el estado puede venir como AC/BA, pero con presencia ya alcanza para MVP)
                return true;
            }
        }

        return false;
    }
}

