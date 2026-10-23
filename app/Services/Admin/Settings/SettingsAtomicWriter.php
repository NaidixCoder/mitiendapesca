<?php
namespace App\Services\Admin\Settings;

use App\Repositories\Settings\AppSettingsRepository;
use PDO;
use Throwable;

/** Agrupa las escrituras de una operación de Ajustes sin cambiar las claves persistidas. */
final class SettingsAtomicWriter
{
    /** @var array<string,string> */
    private array $pending = [];

    public function __construct(private PDO $db, private AppSettingsRepository $repository) {}

    public function set(string $key, string $value): bool
    {
        $this->pending[$key] = $value;
        return true;
    }

    public function commit(?callable $also = null): bool
    {
        if ($this->db->inTransaction()) {
            // Evita confirmar transacciones cuyo propietario sea otro servicio.
            return false;
        }
        try {
            $this->db->beginTransaction();
            foreach ($this->pending as $key => $value) {
                if (!$this->repository->set($key, $value)) {
                    throw new \RuntimeException('Error al guardar ajustes.');
                }
            }
            if ($also !== null) {
                $also();
            }
            $this->db->commit();
            $this->pending = [];
            return true;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }
}
