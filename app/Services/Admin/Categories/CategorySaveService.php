<?php
declare(strict_types=1);
namespace App\Services\Admin\Categories;

use App\Services\Catalog\CategoryAdminService;
use App\Services\Seo\SeoManagerService;
use App\Services\Seo\CategorySeoBlocksService;
use PDO;
use Throwable;

/**
 * Unidad de trabajo propia de Categorías. Los servicios SEO compartidos usan
 * la misma PDO; no hacen commit ni silencian errores de persistencia.
 * No publica archivos definitivamente hasta superar las etapas SQL.
 */
final class CategorySaveService
{
    public function __construct(private PDO $db) {}

    /**
     * @param array<string,mixed> $category  Campos ya normalizados/validados por Controller.
     * @param array<string,mixed> $seoSettings
     * @param array{bullets:array,specs:array,faq:array} $blocks
     * @param array{path:string,staged_path:string}|null $upload
     * @return array<string,mixed>
     * @throws \DomainException para problemas funcionales
     * @throws Throwable ante fallos de almacenamiento, para rollback del caller HTTP
     */
    public function save(array $category, array $seoSettings, array $blocks, ?array $upload = null): array
    {
        if ($this->db->inTransaction()) throw new \LogicException('CategorySaveService requiere controlar la transacción.');
        $id = (int)$category['id'];
        $parent = $category['parent_id'];
        $active = (int)$category['is_active'];
        $slug = (string)$category['slug'];
        $prevSlug = '';
        $beforeActive = null;
        $visibility = [];
        $publishedFile = null;
        $stage = $upload['staged_path'] ?? null;
        $operation = $id > 0 ? 'update' : 'insert';
        $destination = null;

        try {
            $this->db->beginTransaction();
            $guard = new CategoryHierarchyGuard($this->db);
            $tree = $guard->snapshot(true);
            if ($id > 0 && !isset($tree[$id])) {
                throw new \DomainException('La categoría que intentás editar ya no existe.');
            }
            $wasParent = $id > 0 ? $tree[$id]['parent_id'] : null;
            $beforeActive = $id > 0 ? $tree[$id]['is_active'] : null;
            $projected = $tree;
            if ($id > 0 && $beforeActive === 1 && $active === 0) {
                foreach (array_merge([$id], $guard->descendantIdsFromSnapshot($id, $tree)) as $nodeId) {
                    $projected[$nodeId]['is_active'] = 0;
                }
            }
            if ($id === 0 || $parent !== $wasParent || $active !== $beforeActive) {
                $error = $guard->validatePlacement($id, $parent, $active, $projected);
                if ($error !== null) throw new \DomainException($error);
            }
            $slugCheck = $this->db->prepare('SELECT id FROM categories WHERE slug=? AND id<>? LIMIT 1');
            $slugCheck->execute([$slug, $id]);
            if ($slugCheck->fetchColumn() !== false) {
                throw new \DomainException('Ya existe una categoría con ese slug.');
            }
            if ($id > 0) {
                $prior = $this->db->prepare('SELECT slug FROM categories WHERE id=?');
                $prior->execute([$id]);
                $prevSlug = (string)$prior->fetchColumn();
            }

            // Protección contra SeoSlugHistoryRepository::add() que utiliza
            // ON DUPLICATE KEY UPDATE entity_id=VALUES(entity_id).
            // El lock se ejecuta dentro de la transacción, antes del API SEO.
            foreach (array_values(array_unique(array_filter([$prevSlug, $slug], static fn($v) => $v !== ''))) as $historicalSlug) {
                $history = $this->db->prepare(
                    "SELECT entity_id FROM seo_slug_history WHERE entity_type='category' AND slug=? FOR UPDATE"
                );
                $history->execute([$historicalSlug]);
                $historicalOwner = $history->fetchColumn();
                if ($historicalOwner !== false && (int)$historicalOwner !== $id) {
                    throw new \DomainException('El slug está reservado en el historial SEO de otra categoría.');
                }
            }

            if ($id > 0) {
                $st = $this->db->prepare(
                    'UPDATE categories SET name=?,slug=?,parent_id=?,meta_title=?,meta_description=?,seo_text=?,cover_image=?,sort=? WHERE id=?'
                );
                $st->execute([
                    $category['name'],$slug,$parent,$category['meta_title'],$category['meta_description'],
                    $category['seo_text'],$category['cover_image'],$category['sort'],$id,
                ]);
            } else {
                $st = $this->db->prepare(
                    'INSERT INTO categories (name,slug,parent_id,meta_title,meta_description,seo_text,cover_image,sort,is_active) VALUES (?,?,?,?,?,?,?,?,0)'
                );
                $st->execute([
                    $category['name'],$slug,$parent,$category['meta_title'],$category['meta_description'],
                    $category['seo_text'],$category['cover_image'],$category['sort'],
                ]);
                $id = (int)$this->db->lastInsertId();
            }
            if ($id <= 0) throw new \RuntimeException('No se pudo guardar la categoría.');
            $visibility = (new CategoryAdminService($this->db))->setActive($id, $active);
            if (!($visibility['ok'] ?? false)) {
                throw new \DomainException((string)($visibility['error'] ?? 'Estado o jerarquía inválidos.'));
            }

            $manager = new SeoManagerService($this->db);
            $manager->registerSlug('category', $id, $slug);

            // La URL vigente nunca debe redirigir: especialmente A→B→A.
            // Este redirect es exclusivo del slug registrado a esta categoría.
            $canonicalPath = '/categoria?slug='.$slug;
            $cleanCanonical = $this->db->prepare('DELETE FROM seo_redirects WHERE from_path=?');
            $cleanCanonical->execute([$canonicalPath]);
            if ($prevSlug !== '' && $prevSlug !== $slug) {
                $manager->recordCategorySlugChange($id, $prevSlug, $slug);
            }
            $manager->saveSettings('category', $id, $seoSettings);
            (new CategorySeoBlocksService($this->db))->saveForCategory(
                $id, $blocks['bullets'] ?? [], $blocks['specs'] ?? [], $blocks['faq'] ?? []
            );

            // Staging privado → ubicación final, ANTES de commit.
            // Si SQL/commit falla se compensa solo el archivo recién creado.
            if ($upload !== null) {
                $relative = (string)($upload['path'] ?? '');
                if (!preg_match('~^/uploads/categories/[a-f0-9]{32}\.(?:png|jpg|webp)$~', $relative)) {
                    throw new \RuntimeException('Destino de portada inválido.');
                }
                $destination = base_path('public_html'.$relative);
                $folder = dirname($destination);
                if (!is_dir($folder) && !@mkdir($folder, 0775, true) && !is_dir($folder)) {
                    throw new \RuntimeException('No se pudo preparar la carpeta de portadas.');
                }
                if (!is_string($stage) || !is_file($stage) || is_file($destination) || !@rename($stage, $destination)) {
                    throw new \RuntimeException('No se pudo publicar la nueva portada.');
                }
                $publishedFile = $destination;
            }
            if (!$this->db->commit()) throw new \RuntimeException('No se pudo confirmar el guardado.');

            return [
                'id'=>$id, 'operation'=>$operation, 'from'=>$beforeActive, 'to'=>$active,
                'visibility'=>$visibility,
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            if ($publishedFile !== null && is_file($publishedFile)) @unlink($publishedFile);
            throw $e;
        } finally {
            // Archivo temporario de ESTE intento exclusivamente, si no fue promovido.
            if (is_string($stage) && is_file($stage)) @unlink($stage);
        }
    }
}
