<?php
namespace App\Services\Billing\Arca;

use App\Repositories\Settings\AppSettingsRepository;
use PDO;

final class ArcaConfig
{
    public function __construct(private PDO $db) {}


    private function normalizePath(string $path): string
    {
        $path = trim($path);
        if ($path === '') return '';
        if ($this->isAbsolutePath($path)) return $path;
        // Permite usar rutas relativas tipo "storage/arca/arca_homo.crt" tanto en local como en Hostinger.
        return base_path($path);
    }

    private function isAbsolutePath(string $path): bool
    {
        if ($path === '') return false;
        // Linux / Unix, Windows UNC
        if ($path[0] === '/' || $path[0] === '\\') return true;
        // Windows drive letter
        return (bool)preg_match('/^[A-Za-z]:[\\/]/', $path);
    }


    /**
     * Config desde app_settings + env.
     *
     * @return array<string,mixed>
     */
    public function get(): array
    {
        $settings = new AppSettingsRepository($this->db);
        $env = (string)($settings->get('arca_env') ?? 'homologacion');
        if (!in_array($env, ['homologacion','produccion'], true)) $env = 'homologacion';

        $ptoVta = (int)($settings->get('arca_pto_vta') ?? 1);
        if ($ptoVta <= 0) $ptoVta = 1;

        $auto = (int)($settings->get('arca_auto_emit') ?? 0) === 1;

        // Momento de auto-emisión: 'paid' (al confirmar el pago) o 'shipped' (al pasar a Enviado).
        $trigger = strtolower(trim((string)($settings->get('arca_auto_emit_trigger') ?? 'paid')));
        if (!in_array($trigger, ['paid','shipped'], true)) $trigger = 'paid';

        $service = (string)($settings->get('arca_ws_service') ?? 'wsfe');
        $service = trim($service) !== '' ? trim($service) : 'wsfe';

        // Secrets (no en DB): certificados/clave y CUIT emisor
        $cuit = (string)env('ARCA_CUIT', env('AFIP_CUIT', ''));
        $certPath = (string)env('ARCA_CERT_PATH', '');
        $certPath = $this->normalizePath($certPath);
        $keyPath  = (string)env('ARCA_KEY_PATH', '');
        $keyPath  = $this->normalizePath($keyPath);
        $keyPass  = (string)env('ARCA_KEY_PASS', '');

        return [
            'env' => $env,
            'pto_vta' => $ptoVta,
            'auto_emit' => $auto,
            'auto_emit_trigger' => $trigger,
            'service' => $service,
            'cuit' => $cuit,
            'cert_path' => $certPath,
            'key_path' => $keyPath,
            'key_pass' => $keyPass,
        ];
    }
}
