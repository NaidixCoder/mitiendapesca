<?php

declare(strict_types=1);

namespace App\Controllers\Account;

use App\Repositories\Users\UserFiscalProfileRepository;
use App\Services\Billing\Arca\PadronValidationService;
use App\Support\Security\CheckoutFiscal;

final class FiscalProfileController
{
    public function validateCuit(): void
    {
        auth_required('/cuenta/perfil');
        verify_csrf_or_abort();

        header('Content-Type: application/json; charset=utf-8');

        $raw = file_get_contents('php://input');
        $in = [];
        if (is_string($raw) && trim($raw) !== '') {
            $tmp = json_decode($raw, true);
            if (is_array($tmp)) $in = $tmp;
        }
        if (empty($in)) $in = $_POST;

        $taxCond = CheckoutFiscal::normalizeTaxCondition((string)($in['tax_condition'] ?? 'consumidor_final'));
        $cuitRaw = (string)($in['cuit'] ?? '');

        if ($taxCond === 'consumidor_final') {
            echo json_encode(['ok' => true, 'status' => 'skip'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $result = (new PadronValidationService(db()))->validate($cuitRaw);
        $status = (string)($result['status'] ?? 'pending');
        if ($status === 'invalid') {
            http_response_code(422);
        }

        echo json_encode([
            'ok' => true,
            'status' => $status,
            'legal_name' => (string)($result['legal_name'] ?? ''),
            'message' => (string)($result['error'] ?? ''),
        ], JSON_UNESCAPED_UNICODE);
    }

    public function update(): void
    {
        auth_required('/cuenta/perfil');
        verify_csrf_or_abort();

        $uid = (int)($_SESSION['uid'] ?? 0);
        if ($uid <= 0) { redirect('/login'); return; }

        $taxCond = CheckoutFiscal::normalizeTaxCondition((string)($_POST['tax_condition'] ?? 'consumidor_final'));
        $cuitRaw = (string)($_POST['cuit'] ?? '');

        $digits = preg_replace('/\D+/', '', $cuitRaw);
        $digits = is_string($digits) ? $digits : '';

        // Validaciones mínimas
        if ($taxCond !== 'consumidor_final') {
            if ($digits === '' || strlen($digits) !== 11) {
                flash('error', 'Ingresá tu CUIT (11 dígitos) o elegí “Consumidor final”.');
                redirect('/cuenta/perfil');
                return;
            }
            if (!CheckoutFiscal::isValidCuit($digits)) {
                flash('error', 'CUIT inválido. Revisá los 11 dígitos.');
                redirect('/cuenta/perfil');
                return;
            }
        }

        $validation = ['status' => 'unverified', 'legal_name' => '', 'error' => ''];
        if ($taxCond !== 'consumidor_final') {
            $validation = (new PadronValidationService(db()))->validate($digits);
        }

        $repo = new UserFiscalProfileRepository(db());
        if ($repo->tableExists()) {
            $repo->upsert($uid, [
                'tax_condition' => $taxCond,
                'cuit' => $taxCond === 'consumidor_final' ? '' : $digits,
                'legal_name' => (string)($validation['legal_name'] ?? ''),
                'validation_status' => (string)($validation['status'] ?? 'unverified'),
                'validation_error' => (string)($validation['status'] ?? '') === 'valid' ? '' : (string)($validation['error'] ?? ''),
                'validated_at' => (string)($validation['status'] ?? '') === 'valid' ? date('Y-m-d H:i:s') : null,
            ]);
        }

        // También prellenamos/actualizamos la sesión del checkout
        CheckoutFiscal::saveToSession([
            'tax_condition' => $taxCond,
            'cuit' => $taxCond === 'consumidor_final' ? '' : $digits,
            'validation_status' => (string)($validation['status'] ?? 'unverified'),
            'legal_name' => (string)($validation['legal_name'] ?? ''),
            'validation_error' => (string)($validation['status'] ?? '') === 'valid' ? '' : (string)($validation['error'] ?? ''),
            'validated_at' => (string)($validation['status'] ?? '') === 'valid' ? date('Y-m-d H:i:s') : null,
            'prefilled' => 0,
        ]);

        if ((string)($validation['status'] ?? '') === 'valid') {
            flash('ok', 'Datos de facturación guardados y CUIT validado.');
        } elseif ((string)($validation['status'] ?? '') === 'pending') {
            flash('warn', 'Datos de facturación guardados. No pudimos validar el CUIT ahora; podés reintentar más tarde.');
        } else {
            flash('warn', 'Datos de facturación guardados. Atención: el CUIT no pudo validarse en padrón.');
        }

        redirect('/cuenta/perfil');
    }
}

