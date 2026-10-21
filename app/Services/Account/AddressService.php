<?php
namespace App\Services\Account;

use PDO;
use App\Repositories\Account\AddressRepository;
use App\Support\Geo\AddressGeoValidator;
use App\Support\Geo\ProvinciasAR;

class AddressService
{
    private AddressRepository $repo;

    public function __construct(private PDO $db)
    {
        $this->repo = new AddressRepository($db);
    }

    public function repo(): AddressRepository
    {
        return $this->repo;
    }

    /**
     * @return array{0:bool,1:array,2:array} [ok, errors, clean]
     */
    public function validate(array $in): array
    {
        $clean = [];
        $errors = [];

        $take = function (string $k, int $max = 255) use ($in): string {
            $v = $in[$k] ?? '';
            if (!is_string($v)) {
                return '';
            }
            $v = trim($v);
            $v = preg_replace('/[^\P{C}\s]/u', '', $v) ?? '';
            if (mb_strlen($v, 'UTF-8') > $max) {
                $v = mb_substr($v, 0, $max, 'UTF-8');
            }
            return $v;
        };

        $clean['label'] = $take('label', 60);
        $clean['full_name'] = $take('full_name', 120);
        $clean['phone'] = $take('phone', 40);
        $clean['street'] = $take('street', 160);
        $clean['number'] = $take('number', 20);
        $clean['floor'] = $take('floor', 20);
        $clean['apartment'] = $take('apartment', 20);
        $clean['city'] = $take('city', 80);
        $clean['state'] = $take('state', 80);
        $clean['zip'] = preg_replace('/\D+/', '', $take('zip', 20)) ?? '';
        $clean['notes'] = $take('notes', 255);

        if ($clean['full_name'] === '') {
            $errors['full_name'] = 'Ingresa tu nombre y apellido.';
        }

        if ($clean['phone'] === '') {
            $errors['phone'] = 'Ingresa un telefono de contacto.';
        } else {
            $digits = preg_replace('/\D+/', '', $clean['phone']) ?? '';
            if (strlen($digits) < 6) {
                $errors['phone'] = 'Ingresa un telefono valido.';
            }
        }

        if ($clean['street'] === '') {
            $errors['street'] = 'Ingresa la calle.';
        }
        if ($clean['number'] === '') {
            $errors['number'] = 'Ingresa la altura.';
        }
        if ($clean['city'] === '') {
            $errors['city'] = 'Ingresa la localidad.';
        }
        if ($clean['state'] === '') {
            $errors['state'] = 'Selecciona una provincia.';
        } else {
            $allowed = ProvinciasAR::all();
            if (!in_array($clean['state'], $allowed, true)) {
                $errors['state'] = 'Selecciona una provincia valida.';
            }
        }
        if ($clean['zip'] === '') {
            $errors['zip'] = 'Ingresa el codigo postal.';
        } elseif (!preg_match('/^\d{4}$/', $clean['zip'])) {
            $errors['zip'] = 'Ingresa un codigo postal valido de 4 digitos.';
        }

        if (!isset($errors['zip'], $errors['city'], $errors['state'])) {
            $geo = AddressGeoValidator::validate($clean['zip'], $clean['city'], $clean['state']);
            if (!$geo['ok']) {
                $errors = array_merge($errors, $geo['errors']);
            }
        }

        if ($clean['label'] === '') {
            $clean['label'] = null;
        }
        if ($clean['floor'] === '') {
            $clean['floor'] = null;
        }
        if ($clean['apartment'] === '') {
            $clean['apartment'] = null;
        }
        if ($clean['notes'] === '') {
            $clean['notes'] = null;
        }

        return [empty($errors), $errors, $clean];
    }

    /**
     * @return array{0:bool,1:string,2:int|null}
     */
    public function create(int $userId, array $data): array
    {
        [$ok, $errors, $clean] = $this->validate($data);
        if (!$ok) {
            return [false, 'Revisa los campos marcados.', null];
        }

        try {
            $this->db->beginTransaction();

            $count = $this->repo->countByUser($userId);
            $clean['is_default'] = ($count === 0) ? 1 : 0;

            $id = $this->repo->create($userId, $clean);

            if ($clean['is_default'] === 1) {
                $this->repo->clearDefault($userId);
                $this->repo->setDefault($id, $userId);
            }

            $this->db->commit();
            return [true, 'Direccion guardada.', $id];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            if (function_exists('log_error')) {
                log_error('AddressService@create: ' . $e->getMessage());
            }
            return [false, 'No se pudo guardar la direccion. Intenta nuevamente.', null];
        }
    }

    /**
     * @return array{0:bool,1:string}
     */
    public function update(int $userId, int $id, array $data): array
    {
        [$ok, $errors, $clean] = $this->validate($data);
        if (!$ok) {
            return [false, 'Revisa los campos marcados.'];
        }

        try {
            $updated = $this->repo->update($id, $userId, $clean);
            if (!$updated) {
                return [false, 'No se encontro la direccion o no tenes permisos.'];
            }
            return [true, 'Direccion actualizada.'];
        } catch (\Throwable $e) {
            if (function_exists('log_error')) {
                log_error('AddressService@update: ' . $e->getMessage());
            }
            return [false, 'No se pudo actualizar la direccion.'];
        }
    }

    /**
     * @return array{0:bool,1:string}
     */
    public function delete(int $userId, int $id): array
    {
        try {
            $this->db->beginTransaction();

            $addr = $this->repo->findForUser($id, $userId);
            if (!$addr) {
                $this->db->rollBack();
                return [false, 'No se encontro la direccion.'];
            }

            $wasDefault = (int)($addr['is_default'] ?? 0) === 1;
            $ok = $this->repo->delete($id, $userId);

            if ($ok && $wasDefault) {
                $list = $this->repo->listByUser($userId);
                $newDefault = $list[0]['id'] ?? null;
                if ($newDefault) {
                    $this->repo->clearDefault($userId);
                    $this->repo->setDefault((int)$newDefault, $userId);
                }
            }

            $this->db->commit();
            return [true, 'Direccion eliminada.'];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            if (function_exists('log_error')) {
                log_error('AddressService@delete: ' . $e->getMessage());
            }
            return [false, 'No se pudo eliminar la direccion.'];
        }
    }

    /**
     * @return array{0:bool,1:string}
     */
    public function setDefault(int $userId, int $id): array
    {
        try {
            $this->db->beginTransaction();

            $addr = $this->repo->findForUser($id, $userId);
            if (!$addr) {
                $this->db->rollBack();
                return [false, 'No se encontro la direccion.'];
            }

            $this->repo->clearDefault($userId);
            $ok = $this->repo->setDefault($id, $userId);

            $this->db->commit();
            return [$ok, $ok ? 'Direccion principal actualizada.' : 'No se pudo actualizar la direccion principal.'];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            if (function_exists('log_error')) {
                log_error('AddressService@setDefault: ' . $e->getMessage());
            }
            return [false, 'No se pudo actualizar la direccion principal.'];
        }
    }
}
