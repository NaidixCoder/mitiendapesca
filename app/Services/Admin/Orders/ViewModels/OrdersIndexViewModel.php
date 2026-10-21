<?php
namespace App\Services\Admin\Orders\ViewModels;

final class OrdersIndexViewModel
{
    public function build(array $input): array
    {
        $orders=is_array($input['orders']??null)?$input['orders']:[];
        $filters=is_array($input['filters']??null)?$input['filters']:[];
        $meta=is_array($input['meta']??null)?$input['meta']:[];
        $statusOptions=is_array($input['statusOptions']??null)?$input['statusOptions']:[];
        $paymentOptions=is_array($input['paymentMethodOptions']??null)?$input['paymentMethodOptions']:[];
        $prepared=[];
        foreach($orders as $row){
            if(!is_array($row))continue;
            $state=(string)($row['status']??'');
            $pm=(string)($row['payment_method']??'');
            $pstate=(string)($row['payment_status']??'');
            $shipping=(string)($row['shipment_status']??'');
            [$payLabel,$payTone]=$this->paymentState($pstate,$state);
            [$deliveryLabel,$deliveryTone]=$this->deliveryState($shipping,$state);
            $prepared[]=[
                'id'=>(int)($row['id']??0),
                'href'=>(string)($row['href']??''),
                'created_short'=>(string)($row['created_short']??''),
                'date_label'=>(string)($row['date_label']??''),
                'time_label'=>(string)($row['time_label']??''),
                'customer'=>(string)($row['customer']??''),
                'status'=>$state,
                'status_label'=>(string)($row['status_label']??$this->statusLabel($state)),
                'status_tone'=>$this->orderTone($state),
                'payment_label'=>$payLabel,
                'payment_tone'=>$payTone,
                'payment_method_label'=>$this->paymentMethodLabel($pm),
                'delivery_label'=>$deliveryLabel,
                'delivery_tone'=>$deliveryTone,
                'total_fmt'=>(string)($row['total_fmt']??''),
            ];
        }
        $statuses=[];
        foreach($statusOptions as $status){
            $status=(string)$status;
            $statuses[]=['value'=>$status,'label'=>$this->statusLabel($status),
                'selected'=>(string)($filters['status']??'')===$status];
        }
        $methods=[];
        foreach($paymentOptions as $method){
            $method=(string)$method;
            $methods[]=['value'=>$method,'label'=>$this->paymentMethodLabel($method),
                'selected'=>(string)($filters['payment_method']??'')===$method];
        }
        $filters=array_merge(['q'=>'','status'=>'','payment_method'=>'','from'=>'','to'=>'',
            'view'=>'all','sort'=>'newest','per_page'=>25,'page'=>1],$filters);
        $meta=array_merge(['page'=>1,'pages'=>1,'per_page'=>25,'total'=>count($prepared),
            'view'=>'all','sort'=>'newest','quick_counts'=>[]],$meta);
        $filters['view']=$meta['view'];
        $filters['sort']=$meta['sort'];
        $filters['per_page']=$meta['per_page'];
        $filters['page']=$meta['page'];
        $quickLabels=['all'=>'Todos','collect'=>'Por cobrar','prepare'=>'Para preparar',
            'shipping'=>'En camino','finished'=>'Finalizados'];
        $quick=[];
        foreach($quickLabels as $key=>$label){
            $params=$filters;
            $params['view']=$key;
            $params['status']='';
            $params['page']=1;
            $quick[]=['key'=>$key,'label'=>$label,
                'count'=>(int)($meta['quick_counts'][$key]??0),
                'selected'=>$meta['view']===$key,
                'href'=>url('/admin/orders').'?'.http_build_query(array_filter($params,static fn($v)=>$v!==''&&$v!==null))];
        }
        $urlFor=static function(array $changes=[]) use($filters):string{
            $params=array_merge($filters,$changes);
            unset($params['page']);
            if(array_key_exists('page',$changes))$params['page']=$changes['page'];
            $params=array_filter($params,static fn($value)=>$value!==''&&$value!==null);
            return url('/admin/orders').'?'.http_build_query($params);
        };
        $activeFilters=[];
        $filterLabels=[
            'q'=>'Búsqueda',
            'status'=>'Estado',
            'payment_method'=>'Método',
            'from'=>'Desde',
            'to'=>'Hasta',
        ];
        foreach($filterLabels as $key=>$label){
            $raw=trim((string)($filters[$key]??''));
            if($raw==='')continue;
            $value=$raw;
            if($key==='status'){
                foreach($statuses as $item)if($item['value']===$raw)$value=$item['label'];
            } elseif($key==='payment_method'){
                $value=$this->paymentMethodLabel($raw);
            } elseif(($key==='from'||$key==='to')&&preg_match('/^\\d{4}-\\d{2}-\\d{2}$/',$raw)){
                $value=substr($raw,8,2).'/'.substr($raw,5,2).'/'.substr($raw,0,4);
            }
            $activeFilters[]=['label'=>$label.': '.$value,'href'=>$urlFor([$key=>'', 'page'=>1])];
        }
        $advancedActive=(string)($filters['status']??'')!=='' ||
            (string)($filters['payment_method']??'')!=='' ||
            (string)($filters['from']??'')!=='' ||
            (string)($filters['to']??'')!=='';
        $pagination=[
            'prev_url'=>(int)$meta['page']>1?$urlFor(['page'=>(int)$meta['page']-1]):'',
            'next_url'=>(int)$meta['page']<(int)$meta['pages']?$urlFor(['page'=>(int)$meta['page']+1]):'',
        ];
        return [
            'orders_prepared'=>$prepared,'orders_count'=>(int)$meta['total'],
            'orders_meta'=>$meta,'orders_quick_views'=>$quick,
            'orders_advanced_active'=>$advancedActive,
            'orders_active_filters'=>$activeFilters,'orders_pagination'=>$pagination,
            'filters'=>$filters,'status_options_prepared'=>$statuses,
            'payment_method_options_prepared'=>$methods,
            'links'=>['dashboard'=>url('/admin'),'index'=>url('/admin/orders')],
        ];
    }

    private function orderTone(string $status):string {
        return match($status){
            'paid','processing','packed'=>'info',
            'shipped','delivered','completed'=>'success',
            'payment_failed','payment_expired','chargeback'=>'danger',
            'cancelled','refunded'=>'muted',
            default=>'warning',
        };
    }
    private function paymentState(string $payment,string $order):array {
        $st=strtolower(trim($payment));
        if(in_array($st,['approved','paid','accredited','succeeded','success','authorized'],true))
            return ['Confirmado','success'];
        if(in_array($st,['rejected','failed','failure','cancelled','canceled','charged_back'],true))
            return ['Fallido','danger'];
        if(in_array($st,['refunded','refunded_partial'],true))
            return ['Reembolsado','muted'];
        if(in_array($st,['pending','in_process','in_mediation','pending_review'],true))
            return ['Pendiente','warning'];
        if($st!=='')return ['Sin confirmar','muted'];
        if(in_array($order,['paid','processing','packed','shipped','delivered','completed'],true))
            return ['Confirmado por pedido','success'];
        if(in_array($order,['payment_failed','payment_expired','chargeback'],true))
            return ['No confirmado','danger'];
        return ['Sin registro','muted'];
    }
    private function deliveryState(string $shipment,string $order):array {
        if(in_array($order,['delivered','completed'],true))return ['Entregado','success'];
        if($order==='shipped'||$shipment==='despachado')return ['Despachado','info'];
        if($order==='packed')return ['Embalado','info'];
        if(in_array($order,['cancelled','refunded','chargeback','payment_expired'],true))
            return ['No aplica','muted'];
        if($shipment==='preparando'||in_array($order,['paid','processing'],true))
            return ['Por preparar','warning'];
        return ['No iniciada','muted'];
    }
    private function paymentMethodLabel(string $method):string {
        $map=[
            'tarjeta_credito'=>'Tarjeta de crédito','tarjeta_debito'=>'Tarjeta de débito',
            'card_mp'=>'Tarjeta','mercadopago_card'=>'Tarjeta',
            'transfer_bank'=>'Transferencia','transferencia'=>'Transferencia',
            'transfer'=>'Transferencia','bank_transfer'=>'Transferencia',
            'transfer_mp'=>'Transferencia (MP)',
        ];
        return $map[$method]??($method!==''?$method:'No informado');
    }
    private function statusLabel(string $status):string {
        return function_exists('order_status_label_admin')?(string)order_status_label_admin($status):$status;
    }
}
