<?php
namespace App\Services\Shipping\CorreoArgentino;

use App\Repositories\Settings\AppSettingsRepository;
use PDO;

final class CorreoClient
{
    public function __construct(private PDO $db) {}

    /** @return array{token:string,exp:?int} */
    private function getTokenCached(): array
    {
        $cfg = (new CorreoConfig($this->db))->get();
        $mode = (string)($cfg['mode'] ?? 'test');

        $cacheFile = base_path('storage/cache/correo_token_' . $mode . '.json');
        try {
            if (is_file($cacheFile)) {
                $raw = @file_get_contents($cacheFile);
                $j = $raw ? json_decode($raw, true) : null;
                $token = is_array($j) ? (string)($j['token'] ?? '') : '';
                $exp = is_array($j) ? (int)($j['exp'] ?? 0) : 0;
                if ($token !== '' && $exp > 0 && $exp > (time() + 60)) {
                    return ['token' => $token, 'exp' => $exp];
                }
            }
        } catch (\Throwable $e) {}

        $token = $this->authenticate();
        $exp = $this->jwtExp($token);
        try {
            @mkdir(dirname($cacheFile), 0775, true);
            @file_put_contents($cacheFile, json_encode(['token' => $token, 'exp' => $exp], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $e) {}

        return ['token' => $token, 'exp' => $exp];
    }

    private function jwtExp(string $jwt): ?int
    {
        $parts = explode('.', $jwt);
        if (count($parts) < 2) return null;
        $payload = $parts[1];
        $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
        $json = base64_decode(strtr($payload, '-_', '+/'));
        if (!$json) return null;
        $data = json_decode($json, true);
        $exp = is_array($data) ? (int)($data['exp'] ?? 0) : 0;
        return $exp > 0 ? $exp : null;
    }

    private function request(string $method, string $path, array $opts = []): array
    {
        $cfg = (new CorreoConfig($this->db))->get();
        $baseUrl = rtrim((string)($cfg['base_url'] ?? ''), '/');
        if ($baseUrl === '') throw new \RuntimeException('Base URL de Correo Argentino no configurada.');

        $url = $baseUrl . '/' . ltrim($path, '/');

        // Query params (para endpoints GET como /agencies)
        if (isset($opts['query']) && is_array($opts['query']) && !empty($opts['query'])) {
            $qs = http_build_query($opts['query']);
            if ($qs !== '') {
                $url .= (strpos($url, '?') === false ? '?' : '&') . $qs;
            }
        }
        $headers = $opts['headers'] ?? [];
        $body = $opts['body'] ?? null;
        $basicAuth = $opts['basic_auth'] ?? null; // [user,pass]

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);

        $h = [];
        foreach ($headers as $k => $v) {
            $h[] = $k . ': ' . $v;
        }

        if (is_array($basicAuth) && count($basicAuth) >= 2) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, (string)$basicAuth[0] . ':' . (string)$basicAuth[1]);
        }

        if ($body !== null) {
            if (is_array($body)) {
                $json = json_encode($body, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
                $h[] = 'Content-Type: application/json';
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
        }

        if (!empty($h)) curl_setopt($ch, CURLOPT_HTTPHEADER, $h);

        $raw = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $raw === null) {
            throw new \RuntimeException('Error de red: ' . ($err ?: 'sin detalle'));
        }

        $data = json_decode((string)$raw, true);
        if ($http < 200 || $http >= 300) {
            $msg = '';
            if (is_array($data) && isset($data['message'])) $msg = (string)$data['message'];
            if ($msg === '' && is_array($data) && isset($data['error'])) $msg = (string)$data['error'];
            if ($msg === '') $msg = 'HTTP ' . $http;
            throw new \RuntimeException($msg);
        }

        return is_array($data) ? $data : ['raw' => (string)$raw];
    }

    /** Obtiene JWT (HTTP Basic Auth a /token) */
    public function authenticate(): string
    {
        $cfg = (new CorreoConfig($this->db))->get();
        $user = trim((string)($cfg['api_username'] ?? ''));
        $pass = trim((string)($cfg['api_password'] ?? ''));
        if ($user === '' || $pass === '') {
            throw new \RuntimeException('Faltan credenciales de API (CORREO_API_USERNAME / CORREO_API_PASSWORD).');
        }

        $res = $this->request('POST', '/token', [
            'basic_auth' => [$user, $pass],
            'headers' => ['Accept' => 'application/json'],
        ]);

        $token = (string)($res['token'] ?? '');
        if ($token === '') throw new \RuntimeException('La API no devolvió token.');
        return $token;
    }

        public function hasUserCredentials(): bool
    {
        $cfg = (new CorreoConfig($this->db))->get();
        $email = trim((string)($cfg['user_email'] ?? ''));
        $pass = trim((string)($cfg['user_password'] ?? ''));
        return ($email !== '' && $pass !== '');
    }

/** @return array{customerId:string,createdAt:?string} */
    public function validateUser(): array
    {
        $cfg = (new CorreoConfig($this->db))->get();
        $email = trim((string)($cfg['user_email'] ?? ''));
        $pass = trim((string)($cfg['user_password'] ?? ''));
        if ($email === '' || $pass === '') {
            throw new \RuntimeException('Faltan credenciales de usuario (CORREO_USER_EMAIL / CORREO_USER_PASSWORD).');
        }

        $t = $this->getTokenCached();
        $res = $this->request('POST', '/users/validate', [
            'headers' => [
                'Authorization' => 'Bearer ' . $t['token'],
                'Accept' => 'application/json',
            ],
            'body' => ['email' => $email, 'password' => $pass],
        ]);

        $cid = (string)($res['customerId'] ?? '');
        if ($cid === '') throw new \RuntimeException('La API no devolvió customerId.');

        return ['customerId' => $cid, 'createdAt' => isset($res['createdAt']) ? (string)$res['createdAt'] : null];
    }

    private function resolveCustomerId(bool $autoHydrate = true): string
    {
        $cfg = (new CorreoConfig($this->db))->get();
        $customerId = trim((string)($cfg['customer_id'] ?? ''));
        if ($customerId !== '') return $customerId;

        try {
            $customerId = trim((string)((new AppSettingsRepository($this->db))->get('correo_customer_id', '') ?? ''));
        } catch (\Throwable $e) {
            $customerId = '';
        }
        if ($customerId !== '') return $customerId;

        if (!$autoHydrate || !$this->hasUserCredentials()) {
            return '';
        }

        try {
            $validated = $this->validateUser();
            $customerId = trim((string)($validated['customerId'] ?? ''));
            if ($customerId !== '') {
                $this->saveCustomerId($customerId);
                return $customerId;
            }
        } catch (\Throwable $e) {
            return '';
        }

        return '';
    }

    /** @return array<int,array<string,mixed>> */
    public function track(string $shippingId): array
    {
        $shippingId = trim($shippingId);
        if ($shippingId === '') throw new \RuntimeException('shippingId vacío.');

        $t = $this->getTokenCached();
        $res = $this->request('GET', '/shipping/tracking', [
            'headers' => [
                'Authorization' => 'Bearer ' . $t['token'],
                'Accept' => 'application/json',
            ],
            // La doc manda body JSON en GET.
            'body' => ['shippingId' => $shippingId],
        ]);

        // La API puede devolver array o objeto. Normalizamos a array de "events".
        if (isset($res[0]) && is_array($res[0])) {
            return $res;
        }
        // Objeto simple
        return [$res];
    }



    /** @param array{postalCodeOrigin:string,postalCodeDestination:string,deliveredType:string,dimensions:array<string,int>} $payload
     *  @return array{0:bool,1:mixed} [ok, data]
     */
    public function rates(array $payload): array
    {
        $customerId = $this->resolveCustomerId(true);
        if ($customerId === '') return [false, ['error' => 'Falta customerId (config Correo).']];

        $deliveredType = strtoupper(trim((string)($payload['deliveredType'] ?? 'D')));
        if (!in_array($deliveredType, ['D','S'], true)) $deliveredType = 'D';

        $body = [
            'customerId' => $customerId,
            'postalCodeOrigin' => trim((string)($payload['postalCodeOrigin'] ?? '')),
            'postalCodeDestination' => trim((string)($payload['postalCodeDestination'] ?? '')),
            'deliveredType' => $deliveredType,
            'dimensions' => $payload['dimensions'] ?? [],
        ];

        $t = $this->getTokenCached();
        try {
            $res = $this->request('POST', '/rates', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $t['token'],
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'body' => $body,
            ]);
            return [true, $res];
        } catch (\Throwable $e) {
            return [false, ['error' => $e->getMessage()]];
        }
    }

    /** @return array{0:bool,1:array<int,array<string,mixed>>} */
    public function agencies(string $provinceCode): array
    {
        $customerId = $this->resolveCustomerId(true);
        if ($customerId === '') return [false, []];

        $provinceCode = strtoupper(trim($provinceCode));
        if ($provinceCode === '' || !preg_match('/^[A-Z]$/', $provinceCode)) return [false, []];

        $t = $this->getTokenCached();

        try {
            $res = $this->request('GET', '/agencies', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $t['token'],
                    'Accept' => 'application/json',
                ],
                'query' => [
                    'customerId' => $customerId,
                    'provinceCode' => $provinceCode,
                ],
            ]);

            // Normalizamos a lista
            if (is_array($res) && isset($res[0]) && is_array($res[0])) return [true, $res];
            if (is_array($res) && isset($res['agencies']) && is_array($res['agencies'])) return [true, $res['agencies']];
            if (is_array($res)) return [true, [$res]];
        } catch (\Throwable $e) {}

        return [false, []];
    }

    /**
     * /shipping/import [POST]
     * @param array<string,mixed> $payload
     * @return array{0:bool,1:array<string,mixed>}
     */
    public function shippingImport(array $payload): array
    {
        $t = $this->getTokenCached();

        try {
            $res = $this->request('POST', '/shipping/import', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $t['token'],
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            if (is_array($res)) return [true, $res];
        } catch (\Throwable $e) {
            // intentar leer respuesta si vino con code/message
        }

        return [false, ['code' => 'api_error', 'message' => 'No se pudo importar el envío.']];
    }

    public function saveCustomerId(string $customerId): void
    {
        $customerId = trim($customerId);
        if ($customerId === '') return;
        try {
            (new AppSettingsRepository($this->db))->set('correo_customer_id', $customerId);
        } catch (\Throwable $e) {
            // silencioso
        }
    }
}
