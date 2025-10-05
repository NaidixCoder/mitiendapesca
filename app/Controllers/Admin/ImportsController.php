<?php
namespace App\Controllers\Admin;

class ImportsController {
    public function index()     { require_admin(); render_page('admin/imports/index'); }
    public function upload()    { require_admin(); render_page('admin/imports/upload'); }
    public function uploadPost(){ require_admin(); require BASE_PATH.'/app/Views/pages/admin/imports/upload_post.php'; }
}
