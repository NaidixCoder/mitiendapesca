<?php
function cache_get(string $k){
  $f = base_path('storage/cache/'.sha1($k).'.json');
  return is_file($f) ? json_decode(file_get_contents($f), true) : null;
}
function cache_put(string $k,$v,$ttl){
  $dir = base_path('storage/cache');
  @mkdir($dir,0775,true);
  file_put_contents($dir.'/'.sha1($k).'.json', json_encode(['exp'=>time()+$ttl,'val'=>$v]));
}
function cache_remember(string $key,int $ttl, callable $cb){
  $c = cache_get($key);
  if($c && ($c['exp']??0) > time()) return $c['val'];
  $v = $cb(); cache_put($key,$v,$ttl); return $v;
}
function cache_forget(string $key){
  $f = base_path('storage/cache/'.sha1($key).'.json');
  if (is_file($f)) @unlink($f);
}
