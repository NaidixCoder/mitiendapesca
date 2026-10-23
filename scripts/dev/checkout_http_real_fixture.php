<?php
declare(strict_types=1);
/** Private-only seed and verification. Refuses the store database by physical port and name. */
$host=(string)getenv('MI_QA_DB_HOST');
$port=(int)getenv('MI_QA_DB_PORT');
$name=(string)getenv('MI_QA_DB_NAME');
$user=(string)getenv('MI_QA_DB_USER');
$pass=(string)getenv('MI_QA_DB_PASS');
$mode=$argv[1]??'';
if($host!=='127.0.0.1'||$port!==18997||$name!=='mi_checkout_http_private_qa'
   ||$user!=='root'||$pass===''||!in_array($mode,['seed','snapshot','session','hide','show','stock_low','stock_restore','cart_restore','race_reset','diagnose'],true)){
    fwrite(STDERR,"REFUSED: private QA identity invalid\n");exit(2);
}
$db=new PDO("mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4",$user,$pass,[
  PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false
]);
$meta=$db->query('SELECT DATABASE() db,@@port port,@@datadir datadir')->fetch(PDO::FETCH_ASSOC);
$expected=str_replace('\\','/',strtolower(sys_get_temp_dir().DIRECTORY_SEPARATOR.'mi_checkout_http_private_qa'.DIRECTORY_SEPARATOR.'data'));
$actual=str_replace('\\','/',strtolower(rtrim((string)$meta['datadir'],"\\/")));
if($meta['db']!==$name||(int)$meta['port']!==$port||rtrim($actual,'/')!==rtrim($expected,'/')){
    fwrite(STDERR,"REFUSED: MariaDB datadir or identity mismatched\n");exit(2);
}
if(in_array($mode,['hide','show','stock_low','stock_restore','cart_restore','race_reset'],true)){
    if($mode==='race_reset'){
        $db->beginTransaction();
        try {
            $db->exec('DELETE FROM order_items WHERE product_id=197');
            $db->exec('DELETE FROM orders WHERE user_id=197');
            $db->exec('UPDATE inventory SET stock=5 WHERE product_id=197');
            $db->exec('INSERT INTO cart_items(cart_id,product_id,quantity) VALUES(197,197,2) ON DUPLICATE KEY UPDATE quantity=2');
            $db->commit();
        }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    }elseif($mode==='cart_restore'){
        $db->exec('UPDATE cart_items SET quantity=2 WHERE cart_id=197 AND product_id=197');
    }elseif($mode==='hide'||$mode==='show'){
        $db->prepare('UPDATE products SET is_active=? WHERE id=197')->execute([$mode==='show'?1:0]);
    }else{
        $db->prepare('UPDATE inventory SET stock=? WHERE product_id=197')->execute([$mode==='stock_low'?1:5]);
    }
    echo "PRIVATE_QA_MUTATION_OK\n";
}elseif($mode==='seed'){
   $password=(string)getenv('MI_QA_HTTP_PASSWORD');
   if(strlen($password)<20)throw new RuntimeException('QA password missing');
   $db->beginTransaction();
   try {
    $db->exec("INSERT INTO categories(id,name,slug,is_active) VALUES (197, 'Categoria QA E2E','categoria-qa-e2e',1)");
    $db->exec("INSERT INTO products(id,sku,slug,name,category_id,sale_mode,iva_rate,price,list_price,is_active)
          VALUES(197,'QA-E2E-P','producto-qa-e2e','Producto sintetico E2E',197,'simple',0.2100,1500,1500,1)");
    $db->exec("INSERT INTO inventory(product_id,stock) VALUES(197,5)");
    $st=$db->prepare("INSERT INTO users(id,name,email,password_hash,email_verified,session_version,role)
        VALUES(197,'Comprador QA','comprador-checkout@example.test',?,1,1,'customer')");
    $st->execute([password_hash($password,PASSWORD_DEFAULT)]);
    $db->exec("INSERT INTO user_addresses(id,user_id,label,full_name,street,number,city,state,zip,is_default)
        VALUES(197,197,'Casa','Comprador QA','Calle QA','197','Firmat','Santa Fe','2630',1)");
    $db->exec("INSERT INTO carts(id,user_id,status) VALUES(197,197,'active')");
    $db->exec("INSERT INTO cart_items(cart_id,product_id,quantity) VALUES(197,197,2)");
    $db->commit();
    echo "PRIVATE_QA_SEED_OK\n";
   }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}elseif($mode==='snapshot'){
    $order=(int)$db->query("SELECT COUNT(*) FROM orders WHERE user_id=197")->fetchColumn();
    $items=(int)$db->query("SELECT COUNT(*) FROM order_items WHERE product_id=197")->fetchColumn();
    $stock=(int)$db->query("SELECT stock FROM inventory WHERE product_id=197")->fetchColumn();
    $status=$db->query("SELECT status FROM orders WHERE user_id=197 ORDER BY id DESC LIMIT 1")->fetchColumn()?:'none';
    echo json_encode(compact('order','items','stock','status'),JSON_UNESCAPED_UNICODE)."\n";
}elseif($mode==='session'||$mode==='diagnose'){
    $sid=(string)getenv('MI_QA_HTTP_SESSION_ID');
    if(!preg_match('/^[A-Za-z0-9,-]{12,160}$/',$sid))throw new RuntimeException('Missing session id');
    $save=(string)getenv('MI_QA_SESSION_DIR');
    $expectedSave=sys_get_temp_dir().DIRECTORY_SEPARATOR.'mi_checkout_http_private_qa'.DIRECTORY_SEPARATOR.'sessions';
    if(strtolower(str_replace('\\','/',$save))!==strtolower(str_replace('\\','/',$expectedSave)))
        throw new RuntimeException('QA session path invalid');
    session_save_path($save);
    session_name('MIE2EPRIVATE');
    session_id($sid);
    if(!session_start())throw new RuntimeException('Cannot load PHP session');
    if((int)($_SESSION['uid']??0)!==197)throw new RuntimeException('QA synthetic user not authenticated');
    if($mode==='diagnose'){
        $q=$db->query('SELECT c.id,c.status,COUNT(ci.id) AS item_count FROM carts c LEFT JOIN cart_items ci ON ci.cart_id=c.id WHERE c.user_id=197 GROUP BY c.id,c.status ORDER BY c.id')->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['user_id'=>(int)($_SESSION['uid']??0),'shipping'=>$_SESSION['checkout_shipping']??null,'flash'=>$_SESSION['_flash']??($_SESSION['flash']??null),'carts'=>$q],JSON_UNESCAPED_UNICODE)."\n";
        session_write_close();
        exit(0);
    }
    $_SESSION['checkout_shipping']=['mode'=>'free','service_code'=>'FREE','cost'=>0,
      'label'=>'Entrega privada QA', 'cart_sig'=>'', 'created_at'=>time()];
    $_SESSION['checkout_address_selected_id']=197;
    session_write_close();
    echo "PRIVATE_QA_SESSION_OK\n";
}
