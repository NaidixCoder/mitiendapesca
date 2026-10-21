<?php

declare(strict_types=1);

namespace App\Services\Billing\Arca;

/**
 * WS SR Padrón A5 (consulta básica de persona por CUIT)
 *
 * Importante:
 * - Requiere WSAA (token+sign) con service = ws_sr_padron_a5
 * - En homologación la data puede no estar completa.
 */
final class PadronService
{
    /** @param array<string,mixed> $cfg */
    public function __construct(private array $cfg) {}

    /**
     * @param array{token:string,sign:string} $auth
     * @return array{ok:bool, legal_name:string, raw?:mixed, error?:string}
     */
    public function getPersona(array $auth, string $cuit): array
    {
        $cuitNum = preg_replace('/\D+/', '', $cuit) ?? '';
        if ($cuitNum === '' || strlen($cuitNum) !== 11) {
            return ['ok' => false, 'legal_name' => '', 'error' => 'CUIT inválido'];
        }

        try {
            $client = $this->client();
            // nombre del método: getPersona
            $resp = $client->getPersona([
                'token' => $auth['token'],
                'sign' => $auth['sign'],
                'cuitRepresentada' => (float)$this->sellerCuit(),
                'idPersona' => (float)$cuitNum,
            ]);

            $pr = $resp->personaReturn ?? $resp->getPersonaReturn ?? null;
            $datos = $pr->datosGenerales ?? null;
            $legal = '';
            if ($datos) {
                // persona física
                $nombre = trim((string)($datos->nombre ?? ''));
                $apellido = trim((string)($datos->apellido ?? ''));
                $razon = trim((string)($datos->razonSocial ?? ''));
                $legal = $razon !== '' ? $razon : trim($apellido . ' ' . $nombre);
            }

            if ($legal === '') {
                return ['ok' => false, 'legal_name' => '', 'error' => 'Sin datos en padrón', 'raw' => $resp];
            }

            return ['ok' => true, 'legal_name' => $legal, 'raw' => $resp];
        } catch (\Throwable $e) {
            // Log dedicado (best-effort) para poder diagnosticar "pending".
            try {
                if (function_exists('log_error')) {
                    log_error('ARCA PADRON error: ' . $e->getMessage() . ' | cuit=' . $cuitNum);
                }
            } catch (\Throwable $ignore) {}

            return ['ok' => false, 'legal_name' => '', 'error' => $e->getMessage()];
        }
    }

    private function sellerCuit(): string
    {
        $cuit = preg_replace('/\D+/', '', (string)($this->cfg['cuit'] ?? '')) ?? '';
        if ($cuit === '') throw new \RuntimeException('ARCA_CUIT no configurado.');
        return $cuit;
    }

    private function client(): \SoapClient
    {
        if (!class_exists('SoapClient')) {
            throw new \RuntimeException('Extensión SOAP no disponible en PHP.');
        }
        $env = (string)($this->cfg['env'] ?? 'homologacion');
        $wsdl = $env === 'produccion'
            ? 'https://aws.afip.gov.ar/sr-padron/webservices/personaServiceA5?WSDL'
            : 'https://awshomo.afip.gov.ar/sr-padron/webservices/personaServiceA5?WSDL';

        // Timeouts (opcional):
        // - padron_connection_timeout: segundos para conectar
        // - padron_timeout: segundos totales de espera (stream)
        $connTimeout = (int)($this->cfg['padron_connection_timeout'] ?? 0);
        $timeout = (int)($this->cfg['padron_timeout'] ?? 0);

        $opts = [
            'soap_version' => SOAP_1_1,
            'exceptions' => true,
            'trace' => false,
            'cache_wsdl' => WSDL_CACHE_BOTH,
        ];

        if ($connTimeout > 0) {
            $opts['connection_timeout'] = $connTimeout;
        }

        if ($timeout > 0) {
            $opts['stream_context'] = stream_context_create([
                'http' => ['timeout' => $timeout],
                'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true],
            ]);
        }

        return new \SoapClient($wsdl, $opts);
    }
}
