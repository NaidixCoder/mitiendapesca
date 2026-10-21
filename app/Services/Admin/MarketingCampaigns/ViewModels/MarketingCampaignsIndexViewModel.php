<?php
namespace App\Services\Admin\MarketingCampaigns\ViewModels;

/** Prepara los datos del listado sin mezclar cálculos ni URLs en el HTML. */
final class MarketingCampaignsIndexViewModel
{
    public function build(array $rows, string $q, int $days, string $status, int $page, int $per, int $total, int $pages): array
    {
        $prepared = [];
        foreach ($rows as $r) {
            $id = (int)($r['id'] ?? 0);
            $slug = (string)($r['slug'] ?? '');
            $active = !empty($r['is_active']);
            $promoPath = '/promo/'.$slug;
            $prepared[] = [
                'id' => $id,
                'name' => (string)($r['name'] ?? ''),
                'slug' => $slug,
                'is_active' => $active,
                'state' => $active ? 'Activa' : 'Inactiva',
                'promo_path' => $promoPath,
                'promo_url' => url($promoPath),
                'destination' => (string)($r['destination_path'] ?? ''),
                'hits' => (int)($r['hits_30d'] ?? 0),
                'add_to_cart' => (int)($r['add_to_cart_30d'] ?? 0),
                'purchases' => (int)($r['purchases_30d'] ?? 0),
                'revenue_label' => money((int)($r['revenue_30d'] ?? 0)),
                'detail_url' => url('/admin/marketing/campanas/show?id='.$id.'&days='.$days),
                'edit_url' => url('/admin/marketing/campanas/edit?id='.$id),
            ];
        }

        $base = ['days' => $days, 'per' => $per];
        if ($q !== '') $base['q'] = $q;
        if ($status !== '') $base['status'] = $status;
        $href = static fn(array $extra): string => url('/admin/marketing/campanas?'.http_build_query(array_merge($base, $extra)));

        $tabs = [];
        foreach (['' => 'Todas', 'active' => 'Activas', 'inactive' => 'Inactivas'] as $key => $label) {
            $query = $base;
            if ($key === '') unset($query['status']);
            else $query['status'] = $key;
            unset($query['page']);
            $tabs[] = ['label' => $label, 'active' => $status === $key,
                'url' => url('/admin/marketing/campanas?'.http_build_query($query))];
        }

        $pagination = [];
        if ($pages > 1) {
            $pagination[] = ['label' => 'Anterior', 'active' => false, 'disabled' => $page <= 1,
                'url' => $href(['page' => max(1, $page-1)])];
            $start = max(1, $page-2);
            $end = min($pages, max(5, $page+2));
            $start = max(1, min($start, $end-4));
            for ($i = $start; $i <= $end; $i++) {
                $pagination[] = ['label' => (string)$i, 'active' => $i === $page, 'disabled' => false,
                    'url' => $href(['page' => $i])];
            }
            $pagination[] = ['label' => 'Siguiente', 'active' => false, 'disabled' => $page >= $pages,
                'url' => $href(['page' => min($pages, $page+1)])];
        }

        return [
            'campaigns_prepared' => $prepared,
            'campaign_tabs' => $tabs,
            'campaign_pagination' => $pagination,
            'campaign_range' => $total ? ((($page-1)*$per)+1).'–'.min($page*$per, $total).' de '.$total : '0 resultados',
            'campaign_total' => $total,
            'campaign_filter_active' => $q !== '' || $status !== '',
            'campaign_status' => $status,
            'campaign_per' => $per,
            'campaign_page' => $page,
        ];
    }
}
