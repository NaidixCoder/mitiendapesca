<?php
namespace App\Controllers\Account;

use App\Repositories\Account\ProfileRepository;
use App\Services\Account\AddressService;
use App\Support\Geo\ProvinciasAR;

class AddressesController
{
    private function formExtraHead(): string
    {
        return '<link rel="stylesheet" href="' . e(asset('css/public/pages/account-address-edit.css')) . '">';
    }

    private function profilePhone(): string
    {
        $uid = $this->uid();
        if ($uid <= 0) {
            return '';
        }

        try {
            $profileRepo = new ProfileRepository(db());
            $profile = $profileRepo->findByUserId($uid);
            return trim((string)($profile['phone'] ?? ''));
        } catch (\Throwable $e) {
            if (function_exists('log_error')) {
                log_error('AddressesController@profilePhone: ' . $e->getMessage());
            }
            return '';
        }
    }

    private function provinces(): array
    {
        return ProvinciasAR::all();
    }

    private function uid(): int
    {
        return (int)($_SESSION['uid'] ?? 0);
    }

    private function syncProfilePhoneIfEmpty(int $userId, ?string $phone): void
    {
        $phone = is_string($phone) ? trim($phone) : '';
        if ($userId <= 0 || $phone === '') {
            return;
        }

        try {
            $profileRepo = new ProfileRepository(db());
            $profile = $profileRepo->findByUserId($userId);
            $currentPhone = trim((string)($profile['phone'] ?? ''));
            if ($currentPhone !== '') {
                return;
            }

            $profileRepo->upsert($userId, [
                'phone' => $phone,
                'birth_date' => (string)($profile['birth_date'] ?? ''),
                'marketing_email_opt_in' => !empty($profile['marketing_email_opt_in']) ? 1 : 0,
                'marketing_whatsapp_opt_in' => !empty($profile['marketing_whatsapp_opt_in']) ? 1 : 0,
            ]);
        } catch (\Throwable $e) {
            if (function_exists('log_error')) {
                log_error('AddressesController@syncProfilePhoneIfEmpty: ' . $e->getMessage());
            }
        }
    }

    private function wantsJson(): bool
    {
        $accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
        $xrw = (string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        return stripos($accept, 'application/json') !== false
            || stripos($xrw, 'xmlhttprequest') !== false
            || strtolower(trim($xrw)) === 'fetch';
    }

    private function safeReturnTo(?string $path): ?string
    {
        $path = is_string($path) ? trim($path) : '';
        if ($path === '') {
            return null;
        }
        if (preg_match('~^https?://~i', $path)) {
            return null;
        }
        if (strpos($path, '/checkout') !== 0) {
            return null;
        }

        return $path;
    }

    /**
     * @param array<string,string> $errors
     */
    private function validationSummary(array $errors): string
    {
        $messages = array_values(array_filter(array_map(
            static fn($v): string => is_string($v) ? trim($v) : '',
            $errors
        )));
        $messages = array_values(array_unique($messages));

        if (count($messages) === 1) {
            return $messages[0];
        }

        if (count($messages) === 2) {
            return implode(' ', $messages);
        }

        return 'Revisa los campos marcados.';
    }

    public function index(): void
    {
        auth_required('/cuenta/direcciones');

        $svc = new AddressService(db());
        $list = $svc->repo()->listByUser($this->uid());

        render_page('cuenta/direcciones/index', [
            'pageId' => 'account-addresses',
            'addresses' => $list,
        ]);
    }

    public function createForm(): void
    {
        auth_required('/cuenta/direcciones/nueva');

        $profilePhone = $this->profilePhone();

        render_page('cuenta/direcciones/form', [
            'pageId' => 'account-addresses',
            'extraHead' => $this->formExtraHead(),
            'mode' => 'create',
            'errors' => [],
            'provinces' => $this->provinces(),
            'values' => [
                'label' => '',
                'full_name' => $_SESSION['uname'] ?? '',
                'phone' => $profilePhone,
                'street' => '',
                'number' => '',
                'floor' => '',
                'apartment' => '',
                'city' => '',
                'state' => '',
                'zip' => '',
                'notes' => '',
            ],
        ]);
    }

    public function create(): void
    {
        auth_required('/cuenta/direcciones');
        verify_csrf_or_abort();

        $svc = new AddressService(db());
        [$ok, $errors, $clean] = $svc->validate($_POST);
        $returnTo = $this->safeReturnTo($_POST['return_to'] ?? null);

        if (!$ok) {
            if ($this->wantsJson()) {
                json([
                    'ok' => false,
                    'error' => $this->validationSummary($errors),
                    'errors' => $errors,
                ], 422);
            }

            render_page('cuenta/direcciones/form', [
                'pageId' => 'account-addresses',
                'extraHead' => $this->formExtraHead(),
                'mode' => 'create',
                'errors' => $errors,
                'provinces' => $this->provinces(),
                'values' => array_merge([
                    'label' => '',
                    'full_name' => '',
                    'phone' => '',
                    'street' => '',
                    'number' => '',
                    'floor' => '',
                    'apartment' => '',
                    'city' => '',
                    'state' => '',
                    'zip' => '',
                    'notes' => '',
                ], $clean),
            ]);
            return;
        }

        $uid = $this->uid();
        [$saved, $msg, $id] = $svc->create($uid, $clean);
        if ($saved) {
            $this->syncProfilePhoneIfEmpty($uid, (string)($clean['phone'] ?? ''));
        }
        if ($this->wantsJson()) {
            if ($saved) {
                json([
                    'ok' => true,
                    'message' => $msg,
                    'id' => $id,
                    'redirect_url' => $returnTo ?: url('/checkout?step=2'),
                ]);
            }

            json([
                'ok' => false,
                'error' => $msg,
            ], 422);
        }

        flash($saved ? 'ok' : 'error', $msg);
        redirect($returnTo ?: '/cuenta/direcciones');
    }

    public function editForm(): void
    {
        auth_required('/cuenta/direcciones');

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            flash('error', 'Direccion invalida.');
            redirect('/cuenta/direcciones');
        }

        $svc = new AddressService(db());
        $addr = $svc->repo()->findForUser($id, $this->uid());
        if (!$addr) {
            flash('error', 'No se encontro la direccion.');
            redirect('/cuenta/direcciones');
        }

        render_page('cuenta/direcciones/form', [
            'pageId' => 'account-addresses',
            'extraHead' => $this->formExtraHead(),
            'mode' => 'edit',
            'errors' => [],
            'provinces' => $this->provinces(),
            'values' => $addr,
        ]);
    }

    public function update(): void
    {
        auth_required('/cuenta/direcciones');
        verify_csrf_or_abort();

        $id = (int)($_POST['id'] ?? 0);
        $returnTo = $this->safeReturnTo($_POST['return_to'] ?? null);

        if ($id <= 0) {
            if ($this->wantsJson()) {
                json(['ok' => false, 'error' => 'Direccion invalida.'], 422);
            }
            flash('error', 'Direccion invalida.');
            redirect($returnTo ?: '/cuenta/direcciones');
        }

        $svc = new AddressService(db());
        [$ok, $errors, $clean] = $svc->validate($_POST);

        if (!$ok) {
            if ($this->wantsJson()) {
                json([
                    'ok' => false,
                    'error' => $this->validationSummary($errors),
                    'errors' => $errors,
                ], 422);
            }

            $clean['id'] = $id;
            render_page('cuenta/direcciones/form', [
                'pageId' => 'account-addresses',
                'extraHead' => $this->formExtraHead(),
                'mode' => 'edit',
                'errors' => $errors,
                'provinces' => $this->provinces(),
                'values' => array_merge(['id' => $id], $clean),
            ]);
            return;
        }

        $uid = $this->uid();
        [$updated, $msg] = $svc->update($uid, $id, $clean);
        if ($updated) {
            $this->syncProfilePhoneIfEmpty($uid, (string)($clean['phone'] ?? ''));
        }
        if ($this->wantsJson()) {
            if ($updated) {
                json([
                    'ok' => true,
                    'message' => $msg,
                    'redirect_url' => $returnTo ?: url('/checkout?step=2'),
                ]);
            }

            json([
                'ok' => false,
                'error' => $msg,
            ], 422);
        }

        flash($updated ? 'ok' : 'error', $msg);
        redirect($returnTo ?: '/cuenta/direcciones');
    }

    public function delete(): void
    {
        auth_required('/cuenta/direcciones');
        verify_csrf_or_abort();

        $id = (int)($_POST['id'] ?? 0);
        $returnTo = $this->safeReturnTo($_POST['return_to'] ?? null);
        if ($id <= 0) {
            flash('error', 'Direccion invalida.');
            redirect($returnTo ?: '/cuenta/direcciones');
        }

        $svc = new AddressService(db());
        [$ok, $msg] = $svc->delete($this->uid(), $id);
        flash($ok ? 'ok' : 'error', $msg);
        redirect($returnTo ?: '/cuenta/direcciones');
    }

    public function setDefault(): void
    {
        auth_required('/cuenta/direcciones');
        verify_csrf_or_abort();

        $id = (int)($_POST['id'] ?? 0);
        $returnTo = $this->safeReturnTo($_POST['return_to'] ?? null);
        if ($id <= 0) {
            flash('error', 'Direccion invalida.');
            redirect($returnTo ?: '/cuenta/direcciones');
        }

        $svc = new AddressService(db());
        [$ok, $msg] = $svc->setDefault($this->uid(), $id);
        flash($ok ? 'ok' : 'error', $msg);
        redirect($returnTo ?: '/cuenta/direcciones');
    }
}
