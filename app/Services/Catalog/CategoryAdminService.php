<?php
namespace App\Services\Catalog;

use PDO;
use Throwable;

class CategoryAdminService
{
  public function __construct(private PDO $db) {}

  /**
   * Alterna is_active.
   * - Si va a desactivar: desactiva también todos los descendientes.
   * - Si va a activar: activa solo la categoría objetivo.
   */
  public function toggleActiveCascade(int $categoryId): array
  {
    try {
      $st = $this->db->prepare('SELECT is_active FROM categories WHERE id=?');
      $st->execute([$categoryId]);
      $val = $st->fetchColumn();
      if ($val === false) {
        return ['ok' => false, 'error' => 'Categoría no encontrada.'];
      }

      $from = (int)$val;
      $to = $from ? 0 : 1;

      $cascadeIds = [$categoryId];
      $childrenCount = 0;
      if ($to === 0) {
        $cascadeIds = $this->getDescendantIds($categoryId);
        $childrenCount = max(0, count($cascadeIds) - 1);
      }

      $productsCount = $this->countProductsInCategories($cascadeIds);

      if ($to === 0) {
        $this->updateIsActiveForIds($cascadeIds, 0);
      } else {
        $ok = $this->db->prepare('UPDATE categories SET is_active=1 WHERE id=?')->execute([$categoryId]);
        if (!$ok) {
          return ['ok' => false, 'error' => 'No se pudo activar.'];
        }
      }

      return [
        'ok' => true,
        'from' => $from,
        'to' => $to,
        'cascade_ids' => array_values($cascadeIds),
        'children_count' => $childrenCount,
        'products_count' => $productsCount,
      ];
    } catch (Throwable $e) {
      if (function_exists('log_error')) log_error('CategoryAdminService toggle: ' . $e->getMessage());
      return ['ok' => false, 'error' => 'Error inesperado.'];
    }
  }

  private function updateIsActiveForIds(array $ids, int $active): void
  {
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (empty($ids)) return;

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $params = array_merge([$active], $ids);
    $st = $this->db->prepare("UPDATE categories SET is_active=? WHERE id IN ($placeholders)");
    $st->execute($params);
  }

  private function countProductsInCategories(array $categoryIds): int
  {
    $ids = array_values(array_unique(array_map('intval', $categoryIds)));
    if (empty($ids)) return 0;

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $st = $this->db->prepare("SELECT COUNT(*) FROM products WHERE category_id IN ($placeholders)");
    $st->execute($ids);
    return (int)$st->fetchColumn();
  }

  /** @return int[] */
  private function getDescendantIds(int $rootId): array
  {
    $rows = $this->db->query('SELECT id,parent_id FROM categories')->fetchAll(PDO::FETCH_ASSOC);
    $children = [];
    foreach ($rows as $r) {
      $pid = ($r['parent_id'] === null) ? 0 : (int)$r['parent_id'];
      $children[$pid][] = (int)$r['id'];
    }

    $out = [];
    $stack = [$rootId];
    while (!empty($stack)) {
      $id = array_pop($stack);
      if (in_array($id, $out, true)) continue;
      $out[] = $id;
      foreach (($children[$id] ?? []) as $cid) {
        $stack[] = $cid;
      }
    }
    return $out;
  }
}
