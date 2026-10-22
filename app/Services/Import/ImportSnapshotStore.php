<?php
declare(strict_types=1);

namespace App\Services\Import;

use Closure;
use RuntimeException;
use Throwable;

/**
 * Publicación fail-closed de snapshot v3 ANTES de confirmar SQL.
 *
 * En el NTFS usado por la instalación Windows, link() publica un hard link
 * atómico y no permite sobrescribir un destino existente (a diferencia de
 * ciertos rename()). El temporal permanece para comprobar la identidad en
 * caso de compensación. MySQL + filesystem NO forman una transacción ACID.
 */
class ImportSnapshotStore
{
    private ?string $staged = null;
    private ?string $destination = null;
    private ?string $digest = null;
    private ?int $inode = null;
    private ?int $byteCount = null;
    private bool $published = false;
    private ?Closure $fault = null;
    private ?string $allowedDirectory = null;

    /** Callback opcional de fault injection solo para pruebas de aislamiento. */
    public function __construct(?callable $fault = null, ?string $allowedDirectory = null)
    {
        $this->fault = $fault === null ? null : Closure::fromCallable($fault);
        $this->allowedDirectory = $allowedDirectory;
    }

    private function checkpoint(string $phase): void
    {
        if ($this->fault !== null) ($this->fault)($phase, $this);
    }

    private function destinationFor(string $csvPath): string
    {
        if ($csvPath === '' || !preg_match('/\.csv$/', $csvPath)) {
            throw new RuntimeException('El origen del snapshot no es CSV.');
        }
        $file = realpath($csvPath);
        if ($file === false || !is_file($file)) {
            throw new RuntimeException('CSV de importación inexistente.');
        }
        $dir = realpath(dirname($file));
        if ($dir === false || !is_dir($dir)) {
            throw new RuntimeException('Directorio de importaciones inválido.');
        }
        // En el servidor web se exige storage/imports. En suites CLI sin
        // bootstrap, el test instala su propio directorio descartable.
        if ($this->allowedDirectory !== null || function_exists('storage_path')) {
            $allowed = realpath($this->allowedDirectory ?? (string)storage_path('imports'));
            if ($allowed === false || strcasecmp(rtrim($allowed, '\\/'), rtrim($dir, '\\/')) !== 0) {
                throw new RuntimeException('CSV fuera del directorio autorizado de importaciones.');
            }
        }
        return substr($file, 0, -4) . '_rollback.json';
    }

    /** @param array<string,mixed> $payload */
    public function prepare(string $csvPath, int $jobId, array $payload): void
    {
        if ($this->staged !== null || $this->destination !== null) {
            throw new RuntimeException('Esta instancia ya preparó un snapshot.');
        }
        if ($jobId <= 0 || (int)($payload['job_id'] ?? 0) !== $jobId
            || !ImportRevertService::isSnapshotSafe($payload)) {
            throw new RuntimeException('Snapshot v3 incompleto, vacío o no verificable.');
        }
        $destination = $this->destinationFor($csvPath);
        if (file_exists($destination) || is_link($destination)) {
            throw new RuntimeException('Ya existe un punto de recuperación; se prohíbe reemplazarlo.');
        }
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if (!is_string($json) || $json === '') throw new RuntimeException('No se pudo serializar el snapshot.');
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded) || !ImportRevertService::isSnapshotSafe($decoded)
            || (int)($decoded['job_id'] ?? 0) !== $jobId) {
            throw new RuntimeException('Error de integridad JSON del snapshot.');
        }
        $this->destination = $destination;
        $this->byteCount = strlen($json);
        $this->digest = hash('sha256', $json);

        // El temporal es exclusivo y está en el MISMO directorio/volumen.
        $dir = dirname($destination);
        for ($i = 0; $i < 5; $i++) {
            $temp = $dir . DIRECTORY_SEPARATOR . '.snapshot_' . $jobId . '_' . bin2hex(random_bytes(16)) . '.tmp';
            $stream = @fopen($temp, 'x+b');
            if ($stream !== false) { $this->staged = $temp; break; }
        }
        if ($this->staged === null) throw new RuntimeException('No se pudo reservar el archivo temporal exclusivo.');
        try {
            @chmod($this->staged, 0600);
            $this->checkpoint('open');
            $offset = 0;
            while ($offset < $this->byteCount) {
                $this->checkpoint('write');
                $n = @fwrite($stream, substr($json, $offset));
                if (!is_int($n) || $n <= 0) throw new RuntimeException('Escritura parcial del snapshot.');
                $offset += $n;
            }
            $this->checkpoint('flush');
            if (!@fflush($stream)) throw new RuntimeException('No se pudo vaciar el buffer del snapshot.');
            // Si la plataforma ofrece fsync, DEBE funcionar; no se omite el error.
            $this->checkpoint('sync');
            if (function_exists('fsync') && !@fsync($stream)) {
                throw new RuntimeException('Falló la sincronización a disco del snapshot.');
            }
            if (!fclose($stream)) throw new RuntimeException('No se pudo cerrar el snapshot temporal.');
            $stream = false;
            clearstatcache(true, $this->staged);
            if (@filesize($this->staged) !== $this->byteCount
                || !hash_equals($this->digest, (string)@hash_file('sha256', $this->staged))) {
                throw new RuntimeException('Bytes o contenido incompletos en el temporal.');
            }
            $check = json_decode((string)file_get_contents($this->staged), true, 512, JSON_THROW_ON_ERROR);
            if (!ImportRevertService::isSnapshotSafe($check)
                || (int)$check['job_id'] !== $jobId) {
                throw new RuntimeException('El archivo temporal no contiene un snapshot v3 válido.');
            }
            $inode = @fileinode($this->staged);
            if (!is_int($inode) || $inode <= 0) {
                throw new RuntimeException('No se pudo identificar unívocamente el archivo temporal.');
            }
            $this->inode = $inode;
            $this->checkpoint('verify');
        } finally {
            if (is_resource($stream)) @fclose($stream);
        }
    }

    public function publish(): void
    {
        if ($this->staged === null || $this->destination === null || $this->inode === null) {
            throw new RuntimeException('Snapshot temporal no preparado.');
        }
        $this->checkpoint('publish');
        if (file_exists($this->destination) || is_link($this->destination)) {
            throw new RuntimeException('Colisión de punto de recuperación: no se reemplaza.');
        }
        // link() crea la referencia destino como operación atómica NO-CLOBBER
        // sobre NTFS. Si no hay soporte, se cancela SQL (sin rename inseguro).
        if (!@link($this->staged, $this->destination)) {
            throw new RuntimeException('No fue posible publicar el snapshot sin sobrescritura.');
        }
        $this->published = true;
        $this->checkpoint('published');
        clearstatcache(true, $this->destination);
        if (@fileinode($this->destination) !== $this->inode
            || @filesize($this->destination) !== $this->byteCount
            || !hash_equals((string)$this->digest, (string)@hash_file('sha256', $this->destination))) {
            throw new RuntimeException('La publicación no coincide con el temporal.');
        }
    }

    /**
     * Antes de COMMIT se usa también como barrera inyectable de fallos.
     * Nunca se llama commit sin publicar y verificar el contenido.
     */
    public function beforeCommit(): void
    {
        if (!$this->published) throw new RuntimeException('Snapshot no publicado.');
        $this->checkpoint('before_commit');
    }

    /** SQL confirmado: retirar solo el nombre temporal, mantener destino. */
    public function committed(): void
    {
        $this->removeStaged();
    }

    /**
     * Solo después de ROLLBACK SQL confirmado: borrar destino SI es el mismo
     * inode y hash del temporal propio. No tocar archivos ajenos.
     */
    public function aborted(): void
    {
        if ($this->published && $this->destination !== null && $this->staged !== null) {
            clearstatcache(true, $this->destination);
            // El temporal sigue abierto por nombre: el inode demuestra identidad
            // aunque el contenido haya sufrido una escritura parcial/corrupción.
            if (is_file($this->destination)
                && $this->inode !== null
                && @fileinode($this->staged) === $this->inode
                && @fileinode($this->destination) === $this->inode) {
                @unlink($this->destination);
            }
        }
        $this->removeStaged();
    }

    /** Si COMMIT tuvo resultado incierto, conservar destino como evidencia. */
    public function uncertainCommit(): void
    {
        $this->removeStaged();
    }

    private function removeStaged(): void
    {
        if ($this->staged !== null && is_file($this->staged)) @unlink($this->staged);
        $this->staged = null;
    }
}
