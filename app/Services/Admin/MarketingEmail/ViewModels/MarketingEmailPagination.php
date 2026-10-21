<?php
namespace App\Services\Admin\MarketingEmail\ViewModels;

final class MarketingEmailPagination
{
    /** @return array<string,mixed> */
    public static function build(string $route, int $total, int $per, int $page, array $filters = []): array
    {
        $per = in_array($per, [25,50], true) ? $per : 25;
        $pages = max(1, (int)ceil(max(0, $total) / $per));
        $page = max(1, min($pages, $page));
        $base = array_merge($filters, ['per'=>$per]);
        $url = static fn(int $p):string => url($route.'?'.http_build_query(array_merge($base,['page'=>$p])));
        $links = [];
        if ($pages > 1) {
            $links[]=['label'=>'Anterior','url'=>$url(max(1,$page-1)),'disabled'=>$page<=1,'active'=>false];
            for ($i=max(1,$page-2);$i<=min($pages,$page+2);$i++) {
                $links[]=['label'=>(string)$i,'url'=>$url($i),'disabled'=>false,'active'=>$i===$page];
            }
            $links[]=['label'=>'Siguiente','url'=>$url(min($pages,$page+1)),'disabled'=>$page>=$pages,'active'=>false];
        }
        return [
            'per'=>$per,'page'=>$page,'pages'=>$pages,'total'=>$total,'pagination'=>$links,
            'range_label'=>$total > 0 ? ((($page-1)*$per)+1).'–'.min($page*$per,$total).' de '.$total : '0 resultados',
        ];
    }
}
