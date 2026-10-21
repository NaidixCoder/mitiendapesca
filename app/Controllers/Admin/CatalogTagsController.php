<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Repositories\Admin\CatalogTagsRepository;

class CatalogTagsController extends BaseController
{
    public function index(): void
    {
        $repo = new CatalogTagsRepository(db());
        $groupId = (int)($_GET['group_id'] ?? 0);
        $tagId = (int)($_GET['tag_id'] ?? 0);

        render_page('admin/catalog_tags/index', [
            'pageId' => 'admin-catalog-tags',
            'groups' => $repo->listGroups(),
            'tags' => $repo->listTags(),
            'groupRow' => $repo->findGroupById($groupId),
            'tagRow' => $repo->findTagById($tagId),
        ]);
    }

    public function saveGroup(): void
    {
        verify_csrf_or_abort();
        $repo = new CatalogTagsRepository(db());

        $id = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $slugIn = trim((string)($_POST['slug'] ?? ''));
        $slug = $this->slugify($slugIn !== '' ? $slugIn : $name);
        $sort = max(0, (int)($_POST['sort'] ?? 0));
        $isActive = (($_POST['is_active'] ?? '0') === '1') ? 1 : 0;

        if ($name === '') {
            flash('error', 'Completa el nombre del grupo.');
            redirect(url('/admin/catalog-tags' . ($id > 0 ? '?group_id=' . $id : '')));
            return;
        }

        try {
            $repo->saveGroup($id, [
                'name' => $name,
                'slug' => $slug,
                'sort' => $sort,
                'is_active' => $isActive,
            ]);
            flash('ok', $id > 0 ? 'Grupo actualizado.' : 'Grupo creado.');
        } catch (\Throwable $e) {
            flash('error', 'No se pudo guardar el grupo: ' . $e->getMessage());
        }

        redirect(url('/admin/catalog-tags'));
    }

    public function saveTag(): void
    {
        verify_csrf_or_abort();
        $repo = new CatalogTagsRepository(db());

        $id = (int)($_POST['id'] ?? 0);
        $groupId = (int)($_POST['group_id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $slugIn = trim((string)($_POST['slug'] ?? ''));
        $slug = $this->slugify($slugIn !== '' ? $slugIn : $name);
        $sort = max(0, (int)($_POST['sort'] ?? 0));
        $isActive = (($_POST['is_active'] ?? '0') === '1') ? 1 : 0;

        if ($groupId <= 0 || $name === '') {
            flash('error', 'Completa grupo y nombre del tag.');
            redirect(url('/admin/catalog-tags' . ($id > 0 ? '?tag_id=' . $id : '')));
            return;
        }

        try {
            $repo->saveTag($id, [
                'group_id' => $groupId,
                'name' => $name,
                'slug' => $slug,
                'sort' => $sort,
                'is_active' => $isActive,
            ]);
            flash('ok', $id > 0 ? 'Tag actualizado.' : 'Tag creado.');
        } catch (\Throwable $e) {
            flash('error', 'No se pudo guardar el tag: ' . $e->getMessage());
        }

        redirect(url('/admin/catalog-tags'));
    }

    private function slugify(string $value): string
    {
        $t = function_exists('iconv') ? @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) : $value;
        $t = strtolower($t ?? '');
        $t = preg_replace('/[^a-z0-9]+/', '-', $t);
        return trim((string)$t, '-');
    }
}
