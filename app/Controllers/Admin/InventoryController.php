<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Repositories\Admin\InventoryItemsRepository;
use App\Services\Admin\Inventory\ViewModels\InventoryIndexViewModel;

final class InventoryController extends BaseController
{
    public function index(): void
    {
        $q = trim((string)($_GET['q'] ?? ''));
        $repo = new InventoryItemsRepository(db());
        $items = $repo->listItems($q);

        $vm = new InventoryIndexViewModel();
        render_page('admin/inventario/index', $vm->build($q, $items));
    }
}
