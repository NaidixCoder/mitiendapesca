param([switch]$Keep,[switch]$Tunnel)
$ErrorActionPreference='Stop'
$repo='C:\xampp\htdocs\mi-pesca'
$base=Join-Path $env:TEMP 'mitiendapesca_mp_staging_20261023'
$dbport=18995
$webport=18895
$dbname='mp_staging_private'
$php='C:\xampp\php\php.exe'
$mysql='C:\xampp\mysql\bin\mysql.exe'
$mysqld='C:\xampp\mysql\bin\mysqld.exe'
$init='C:\xampp\mysql\bin\mysql_install_db.exe'
function Check([bool]$ok,[string]$name){if(!$ok){throw $name}}
function Busy([int]$port){[bool](Get-NetTCPConnection -State Listen -LocalPort $port -ErrorAction SilentlyContinue)}
$dbProc=$null;$webProc=$null;$tunnelProc=$null;$completed=$false;$created=$false
try {
 Check (!(Test-Path $base)) 'PRIVATE_STAGE_PATH_EXISTS'
 Check (!(Busy $dbport) -and !(Busy $webport)) 'PRIVATE_STAGE_PORT_BUSY'
 $rng=[Security.Cryptography.RandomNumberGenerator]::Create()
 $b=New-Object byte[] 24;$rng.GetBytes($b);$rng.Dispose()
 $password=[BitConverter]::ToString($b).Replace('-','').ToLowerInvariant()
 $env:MYSQL_PWD=$password
 New-Item -ItemType Directory -Path $base | Out-Null;$created=$true
 $site=Join-Path $base 'site'
 New-Item -ItemType Directory -Path $site | Out-Null
 $null=& robocopy $repo $site /E /XD .git storage node_modules .vs .idea uploads /XF .env .env.* phpinfo.php /NFL /NDL /NJH /NJS /NP
 Check ($LASTEXITCODE -le 7) 'CLONE_FAILED'
 Check (!(Test-Path (Join-Path $site '.env'))) 'ORIGINAL_ENV_COPIED'
 Check (Test-Path (Join-Path $site 'vendor\autoload.php')) 'VENDOR_MISSING'
 New-Item -ItemType Directory -Path (Join-Path $site 'storage') | Out-Null
 $sessions=Join-Path $base 'sessions';New-Item -ItemType Directory -Path $sessions | Out-Null
 $env:MI_QA_SESSION_DIR=$sessions
 $envContent=@(
  'APP_ENV=local','APP_DEBUG=false',"APP_URL=http://127.0.0.1:$webport",
  'DB_HOST=127.0.0.1',"DB_PORT=$dbport","DB_NAME=$dbname",'DB_USER=root',"DB_PASS=$password",
  'SESSION_NAME=MIMPSTAGING','MAIL_MAILER=log','MAIL_DRIVER=log',
  'MP_ACCESS_TOKEN=','MP_PUBLIC_KEY=',"MP_WEBHOOK_SIGNATURE_SECRET=$password",'MP_WEBHOOK_SECRET='
 ) -join [Environment]::NewLine
 [IO.File]::WriteAllText((Join-Path $site '.env'),$envContent,[Text.UTF8Encoding]::new($false))
 $datadir=Join-Path $base 'data'
 & $init "--datadir=$datadir" "--port=$dbport" "--password=$password" | Out-Null
 Check ($LASTEXITCODE -eq 0) 'DB_INITIALIZE_FAILED'
 $ini=Join-Path $datadir 'my.ini'
 Check (Test-Path $ini) 'DB_INI_MISSING'
 $dbProc=Start-Process -FilePath $mysqld -ArgumentList @('--defaults-file='+[char]34+$ini+[char]34,'--bind-address=127.0.0.1',"--port=$dbport",'--console') -PassThru -RedirectStandardOutput (Join-Path $base 'db.out') -RedirectStandardError (Join-Path $base 'db.err')
 $args=@('--no-defaults','--protocol=tcp','--host=127.0.0.1',"--port=$dbport",'--user=root','--batch','--skip-column-names')
 $ready=$false
 for($i=0;$i -lt 50;$i++){Start-Sleep -Milliseconds 300;try{$r=& $mysql @args --execute 'SELECT @@port' 2>$null;if($r -eq "$dbport"){$ready=$true;break}}catch{}}
 Check $ready 'DB_NOT_READY'
 $datadirActual=(& $mysql @args --execute 'SELECT @@datadir' | Out-String).Trim()
 Check ([IO.Path]::GetFullPath($datadirActual).TrimEnd('\') -ieq [IO.Path]::GetFullPath($datadir).TrimEnd('\')) 'DATABASE_IDENTITY_MISMATCH'
 & $mysql @args --execute "CREATE DATABASE $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
 Check ($LASTEXITCODE -eq 0) 'CREATE_PRIVATE_DATABASE_FAILED'
 $schema=Join-Path $repo 'database\schema.sql'
 $p=Start-Process -FilePath $mysql -ArgumentList (@($args)+@("--database=$dbname")) -Wait -PassThru -RedirectStandardInput $schema -RedirectStandardError (Join-Path $base 'schema.err')
 Check ($p.ExitCode -eq 0) 'SCHEMA_IMPORT_FAILED'
 $n=(& $mysql @args --execute "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$dbname'" | Out-String).Trim()
 Check ([int]$n -ge 60) 'SCHEMA_TABLE_COUNT_TOO_SMALL'
 $router=Join-Path $base 'router.php'
 $routerCode='<?php $path=parse_url($_SERVER["REQUEST_URI"],PHP_URL_PATH); if(preg_match("#^/(admin|phpmyadmin|api/admin|storage|vendor|config|scripts|docs)(/|$)#i",$path)){http_response_code(403);exit;} $f=__DIR__."/site/public_html".$path; if($path!=="/" && is_file($f)){return false;} $_SERVER["SCRIPT_NAME"]="/index.php";require __DIR__."/site/public_html/index.php";'
 [IO.File]::WriteAllText($router,$routerCode,[Text.UTF8Encoding]::new($false))
 $webProc=Start-Process -FilePath $php -ArgumentList @('-d',"session.save_path=$sessions",'-S',"127.0.0.1:$webport",'-t',"$site\public_html",$router) -PassThru -RedirectStandardOutput (Join-Path $base 'web.out') -RedirectStandardError (Join-Path $base 'web.err')
 Start-Sleep -Seconds 2
 Check (!$webProc.HasExited) 'STAGING_PHP_EXITED'
 $resp=Invoke-WebRequest -Uri "http://127.0.0.1:$webport/login" -UseBasicParsing -TimeoutSec 15
 Check ($resp.StatusCode -eq 200) 'STAGING_LOGIN_NOT_200'
 try { $denied=Invoke-WebRequest -Uri "http://127.0.0.1:$webport/admin" -UseBasicParsing -TimeoutSec 8; Check ($false) 'ADMIN_ROUTE_PUBLIC' } catch [System.Net.WebException] {Check ($_.Exception.Response.StatusCode.value__ -eq 403) 'ADMIN_ROUTE_NOT_403'}
 Write-Host "MP_STAGE_DB_TABLES=$n"
 Write-Host 'MP_STAGE_LOGIN=HTTP_200'
 Write-Host 'MP_STAGE_ADMIN=HTTP_403'
 Write-Host 'MP_STAGE_ISOLATION=PASS'
 if($Tunnel) {
   $cf='C:\Program Files (x86)\cloudflared\cloudflared.exe'
   Check (Test-Path $cf) 'CLOUDFLARED_NOT_INSTALLED'
   $cferr=Join-Path $base 'tunnel.err'
   $tunnelProc=Start-Process -FilePath $cf -ArgumentList @('tunnel','--no-autoupdate','--protocol','http2','--url',"http://127.0.0.1:$webport") -PassThru -RedirectStandardError $cferr -RedirectStandardOutput (Join-Path $base 'tunnel.out')
   $url=''
   for($j=0;$j -lt 30;$j++){
     Start-Sleep -Milliseconds 700
     $log=Get-Content -LiteralPath $cferr -Raw -ErrorAction SilentlyContinue
     if($log -match 'https://[a-zA-Z0-9-]+\.trycloudflare\.com'){ $url=$matches[0];break }
     if($tunnelProc.HasExited){break}
   }
   Check ($url -ne '') 'PUBLIC_HTTPS_URL_NOT_CREATED'
   $publicOk=$false
   for($k=0;$k -lt 12;$k++){
     try{$login=Invoke-WebRequest -Uri ($url+'/login') -UseBasicParsing -TimeoutSec 8; if($login.StatusCode -eq 200){$publicOk=$true;break}}catch{}
     Start-Sleep -Seconds 2
   }
   Check $publicOk 'PUBLIC_LOGIN_NOT_REACHABLE_AFTER_RETRIES'
   try { $unexpected=Invoke-WebRequest -Uri ($url+'/admin') -UseBasicParsing -TimeoutSec 10;Check $false 'PUBLIC_ADMIN_ACCESSIBLE' }
   catch [System.Net.WebException] { Check ($_.Exception.Response.StatusCode.value__ -eq 403) 'PUBLIC_ADMIN_NOT_DENIED' }
   try {
     $unexpectedWebhook=Invoke-WebRequest -Uri ($url+'/webhooks/mercadopago') -Method Post -ContentType 'application/json' -Body '{"type":"payment","data":{"id":"197"}}' -UseBasicParsing -TimeoutSec 10
     Check $false 'UNSIGNED_WEBHOOK_ACCEPTED'
   } catch [System.Net.WebException] {
     Check ($_.Exception.Response.StatusCode.value__ -eq 403) 'UNSIGNED_WEBHOOK_NOT_REJECTED'
   }
   Write-Host 'MP_STAGE_TUNNEL_HTTPS=PASS'
   Write-Host 'MP_STAGE_PUBLIC_ADMIN=HTTP_403'
   Write-Host 'MP_STAGE_UNSIGNED_WEBHOOK=HTTP_403'
 }
 $completed=$true
 if($Keep){throw 'KEEP_NOT_IMPLEMENTED: secure manual lifecycle required'}
} catch {Write-Host ('MP_STAGE_RESULT='+$_.Exception.Message)}
finally {
 foreach($pr in @($tunnelProc,$webProc,$dbProc)){if($null -ne $pr){try{Stop-Process -Id $pr.Id -Force -ErrorAction SilentlyContinue}catch{}}}
 Start-Sleep -Milliseconds 800
 if($created -and (Test-Path -LiteralPath $base)){Remove-Item -LiteralPath $base -Recurse -Force -ErrorAction SilentlyContinue}
 Remove-Item Env:MYSQL_PWD -ErrorAction SilentlyContinue
 Remove-Item Env:MI_QA_SESSION_DIR -ErrorAction SilentlyContinue
 Write-Host ('MP_STAGE_CLEANUP=' + [string](!(Test-Path $base) -and !(Busy $dbport) -and !(Busy $webport)))
}
if(!$completed){exit 1}
