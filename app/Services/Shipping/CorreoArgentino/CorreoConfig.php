<?php
namespace App\Services\Shipping\CorreoArgentino;

use App\Repositories\Settings\AppSettingsRepository;
use PDO;

final class CorreoConfig
{
    public function __construct(private PDO $db) {}

    /** @return array<string,mixed> */
    public function get(): array
    {
        $settings = new AppSettingsRepository($this->db);

        $enabled = ((string)($settings->get('correo_enabled') ?? '0') === '1');
        $mode = strtolower(trim((string)($settings->get('correo_mode') ?? 'test')));
        if (!in_array($mode, ['test','prod'], true)) $mode = 'test';

        $useEnv = ((string)($settings->get('correo_use_env') ?? '1') === '1');

        // QA/Dev: mock import (solo TEST)
        $mockImport = ((string)($settings->get('correo_mock_import') ?? '0') === '1');
        if ($mode !== 'test') $mockImport = false;

        $baseUrl = ($mode === 'prod')
            ? 'https://api.correoargentino.com.ar/micorreo/v1'
            : 'https://apitest.correoargentino.com.ar/micorreo/v1';

        $apiUser = '';
        $apiPass = '';
        $userEmail = '';
        $userPass = '';

        if ($useEnv) {
            $apiUser = (string)env('CORREO_API_USERNAME', '');
            $apiPass = (string)env('CORREO_API_PASSWORD', '');
            $userEmail = (string)env('CORREO_USER_EMAIL', '');
            $userPass = (string)env('CORREO_USER_PASSWORD', '');
        } else {
            $apiUser = (string)($settings->get('correo_api_username') ?? '');
            $apiPass = (string)($settings->get('correo_api_password') ?? '');
            $userEmail = (string)($settings->get('correo_user_email') ?? '');
            $userPass = (string)($settings->get('correo_user_password') ?? '');
        }

        return [
            'enabled' => $enabled,
            'mode' => $mode,
            'base_url' => $baseUrl,
            'use_env' => $useEnv,
            'mock_import' => $mockImport,
            'api_username' => $apiUser,
            'api_password' => $apiPass,
            'user_email' => $userEmail,
            'user_password' => $userPass,
            'customer_id' => (string)($settings->get('correo_customer_id') ?? ''),
        ];
    }
}
