<?php
declare(strict_types=1);

namespace App\Services\Admin\Brands;

use App\Services\Seo\BrandSeoBlocksService;
use App\Services\Seo\SeoManagerService;
use DomainException;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Única unidad de trabajo SQL para la marca, redirects, settings y bloques.
 * El archivo nuevo se coloca antes por el Controller y se compensa si falla.
 * Nunca administra ni elimina archivos dentro de una transacción SQL.
 */
final class BrandSaveService
{
    public function __construct(private PDO $db) {}

    /**
     * @param array{name:string,slug:string,sort:int,cover_image:string,meta_title:?string,meta_description:?string,seo_text:?string} $brand
     * @param array<string,string> $settings
     * @param array<int,string> $bullets
     * @param array<int,array{label:string,value:string}> $specs
     * @param array<int,array{q:string,a:string}> $faq
     */
    public function save(int $id, array $brand, array $settings, array $bullets, array $specs, array $faq): int
    {
        if ($this->db->inTransaction()) {
            throw new RuntimeException('La transacción de marcas debe ser independiente.');
        }

        $beforeMode = $this->db->getAttribute(PDO::ATTR_ERRMODE);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        try {
            if (!$this->db->beginTransaction()) {
                throw new RuntimeException('No se pudo iniciar la transacción de marcas.');
            }

            $previousSlug = '';
            if ($id > 0) {
                $stmt = $this->db->prepare('SELECT slug FROM brands WHERE id = ? FOR UPDATE');
                $stmt->execute([$id]);
                $old = $stmt->fetchColumn();
                if ($old === false) throw new DomainException('La marca ya no existe.');
                $previousSlug = (string)$old;
            }

            // El historial es único por (entity_type,slug); no apropiarse de
            // redirects históricos de otra marca por un upsert silencioso.
            $history = $this->db->prepare(
                "SELECT entity_id FROM seo_slug_history WHERE entity_type = 'brand' AND slug = ? LIMIT 1 FOR UPDATE"
            );
            $history->execute([$brand['slug']]);
            $owner = $history->fetchColumn();
            if ($owner !== false && (int)$owner !== $id) {
                throw new DomainException('El slug pertenece al historial SEO de otra marca.');
            }

            $values = [
                $brand['name'], $brand['slug'], $brand['meta_title'],
                $brand['meta_description'], $brand['seo_text'],
                $brand['sort'], $brand['cover_image'],
            ];
            if ($id > 0) {
                $stmt = $this->db->prepare(
                    'UPDATE brands SET name=?,slug=?,meta_title=?,meta_description=?,seo_text=?,sort=?,cover_image=? WHERE id=?'
                );
                $stmt->execute([...$values, $id]);
            } else {
                $stmt = $this->db->prepare(
                    'INSERT INTO brands (name,slug,meta_title,meta_description,seo_text,sort,cover_image) VALUES (?,?,?,?,?,?,?)'
                );
                $stmt->execute($values);
                $id = (int)$this->db->lastInsertId();
                if ($id <= 0) throw new RuntimeException('No se creó el identificador de marca.');
            }

            $seo = new SeoManagerService($this->db);
            $seo->registerSlug('brand', $id, $brand['slug']);
            if ($previousSlug !== '' && $previousSlug !== $brand['slug']) {
                $seo->recordBrandSlugChange($id, $previousSlug, $brand['slug']);
            }

            $seo->saveSettings('brand', $id, $settings);
            (new BrandSeoBlocksService($this->db))->saveForBrand($id, $bullets, $specs, $faq);

            if (!$this->db->commit()) throw new RuntimeException('Falló el COMMIT de la marca.');
            return $id;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        } finally {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, $beforeMode);
        }
    }
}
