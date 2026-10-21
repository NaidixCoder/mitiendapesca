<?php
namespace App\Controllers\Admin;

use App\Repositories\Accounting\AccountingClosureRepository;
use App\Services\Accounting\FacturacionAlertasService;
use App\Services\Admin\Facturacion\ViewModels\FacturacionControlIndexViewModel;

/**
 * Dashboard de Control Fiscal.
 * No hace cálculos contables nuevos: solo resume y navega.
 */
final class FacturacionControlController extends BaseController
{
    public function index(): void
    {
        $alertas = (new FacturacionAlertasService(db()))->build();
        $closures = (new AccountingClosureRepository(db()))->listLatest(6);

        $this->view('admin/facturacion/control/index', [
            'alertsCounts' => [
                'loaded_not_validated' => count($alertas['loaded_not_validated'] ?? []),
                'purchases_missing_attachments' => count($alertas['purchases_missing_attachments'] ?? []),
                'unclosed_periods' => count($alertas['unclosed_periods'] ?? []),
            ],
            'latestClosures' => $closures,
            'control_vm' => (new FacturacionControlIndexViewModel())->build([
                'loaded_not_validated' => count($alertas['loaded_not_validated'] ?? []),
                'purchases_missing_attachments' => count($alertas['purchases_missing_attachments'] ?? []),
                'unclosed_periods' => count($alertas['unclosed_periods'] ?? []),
            ], is_array($closures) ? $closures : []),
        ]);
    }
}
