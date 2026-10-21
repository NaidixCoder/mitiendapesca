<?php
namespace App\Controllers\Admin;

use App\Services\Billing\Arca\ArcaConfig;
use App\Services\Billing\Arca\WsaaService;
use App\Services\Billing\Arca\WsfeService;

final class ArcaController extends BaseController
{
    public function test(): void
    {
        $result = $this->runDiagnostics();
        render_page('admin/arca/test', [
            'result' => $result,
        ]);
    }

    public function testJson(): void
    {
        try {
            $result = $this->runDiagnostics();
            $this->json(['ok' => true, 'result' => $result]);
        } catch (\Throwable $e) {
            $this->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /** @return array<string,mixed> */
    private function runDiagnostics(): array
    {
        $db = db();
        $cfg = (new ArcaConfig($db))->get();

        $env = (string)($cfg['env'] ?? 'homologacion');
        $service = (string)($cfg['service'] ?? 'wsfe');
        $ptoVta = (int)($cfg['pto_vta'] ?? 1);

        $cuit = preg_replace('/\D+/', '', (string)($cfg['cuit'] ?? '')) ?? '';
        $certPath = (string)($cfg['cert_path'] ?? '');
        $keyPath  = (string)($cfg['key_path'] ?? '');
        $hasSoap = extension_loaded('soap') && class_exists('SoapClient');
        $opensslOk = extension_loaded('openssl') && function_exists('openssl_pkcs7_sign');
        $iniFile = (string)(php_ini_loaded_file() ?: '');
        $phpSapi = (string)PHP_SAPI;

        $steps = [];
        $warnings = [];

        // Validaciones básicas
        if ($cuit === '' || strlen($cuit) !== 11) {
            $warnings[] = 'ARCA_CUIT: debería tener 11 dígitos (sin guiones).';
        }
        if ($ptoVta <= 0) {
            $warnings[] = 'Punto de venta inválido (arca_pto_vta).';
        }
        if (!$hasSoap) {
            $warnings[] = 'Extensión SOAP no disponible. (Habilitar php_soap en php.ini y reiniciar Apache).';
        }
        if (!$opensslOk) {
            $warnings[] = 'Extensión OpenSSL no disponible. (Debe estar habilitada para firmar el TRA: openssl_pkcs7_sign).';
        }
        if ($certPath === '' || !is_file($certPath)) {
            $warnings[] = 'ARCA_CERT_PATH: certificado .crt no encontrado: ' . ($certPath !== '' ? $certPath : '(vacío)');
        }
        if ($keyPath === '' || !is_file($keyPath)) {
            $warnings[] = 'ARCA_KEY_PATH: clave privada .key no encontrada: ' . ($keyPath !== '' ? $keyPath : '(vacío)');
        }

        // WSAA token
        $wsaaOk = false;
        $taInfo = null;
        try {
            $wsaa = new WsaaService($cfg);
            $ta = $wsaa->getToken();
            $wsaaOk = true;
            $taInfo = [
                'expires_at' => (int)($ta['expires_at'] ?? 0),
                'expires_at_iso' => ((int)($ta['expires_at'] ?? 0) > 0) ? gmdate('c', (int)$ta['expires_at']) : null,
            ];
            $steps[] = ['step' => 'WSAA (loginCms)', 'ok' => true, 'info' => $taInfo];
        } catch (\Throwable $e) {
            $steps[] = ['step' => 'WSAA (loginCms)', 'ok' => false, 'error' => $e->getMessage()];
        }

        // WSFE ultimo autorizado (solo si hay token)
        if ($wsaaOk && $hasSoap) {
            try {
                $wsaa = new WsaaService($cfg);
                $ta = $wsaa->getToken();
                $wsfe = new WsfeService($cfg);
                // Factura C: 11
                $ultimo = $wsfe->ultimoAutorizado(['token'=>$ta['token'],'sign'=>$ta['sign']], $ptoVta, 11);
                $steps[] = ['step' => 'WSFE (FECompUltimoAutorizado)', 'ok' => true, 'info' => ['ultimo_cbte' => $ultimo]];
            } catch (\Throwable $e) {
                $steps[] = ['step' => 'WSFE (FECompUltimoAutorizado)', 'ok' => false, 'error' => $e->getMessage()];
            }
        }

        return [
            'config' => [
                'env' => $env,
                'service' => $service,
                'pto_vta' => $ptoVta,
                'cuit' => $cuit !== '' ? (substr($cuit, 0, 2).'*********') : '',
                'cert_path_set' => $certPath !== '',
                'cert_exists' => $certPath !== '' && is_file($certPath),
                'key_path_set' => $keyPath !== '',
                'key_exists' => $keyPath !== '' && is_file($keyPath),
                'soap_available' => $hasSoap,
                'openssl_available' => $opensslOk,
                'php_ini' => $iniFile,
                'php_sapi' => $phpSapi,
            ],
            'warnings' => $warnings,
            'steps' => $steps,
            'server_time_utc' => gmdate('c'),
        ];
    }
}
