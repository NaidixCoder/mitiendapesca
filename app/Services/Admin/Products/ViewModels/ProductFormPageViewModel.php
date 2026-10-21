<?php
namespace App\Services\Admin\Products\ViewModels;

final class ProductFormPageViewModel
{
    /**
     * @param array<string,mixed> $ctx
     * @param array<string,mixed> $query
     * @param string $error
     * @return array<string,mixed>
     */
    public function build(array $ctx, array $query, string $error = ''): array
    {
        $product = is_array($ctx['product'] ?? null) ? $ctx['product'] : [];
        $wizardStatus = is_array($ctx['wizardStatus'] ?? null) ? $ctx['wizardStatus'] : null;
        $variantsCount = (int)($ctx['variantsCount'] ?? 0);
        $id = (int)($product['id'] ?? 0);

        $saleMode = (string)($product['sale_mode'] ?? 'simple');
        $variantsEnabled = ($id > 0) && ($saleMode === 'variants' || $variantsCount > 0);

        $activeTab = (string)($query['tab'] ?? 'basicos');
        if ($activeTab === 'rel') {
            $activeTab = 'seo';
        }

        $tabMeta = [
            'basicos' => ['step' => 1, 'label' => 'Basicos'],
            'inventario' => ['step' => 2, 'label' => 'Venta'],
            'variantes' => ['step' => 3, 'label' => 'Variantes'],
            'imagenes' => ['step' => 4, 'label' => 'Imagenes'],
            'seo' => ['step' => 5, 'label' => 'SEO'],
            'publicacion' => ['step' => 6, 'label' => 'Publicacion'],
        ];
        if (!isset($tabMeta[$activeTab])) {
            $activeTab = 'basicos';
        }
        if ($activeTab === 'variantes' && !$variantsEnabled) {
            $activeTab = 'inventario';
        }

        $prevMap = [
            'basicos' => null,
            'inventario' => 'basicos',
            'variantes' => 'inventario',
            'imagenes' => 'variantes',
            'seo' => 'imagenes',
            'publicacion' => 'seo',
        ];
        $nextMap = [
            'basicos' => 'inventario',
            'inventario' => 'variantes',
            'variantes' => 'imagenes',
            'imagenes' => 'seo',
            'seo' => 'publicacion',
            'publicacion' => null,
        ];

        $tabs = [];
        foreach ($tabMeta as $key => $meta) {
            $ok = null;
            if (is_array($wizardStatus) && isset($wizardStatus['tabs'][$key])) {
                $ok = (bool)($wizardStatus['tabs'][$key]['ok'] ?? false);
            }
            $tabs[] = [
                'key' => $key,
                'label' => (string)$meta['label'],
                'active' => ($key === $activeTab),
                'visible' => ($key !== 'variantes' || $variantsEnabled),
                'badge_state' => $ok === null ? '' : ($ok ? 'ok' : 'pending'),
                'badge_title' => $ok === null ? '' : ($ok ? 'Completo' : 'Pendiente'),
                'badge_class' => $ok === null ? '' : ($ok ? 'bg-emerald-500' : 'bg-amber-500'),
            ];
        }

        $stepNumber = (int)($tabMeta[$activeTab]['step'] ?? 1);
        $stepLabel = (string)($tabMeta[$activeTab]['label'] ?? 'Basicos');
        $totalSteps = count($tabMeta);
        $progress = (int)round(($stepNumber / max(1, $totalSteps)) * 100);

        $auditUrl = '';
        if ($id > 0) {
            $auditUrl = base_url('/admin/audit?' . http_build_query([
                'tipo' => 'product',
                'q' => '#' . $id,
                'range' => '30',
            ]));
        }

        return [
            'product_form_ui' => [
                'id' => $id,
                'is_edit' => $id > 0,
                'title' => $id > 0 ? 'Editar producto' : 'Nuevo producto',
                'subtitle' => $id > 0 ? ('ID #' . $id) : 'Completa los datos y guarda',
                'variants_enabled' => $variantsEnabled,
                'active_tab' => $activeTab,
                'step_number' => $stepNumber,
                'step_label' => $stepLabel,
                'total_steps' => $totalSteps,
                'progress_percent' => $progress,
                'prev_tab' => $prevMap[$activeTab] ?? null,
                'next_tab' => $nextMap[$activeTab] ?? null,
                'tabs' => $tabs,
                'audit_url' => $auditUrl,
                'error' => $error,
                'next_url' => (string)($query['next'] ?? '/admin/products'),
            ],
        ];
    }
}




