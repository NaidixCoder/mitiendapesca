from pathlib import Path
R=Path(r'C:\xampp\htdocs\mi-pesca')
p=R/'app/Views/partials/admin/page_header.php'
s=p.read_text(encoding='utf8')
s=s.replace('?>\n<header class="av3-page-header',"""if (!empty($GLOBALS['_admin_master_capture'])) { ob_start(); }
?>
<header class="av3-page-header""",1)
s=s.replace('<?php unset($adminHeaderTitle,$adminHeaderDescription,$adminHeaderBreadcrumbs,$adminHeaderActions,$adminHeaderClass); ?>',"""<?php
if (!empty($GLOBALS['_admin_master_capture'])) {
    $GLOBALS['_admin_master_header'] = ob_get_clean();
}
unset($adminHeaderTitle,$adminHeaderDescription,$adminHeaderBreadcrumbs,$adminHeaderActions,$adminHeaderClass);
?>""")
p.write_text(s,encoding='utf8')
p=R/'app/Views/partials/admin/subnav.php'
s=p.read_text(encoding='utf8')
s=s.replace("require BASE_PATH . '/app/Views/partials/admin/sidebar_v2.php';","if (empty($GLOBALS['_admin_master_capture'])) {\n    require BASE_PATH . '/app/Views/partials/admin/sidebar_v2.php';\n}")
p.write_text(s,encoding='utf8')
p=R/'app/Views/layouts/admin.php';p.parent.mkdir(exist_ok=True)
p.write_text("""<?php
/** Layout maestro de administración. Recibe $adminMasterContent y el encabezado registrado. */
?>
<div class="av3-master-shell">
  <?php require BASE_PATH . '/app/Views/partials/admin/sidebar_v2.php'; ?>
  <div class="av3-master-workspace">
    <?php if (!empty($GLOBALS['_admin_master_header'])): ?>
      <div class="av3-master-header">
        <?= $GLOBALS['_admin_master_header'] ?>
      </div>
    <?php endif; ?>
    <div class="av3-master-content"><?= $adminMasterContent ?></div>
  </div>
</div>
""",encoding='utf8')
p=R/'app/Support/helpers.php'
s=p.read_text(encoding='utf8')
old="""        if (is_file($pageFile)) include $pageFile;
        else {
            http_response_code(404);
            echo '<main class="container mx-auto p-6"><h1>Vista no encontrada</h1><p>' . e($name) . '</p></main>';
        }
        if (!(bool)($hideFooter ?? false) && is_file($footer)) include $footer;"""
new="""        if ($isAdminPage && is_file($pageFile)) {
            $GLOBALS['_admin_master_capture'] = true;
            $GLOBALS['_admin_master_header'] = '';
            ob_start();
            try {
                include $pageFile;
                $adminMasterContent = ob_get_clean();
            } catch (\\\\Throwable $adminMasterError) {
                ob_end_clean();
                unset($GLOBALS['_admin_master_capture'], $GLOBALS['_admin_master_header']);
                throw $adminMasterError;
            }
            unset($GLOBALS['_admin_master_capture']);
            include $viewsDir . '/layouts/admin.php';
            unset($GLOBALS['_admin_master_header']);
        } elseif (is_file($pageFile)) include $pageFile;
        else {
            http_response_code(404);
            echo '<main class="container mx-auto p-6"><h1>Vista no encontrada</h1><p>' . e($name) . '</p></main>';
        }
        if (!(bool)($hideFooter ?? false) && is_file($footer)) include $footer;"""
assert old in s
s=s.replace(old,new,1).replace('catch (\\\\Throwable $adminMasterError)', 'catch (\\Throwable $adminMasterError)')
p.write_text(s,encoding='utf8')
print('MASTER_LAYOUT_INSTALLED')
