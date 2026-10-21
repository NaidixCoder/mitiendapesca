<?php
namespace App\Services\Admin\MarketingEmail\ViewModels;

final class MarketingEmailSubscribersViewModel
{
    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function build(array $input): array
    {
        $rows = is_array($input['rows'] ?? null) ? $input['rows'] : [];
        $status = trim((string)($input['status'] ?? ''));
        $q = trim((string)($input['q'] ?? ''));
        $total = (int)($input['total'] ?? count($rows));
        $per = max(1, (int)($input['per'] ?? 25));
        $page = max(1, (int)($input['page'] ?? 1));
        $pages = max(1, (int)($input['pages'] ?? 1));
        $base = ['per'=>$per];
        if ($q !== '') $base['q'] = $q;
        if ($status !== '') $base['status'] = $status;
        $link = static fn(int $p):string => url('/admin/marketing/email/suscriptores?'.http_build_query(array_merge($base,['page'=>$p])));
        $pagination = [];
        if ($pages > 1) {
            $pagination[] = ['label'=>'Anterior','url'=>$link(max(1,$page-1)),'disabled'=>$page<=1,'active'=>false];
            for ($n=max(1,$page-2); $n<=min($pages,$page+2); $n++) {
                $pagination[] = ['label'=>(string)$n,'url'=>$link($n),'disabled'=>false,'active'=>$n===$page];
            }
            $pagination[] = ['label'=>'Siguiente','url'=>$link(min($pages,$page+1)),'disabled'=>$page>=$pages,'active'=>false];
        }

        $prepared = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $st = (string)($row['status'] ?? '');
            [$label, $badge] = $this->statusUi($st);
            $id = (int)($row['id'] ?? 0);
            $prepared[] = [
                'id' => $id,
                'email' => (string)($row['email'] ?? ''),
                'status' => $st,
                'status_label' => $label,
                'status_badge' => $badge,
                'created_at' => (string)($row['created_at'] ?? ''),
                'confirmed_at' => (string)($row['confirmed_at'] ?? ''),
                'can_unsubscribe' => ($st !== 'unsubscribed'),
            ];
        }

        return [
            'q' => $q,
            'status' => $status,
            'rows_prepared' => $prepared,
            'status_options' => $this->statusOptions($status),
            'has_rows' => !empty($prepared),
            'total' => $total,
            'per' => $per,
            'page' => $page,
            'range_label' => $total > 0 ? ((($page-1)*$per)+1).'–'.min($page*$per,$total).' de '.$total : '0 resultados',
            'pagination' => $pagination,
            'links' => [
                'back' => url('/admin/marketing/email'),
                'index' => url('/admin/marketing/email/suscriptores'),
                'set_status' => url('/admin/marketing/email/suscriptores/estado'),
            ],
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function statusOptions(string $selected): array
    {
        $rows = [
            ['value' => '', 'label' => 'Todos'],
            ['value' => 'subscribed', 'label' => 'Suscriptos'],
            ['value' => 'pending', 'label' => 'Pendientes'],
            ['value' => 'unsubscribed', 'label' => 'Bajas'],
        ];
        foreach ($rows as &$row) {
            $row['selected'] = ($row['value'] === $selected);
        }
        unset($row);
        return $rows;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function statusUi(string $status): array
    {
        return match ($status) {
            'subscribed' => ['Suscripto', 'ok'],
            'pending' => ['Pendiente', 'warn'],
            default => ['Baja', 'error'],
        };
    }
}




