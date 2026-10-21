<?php
$pageTitle = 'Atributos';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';

$indexView = is_array($attributes_index_view ?? null) ? $attributes_index_view : [];
extract($indexView, EXTR_SKIP);
?>
<section class="admin-surface">
  <div class="av3-wrap av3-flow-4">

    <?php require BASE_PATH.'/app/Views/partials/admin/attributes/index/header.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/attributes/index/flashes.php'; ?>

    <div class="av3-grid av3-lg-grid-cols-2 av3-gap-4">
      <?php require BASE_PATH.'/app/Views/partials/admin/attributes/index/attributes_card.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/attributes/index/sets_card.php'; ?>
    </div>

  </div>
</section>
