<?php
namespace App\Controllers\Admin;

use App\Services\Accounting\FacturacionAlertasService;
use App\Services\Admin\Facturacion\ViewModels\FacturacionAlertasIndexViewModel;

final class FacturacionAlertasController extends BaseController
{
    public function index(): void
    {
        $data = (new FacturacionAlertasService(db()))->build();
        $vm = new FacturacionAlertasIndexViewModel();

        $this->view('admin/facturacion/alertas/index', $vm->build([
            'loadedNotValidated' => $data['loaded_not_validated'],
            'purchasesMissingAttachments' => $data['purchases_missing_attachments'],
            'unclosedPeriods' => $data['unclosed_periods'],
        ]));
    }
}

