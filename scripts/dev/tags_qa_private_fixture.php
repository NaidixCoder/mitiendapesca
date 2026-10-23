<?php
declare(strict_types=1);
$host=(string)getenv('TAGS_QA_DB_HOST');$port=(string)getenv('TAGS_QA_DB_PORT');$name=(string)getenv('TAGS_QA_DB_NAME');$pass=(string)getenv('TAGS_QA_DB_PASS');
$mode=$argv[1]??'';
if($host!=='127.0.0.1'||$port!=='18998'||$name!=='mi_tags_private_qa_20261023'||strlen($pass)<20) exit(2);
$db=new PDO("mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4",'root',$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$meta=$db->query("SELECT @@port p,@@datadir d,DATABASE() n")->fetch(PDO::FETCH_ASSOC);
$expected=strtolower(str_replace('\\','/',sys_get_temp_dir().'\\mi_tags_private_qa_20261023\\data'));
if((int)$meta['p']!==18998 || $meta['n']!==$name || rtrim(strtolower(str_replace('\\','/',$meta['d'])),'/')!==rtrim($expected,'/')) exit(3);
if($mode==='seed'){
 $password=(string)getenv('TAGS_QA_LOGIN_PASSWORD');
 if(strlen($password)<20)exit(4);
 $stmt=$db->prepare("INSERT INTO users (id,name,email,password_hash,email_verified,session_version,role) VALUES(?,?,?,?,1,1,?)");
 $stmt->execute([198,'QA Admin','admin-tags@example.test',password_hash($password,PASSWORD_DEFAULT),'admin']);
 $stmt->execute([199,'QA Cliente','cliente-tags@example.test',password_hash($password,PASSWORD_DEFAULT),'customer']);
 echo "SEED_OK\n";
}elseif($mode==='inspect'){
 $res=['groups'=>(int)$db->query('SELECT COUNT(*) FROM catalog_tag_groups')->fetchColumn(),'tags'=>(int)$db->query('SELECT COUNT(*) FROM catalog_tags')->fetchColumn(),'relationships'=>(int)$db->query('SELECT COUNT(*) FROM product_catalog_tags')->fetchColumn()];
 echo json_encode($res)."\n";
}else{exit(5);}
