<?php
namespace App\Services\Suppliers;

use App\Repositories\Suppliers\ProveedorRepository;
use App\Support\Security\CheckoutFiscal;
use RuntimeException;

final class ProveedorService
{
    public function __construct(private ProveedorRepository $repo) {}

    public function list(array $filters = []): array
    {
        return $this->repo->list($filters);
    }

    public function count(array $filters = []): int
    {
        return $this->repo->count($filters);
    }

    public function findById(int $id): ?array
    {
        return $this->repo->findById($id);
    }

    /**
     * @return array{ok:bool, errors:array<string,string>, id?:int}
     */
    public function save(array $in, ?int $id = null): array
    {
        $nombre = trim((string)($in['nombre'] ?? ''));
        $cuitRaw = (string)($in['cuit'] ?? '');
        $cuit = preg_replace('/\D+/', '', $cuitRaw);
        $cuit = is_string($cuit) ? $cuit : '';
        $email = trim((string)($in['email'] ?? ''));
        $telefono = trim((string)($in['telefono'] ?? ''));
        $condIva = trim((string)($in['condicion_iva'] ?? ''));
        $isActive = (int)($in['is_active'] ?? 1) ? 1 : 0;

        $errors = [];
        if ($nombre === '') $errors['nombre'] = 'Ingresá el nombre del proveedor.';
        elseif (mb_strlen($nombre) > 180) $errors['nombre'] = 'El nombre es demasiado largo.';
        if (mb_strlen($telefono) > 64) $errors['telefono'] = 'El teléfono es demasiado largo.';
        if (mb_strlen($email) > 190) $errors['email'] = 'El email es demasiado largo.';
        if (!in_array($condIva, ['', 'responsable_inscripto', 'monotributo', 'exento', 'consumidor_final'], true)) $errors['condicion_iva'] = 'Condición IVA inválida.';
        if (!in_array((string)($in['is_active'] ?? '1'), ['0', '1'], true)) $errors['is_active'] = 'Estado inválido.';
        if ($cuit === '' || strlen($cuit) !== 11 || !CheckoutFiscal::isValidCuit($cuit)) {
            $errors['cuit'] = 'CUIT inválido. Revisá los 11 dígitos.';
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email inválido.';
        }

        // Unicidad por CUIT
        if (!$errors) {
            $existing = $this->repo->findByCuit($cuit);
            if ($existing && ($id === null || (int)$existing['id'] !== (int)$id)) {
                $errors['cuit'] = 'Ya existe un proveedor con ese CUIT.';
            }
        }

        if ($errors) return ['ok' => false, 'errors' => $errors];

        $data = [
            'nombre' => $nombre,
            'cuit' => $cuit,
            'email' => $email,
            'telefono' => $telefono,
            'condicion_iva' => $condIva,
            'is_active' => $isActive,
        ];

        if ($id === null) {
            $newId = $this->repo->insert($data);
            return ['ok' => true, 'errors' => [], 'id' => $newId];
        }

        $this->repo->update($id, $data);
        return ['ok' => true, 'errors' => [], 'id' => $id];
    }

    public function delete(int $id): bool
    {
        return $this->repo->softDelete($id);
    }
}

