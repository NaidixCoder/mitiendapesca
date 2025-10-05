<?php
namespace App\Http\Requests;

use PDO;

class ProductRequest
{
    public function collect(): array {
        require_once base_path('app/Support/str.php');

        $sku   = trim($_POST['sku'] ?? '');
        $name  = trim($_POST['name'] ?? '');
        $slug  = trim($_POST['slug'] ?? '');
        $slug  = $slug !== '' ? $slug : str_slug($name);

        $brand = $_POST['brand_id'] !== '' ? (int)$_POST['brand_id'] : null;
        $cat   = $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;

        $short = trim($_POST['short_desc'] ?? '');
        $long  = trim($_POST['long_desc'] ?? '');

        $price = (int)($_POST['price'] ?? 0);
        $listp = ($_POST['list_price'] ?? '') !== '' ? (int)$_POST['list_price'] : null;
        $cost  = ($_POST['cost'] ?? '') !== '' ? (int)$_POST['cost'] : null;
        $bar   = trim($_POST['barcode'] ?? '');

        $active    = isset($_POST['is_active']) ? 1 : 0;
        $metaTitle = trim($_POST['meta_title'] ?? '');
        $metaDesc  = trim($_POST['meta_description'] ?? '');

        return compact(
            'sku','name','slug','brand','cat','short','long','price','listp','cost','bar','active','metaTitle','metaDesc'
        );
    }

    public function validate(PDO $db, array $d, int $id): array {
        $errs = [];
        if ($d['sku'] === '')   $errs[] = 'SKU requerido';
        if ($d['name'] === '')  $errs[] = 'Nombre requerido';
        if ($d['slug'] === '')  $errs[] = 'Slug requerido';
        if (!$d['cat'])         $errs[] = 'Categoría requerida';
        if ($d['price'] < 0)    $errs[] = 'Precio inválido';

        // Unicidad
        $st = $db->prepare("SELECT COUNT(*) FROM products WHERE (sku=? OR slug=?) AND id<>?");
        $st->execute([$d['sku'], $d['slug'], $id]);
        if ((int)$st->fetchColumn() > 0) $errs[] = 'SKU o Slug ya existen';

        return $errs;
    }
}
