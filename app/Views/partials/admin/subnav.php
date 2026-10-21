<?php
// Punto unico de navegacion admin v2 (sidebar).
// Evita duplicacion si el partial se incluye mas de una vez (mismo request).
if (defined('_ADMIN_SUBNAV_RENDERED')) { return; }
define('_ADMIN_SUBNAV_RENDERED', true);

if (empty($GLOBALS['_admin_master_capture'])) {
    require BASE_PATH . '/app/Views/partials/admin/sidebar_v2.php';
}
