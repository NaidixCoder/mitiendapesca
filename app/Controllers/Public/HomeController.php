<?php
namespace App\Controllers\Public;

use App\Controllers\BaseController;
use App\Services\Public\HomePageViewService;

final class HomeController extends BaseController
{
    public function index(): void
    {
        $data = (new HomePageViewService())->build();
        $data['pageId'] = 'home';
        render_page('public/home', $data);
    }
}
