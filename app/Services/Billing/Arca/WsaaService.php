<?php
namespace App\Services\Billing\Arca;

/**
 * WSAA (LoginCms)
 * - Genera TRA (loginTicketRequest)
 * - Firma CMS (PKCS7) con certificado/clave
 * - Llama a LoginCms y devuelve Token/Sign
 * - Cachea TA en storage/arca/ta_*.json
 */
final class WsaaService
{
    /** @param array<string,mixed> $cfg */
    public function __construct(private array $cfg) {}

    /** @return array{token:string,sign:string,expires_at:int,raw:string} */
    public function getToken(): array
    {
        $env = (string)($this->cfg['env'] ?? 'homologacion');
        $service = (string)($this->cfg['service'] ?? 'wsfe');
        $cuit = preg_replace('/\D+/', '', (string)($this->cfg['cuit'] ?? '')) ?? '';
        if ($cuit === '') throw new \RuntimeException('ARCA_CUIT no configurado.');

        $cacheFile = $this->cachePath($env, $service, $cuit);
        $cached = $this->readCache($cacheFile);
        if ($cached) return $cached;

        $tra = $this->buildTRA($service);
        $cms = $this->signTRA($tra);
        $taXml = $this->loginCms($cms, $env);
        $parsed = $this->parseTA($taXml);
        $this->writeCache($cacheFile, $parsed);
        return $parsed;
    }

    private function cachePath(string $env, string $service, string $cuit): string
    {
        $base = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4);
        $dir = $base . '/storage/arca';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $env.'_'.$service.'_'.$cuit) ?? 'ta';
        return $dir . '/ta_' . $safe . '.json';
    }

    /** @return array{token:string,sign:string,expires_at:int,raw:string}|null */
    private function readCache(string $path): ?array
    {
        if (!is_file($path)) return null;
        $raw = @file_get_contents($path);
        if (!is_string($raw) || trim($raw) === '') return null;
        $j = json_decode($raw, true);
        if (!is_array($j)) return null;

        $expiresAt = (int)($j['expires_at'] ?? 0);
        // margen 2 minutos
        if ($expiresAt > 0 && (time() + 120) < $expiresAt && !empty($j['token']) && !empty($j['sign'])) {
            return [
                'token' => (string)$j['token'],
                'sign' => (string)$j['sign'],
                'expires_at' => $expiresAt,
                'raw' => (string)($j['raw'] ?? ''),
            ];
        }
        return null;
    }

    /** @param array{token:string,sign:string,expires_at:int,raw:string} $data */
    private function writeCache(string $path, array $data): void
    {
        @file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    }

    private function buildTRA(string $service): string
    {
        $uniqueId = (string)time();
        $gen = gmdate('Y-m-d\TH:i:s\Z', time() - 60);
        $exp = gmdate('Y-m-d\TH:i:s\Z', time() + 60 * 60);

        $service = htmlspecialchars($service, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>".
            "<loginTicketRequest version=\"1.0\">".
            "<header>".
            "<uniqueId>{$uniqueId}</uniqueId>".
            "<generationTime>{$gen}</generationTime>".
            "<expirationTime>{$exp}</expirationTime>".
            "</header>".
            "<service>{$service}</service>".
            "</loginTicketRequest>";
        return $xml;
    }

    private function signTRA(string $traXml): string
    {
        $certPath = (string)($this->cfg['cert_path'] ?? '');
        $keyPath = (string)($this->cfg['key_path'] ?? '');
        $pass = (string)($this->cfg['key_pass'] ?? '');

        if ($certPath === '' || $keyPath === '') {
            throw new \RuntimeException('ARCA_CERT_PATH / ARCA_KEY_PATH no configurados.');
        }
        if (!is_file($certPath)) throw new \RuntimeException('Cert no encontrado: '.$certPath);
        if (!is_file($keyPath)) throw new \RuntimeException('Key no encontrado: '.$keyPath);

        $tmpDir = sys_get_temp_dir();
        $traFile = tempnam($tmpDir, 'tra_');
        $sigFile = tempnam($tmpDir, 'cms_');
        if ($traFile === false || $sigFile === false) {
            throw new \RuntimeException('No se pudo crear archivos temporales para WSAA.');
        }
        file_put_contents($traFile, $traXml);

        $ok = @openssl_pkcs7_sign(
            $traFile,
            $sigFile,
            'file://'.$certPath,
            ['file://'.$keyPath, $pass],
            [],
            PKCS7_BINARY
        );

        $cms = '';
        if ($ok) {
            $signed = file_get_contents($sigFile);
            if (is_string($signed)) {
                // El PKCS7 viene con headers MIME; nos quedamos con el bloque base64
                $parts = preg_split("/\R\R/", $signed, 2);
                $cms = is_array($parts) && isset($parts[1]) ? trim((string)$parts[1]) : '';
                $cms = preg_replace('/\s+/', '', $cms) ?? '';
            }
        }

        @unlink($traFile);
        @unlink($sigFile);

        if (!$ok || $cms === '') {
            throw new \RuntimeException('No se pudo firmar TRA (openssl_pkcs7_sign). Verificá cert/key/pass y extensión openssl.');
        }
        return $cms;
    }

    private function loginCms(string $cms, string $env): string
    {
        $wsdl = $env === 'produccion'
            ? 'https://wsaa.afip.gov.ar/ws/services/LoginCms?wsdl'
            : 'https://wsaahomo.afip.gov.ar/ws/services/LoginCms?wsdl';

        if (!class_exists('SoapClient')) {
            throw new \RuntimeException('Extensión SOAP no disponible en PHP.');
        }

        $client = new \SoapClient($wsdl, [
            'soap_version' => SOAP_1_1,
            'exceptions' => true,
            'trace' => false,
            'cache_wsdl' => WSDL_CACHE_BOTH,
        ]);

        $resp = $client->loginCms(['in0' => $cms]);
        $out = $resp->loginCmsReturn ?? null;
        if (!is_string($out) || trim($out) === '') {
            throw new \RuntimeException('WSAA: respuesta vacía de loginCms.');
        }
        return $out;
    }

    /** @return array{token:string,sign:string,expires_at:int,raw:string} */
    private function parseTA(string $taXml): array
    {
        $xml = @simplexml_load_string($taXml);
        if (!$xml) throw new \RuntimeException('WSAA: no se pudo parsear TA.');

        $token = (string)($xml->credentials->token ?? '');
        $sign  = (string)($xml->credentials->sign ?? '');
        $expStr = (string)($xml->header->expirationTime ?? '');
        $expTs = 0;
        if ($expStr !== '') {
            $expTs = strtotime($expStr) ?: 0;
        }
        if ($token === '' || $sign === '') {
            throw new \RuntimeException('WSAA: token/sign vacíos.');
        }
        return [
            'token' => $token,
            'sign' => $sign,
            'expires_at' => $expTs,
            'raw' => $taXml,
        ];
    }
}
