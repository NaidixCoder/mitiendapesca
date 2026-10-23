param([switch]$RaceOnly)
# MiTiendaPesca — E2E HTTP privado. EJECUTAR SOLO LOCALMENTE por el propietario.
# Bootstrap REAL de la aplicación + MariaDB privada; no usa la instancia de XAMPP.
$ErrorActionPreference='Stop'
$repo='C:\xampp\htdocs\mi-pesca'
$port=18997
$httpPort=18897
$dbName='mi_checkout_http_private_qa'
$base=Join-Path $env:TEMP 'mi_checkout_http_private_qa'
$site=Join-Path $base 'site'
$datadir=Join-Path $base 'data'
$php='C:\xampp\php\php.exe'
$mysql='C:\xampp\mysql\bin\mysql.exe'
$bootstrap='C:\xampp\mysql\bin\mysql_install_db.exe'
$mysqld='C:\xampp\mysql\bin\mysqld.exe'
$schema=Join-Path $repo 'database\schema.sql'
$log=Join-Path $repo 'docs\QA_CHECKOUT_HTTP_PRIVATE_RESULT.log'
$privateDb=$null
$privateHttp=$null
$privateHttpB=$null
$httpPortB=18896
$created=$false
$success=$false
$cleanup=$false
$client=@('--no-defaults','--protocol=tcp','--host=127.0.0.1',"--port=$port",'--user=root','--batch','--skip-column-names')
function Require([bool]$ok,[string]$what) {if(-not $ok){throw "REFUSED: $what"}}
function TestPort([int]$p){return [bool](Get-NetTCPConnection -LocalPort $p -State Listen -ErrorAction SilentlyContinue)}
function One([string]$sql){
 $v=& $mysql @client --execute $sql
 Require ($LASTEXITCODE -eq 0) "SQL private failed"
 return (($v | Out-String).Trim())
}
function Canon([string]$p){return [IO.Path]::GetFullPath($p).TrimEnd('\','/')}
try {
 Require ($env:OS -eq 'Windows_NT') 'Windows required'
 Require ((Canon $repo) -ieq (Canon 'C:\xampp\htdocs\mi-pesca')) 'wrong repo'
 foreach($f in @($php,$mysql,$bootstrap,$mysqld,$schema)){Require (Test-Path $f -PathType Leaf) "file missing $f"}
 Require (-not(Test-Path -LiteralPath $base)) 'private test directory already exists; review manually'
 Require (-not(TestPort $port) -and -not(TestPort $httpPort) -and -not(TestPort $httpPortB)) 'private port busy'
 $rng=[Security.Cryptography.RandomNumberGenerator]::Create()
 $bytes=New-Object byte[] 24;$rng.GetBytes($bytes);$rng.Dispose()
 $secret=[BitConverter]::ToString($bytes).Replace('-','').ToLowerInvariant()
 $env:MYSQL_PWD=$secret
 $env:MI_QA_DB_HOST='127.0.0.1'
 $env:MI_QA_DB_PORT="$port"
 $env:MI_QA_DB_NAME=$dbName
 $env:MI_QA_DB_USER='root'
 $env:MI_QA_DB_PASS=$secret
 $env:MI_QA_HTTP_PASSWORD=($secret+'QA')
 New-Item -ItemType Directory -Path $base | Out-Null;$created=$true
 # Clone with exclusions: never copy .env, storage, uploads or .git from commercial tree.
 New-Item -ItemType Directory -Path $site | Out-Null
 $null=& robocopy $repo $site /E /XD .git storage node_modules .vs .idea uploads /XF .env .env.* phpinfo.php tmp_session_dump.php tmp_session_info.php /NFL /NDL /NJH /NJS /NP
 Require ($LASTEXITCODE -le 7) 'clone failed'
 Require (-not(Test-Path (Join-Path $site '.env'))) 'commercial env copied'
 # Vendor files are copied into the temporary site, never linked to the commercial tree.
 Require (Test-Path (Join-Path $site 'vendor\autoload.php')) 'vendor dependencies missing in private clone'
 New-Item -ItemType Directory -Path (Join-Path $site 'storage') | Out-Null
 $qaSessions=Join-Path $base 'sessions'
 New-Item -ItemType Directory -Path $qaSessions | Out-Null
 $env:MI_QA_SESSION_DIR=$qaSessions
 $qaEnv=@(
  'APP_ENV=local',
  'APP_DEBUG=false',
  'APP_URL=http://127.0.0.1:18897',
  'DB_HOST=127.0.0.1',
  "DB_PORT=$port",
  "DB_NAME=$dbName",
  'DB_USER=root',
  "DB_PASS=$secret",
  'SESSION_NAME=MIE2EPRIVATE',
  'MAIL_MAILER=log',
  'MAIL_DRIVER=log',
  'MP_ACCESS_TOKEN=',
  'MP_PUBLIC_KEY=',
  'MP_WEBHOOK_SIGNATURE_SECRET=',
  'MP_WEBHOOK_SECRET='
 ) -join [Environment]::NewLine
 [IO.File]::WriteAllText((Join-Path $site '.env'),$qaEnv,(New-Object Text.UTF8Encoding($false)))
 & $bootstrap "--datadir=$datadir" "--port=$port" "--password=$secret" | Out-Null
 Require ($LASTEXITCODE -eq 0) 'private MariaDB initialization failed'
 $ini=Join-Path $datadir 'my.ini'
 Require (Test-Path $ini -PathType Leaf) 'private MariaDB config missing'
 $privateDb=Start-Process -FilePath $mysqld -ArgumentList @('--defaults-file='+[char]34+$ini+[char]34,'--bind-address=127.0.0.1',"--port=$port",'--console') -PassThru -RedirectStandardOutput (Join-Path $base 'mariadb.out') -RedirectStandardError (Join-Path $base 'mariadb.err')
 $ready=$false
 for($i=0;$i -lt 35;$i++){
  Start-Sleep -Milliseconds 500
  try{if((One 'SELECT @@port') -eq "$port"){$ready=$true;break}}catch{}
  if($privateDb.HasExited){break}
 }
 Require $ready 'private MariaDB did not start'
 Require ((Canon (One 'SELECT @@datadir')) -ieq (Canon $datadir)) 'DB DATA DIRECTORY IDENTITY MISMATCH'
 Require ((One "SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name='$dbName'") -eq '0') 'QA database already exists'
 [void](One "CREATE DATABASE $dbName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")
 $schemaArgs=@($client)+@("--database=$dbName")
 $import=Start-Process -FilePath $mysql -ArgumentList $schemaArgs -Wait -PassThru -RedirectStandardInput $schema -RedirectStandardOutput (Join-Path $base 'schema.out') -RedirectStandardError (Join-Path $base 'schema.err')
 Require ($import.ExitCode -eq 0) 'schema import failed'
 Require ([int](One "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$dbName'") -ge 60) 'schema incomplete'
 Write-Host '[QA] Private MariaDB and isolated clone verified.'
 $fixture=Join-Path $repo 'scripts\dev\checkout_http_real_fixture.php'
 $qaTest=Join-Path $repo 'scripts\dev\checkout_http_real_authenticated.py'
 Require (Test-Path $fixture -PathType Leaf) 'private fixture missing'
 Require (Test-Path $qaTest -PathType Leaf) 'HTTP E2E test missing'
 & $php $fixture seed
 Require ($LASTEXITCODE -eq 0) 'private fixture seeding failed'
 # Authentic HTTP checkout against isolated clone, synthetic user and private MariaDB.
 $router=Join-Path $base 'http_router.php'
 [IO.File]::WriteAllText($router, '<?php $p=parse_url($_SERVER["REQUEST_URI"],PHP_URL_PATH);$f=__DIR__."/site/public_html".$p;if($p!=="/" && is_file($f)){return false;}$_SERVER["SCRIPT_NAME"]="/index.php";require __DIR__."/site/public_html/index.php";', (New-Object Text.UTF8Encoding($false)))
 $privateHttp=Start-Process -FilePath $php -ArgumentList @('-d','SMTP=127.0.0.1','-d','smtp_port=1','-d',"session.save_path=$qaSessions",'-S',"127.0.0.1:$httpPort",'-t',"$site\public_html",$router) -PassThru -RedirectStandardOutput (Join-Path $base 'php.out') -RedirectStandardError (Join-Path $base 'php.err')
 $privateHttpB=Start-Process -FilePath $php -ArgumentList @('-d','SMTP=127.0.0.1','-d','smtp_port=1','-d',"session.save_path=$qaSessions",'-S',"127.0.0.1:$httpPortB",'-t',"$site\public_html",$router) -PassThru -RedirectStandardOutput (Join-Path $base 'php_b.out') -RedirectStandardError (Join-Path $base 'php_b.err')
 Start-Sleep -Seconds 1
 Require (-not $privateHttp.HasExited -and -not $privateHttpB.HasExited) 'PHP private servers failed'
 $get=Invoke-WebRequest -Uri "http://127.0.0.1:$httpPort/login" -UseBasicParsing -MaximumRedirection 0 -TimeoutSec 15
 Require ($get.StatusCode -eq 200) 'GET login HTTP failed'
 Write-Host '[QA] Real app GET /login 200.'
 $noOrder=(One "SELECT COUNT(*) FROM $dbName.orders")
 Require ($noOrder -eq '0') 'synthetic checkout baseline contains orders'
 Write-Host '[QA] Synthetic checkout baseline has zero orders.'
 $result='AUTHENTIC_HTTP_19_PREVIOUSLY_APPROVED_RACE_FOCAL_ONLY'
 if(-not $RaceOnly){
 $pyOut=Join-Path $base 'qa_http.out'
 $pyErr=Join-Path $base 'qa_http.err'
 $pyProc=Start-Process -FilePath 'python' -ArgumentList @([char]34+$qaTest+[char]34) -Wait -PassThru -RedirectStandardOutput $pyOut -RedirectStandardError $pyErr
 $result=Get-Content -LiteralPath $pyOut -Raw -ErrorAction SilentlyContinue
 $errorText=Get-Content -LiteralPath $pyErr -Raw -ErrorAction SilentlyContinue
 if($result){Write-Host $result}
 if($errorText){Write-Warning $errorText}
 Require ($pyProc.ExitCode -eq 0) 'HTTP authenticated E2E failed; consult private QA log'
 Require ($result -match 'CHECKOUT_AUTHENTIC_HTTP_MARIADB_PASS') 'HTTP E2E PASS marker missing'
 }
 $raceTest=Join-Path $repo 'scripts\dev\checkout_http_real_concurrent.py'
 Require (Test-Path $raceTest -PathType Leaf) 'concurrency QA missing'
 $raceOut=Join-Path $base 'race_http.out'
 $raceErr=Join-Path $base 'race_http.err'
 $raceProc=Start-Process -FilePath 'python' -ArgumentList @([char]34+$raceTest+[char]34) -Wait -PassThru -RedirectStandardOutput $raceOut -RedirectStandardError $raceErr
 $raceResult=Get-Content -LiteralPath $raceOut -Raw -ErrorAction SilentlyContinue
 $raceError=Get-Content -LiteralPath $raceErr -Raw -ErrorAction SilentlyContinue
 if($raceResult){Write-Host $raceResult}
 if($raceError){Write-Warning $raceError}
 Require ($raceProc.ExitCode -eq 0 -and $raceResult -match 'CHECKOUT_REAL_HTTP_CONCURRENCY_PASS 3') 'parallel HTTP checkout failed'
 $success=$true
 [IO.File]::WriteAllText($log,("PRIVATE AUTHENTIC HTTP E2E PASS`n"+$result+"`n"+$raceResult),(New-Object Text.UTF8Encoding($false)))
} catch {
 Write-Warning ("[QA] FAIL: "+$_.Exception.Message)
 $diag=@("PRIVATE AUTHENTIC HTTP E2E FAILED: "+$_.Exception.Message)
 foreach($file in @('qa_http.out','qa_http.err','race_http.out','race_http.err','schema.err','php.err','php_b.err')){
  $candidate=Join-Path $base $file
  if(Test-Path -LiteralPath $candidate -PathType Leaf){
   $diag+=("--- "+$file+" ---")
   $diag+=((Get-Content -LiteralPath $candidate -Tail 45 -ErrorAction SilentlyContinue) -join [Environment]::NewLine)
  }
 }
 [IO.File]::WriteAllText($log,($diag -join [Environment]::NewLine),(New-Object Text.UTF8Encoding($false)))
} finally {
 foreach($proc in @($privateHttp,$privateHttpB,$privateDb)){
  if($null -ne $proc){
   $p=Get-CimInstance Win32_Process -Filter "ProcessId = $($proc.Id)" -ErrorAction SilentlyContinue
   if($null -ne $p -and $p.Name -in @('php.exe','mysqld.exe') -and $p.CommandLine -match '18897|18896|18997|mi_checkout_http_private_qa'){
    Stop-Process -Id $proc.Id -Force -ErrorAction SilentlyContinue
   }
  }
 }
 Start-Sleep -Milliseconds 900
 try {
  Require (-not(TestPort $port) -and -not(TestPort $httpPort) -and -not(TestPort $httpPortB)) 'private port still occupied'
  if($created){
   Require ((Canon $base) -ieq (Canon (Join-Path $env:TEMP 'mi_checkout_http_private_qa'))) 'cleanup path mismatch'
   Remove-Item -LiteralPath $base -Recurse -Force
   Require (-not(Test-Path $base)) 'private directory cleanup failed'
  }
  $cleanup=$true
  Write-Host '[QA] Private test folders and ports cleaned.'
 }catch{Write-Warning ("CLEANUP REVIEW: "+$_.Exception.Message)}
 foreach($key in @('MYSQL_PWD','MI_QA_DB_HOST','MI_QA_DB_PORT','MI_QA_DB_NAME','MI_QA_DB_USER','MI_QA_DB_PASS','MI_QA_HTTP_PASSWORD','MI_QA_SESSION_DIR')) {
  Remove-Item "Env:$key" -ErrorAction SilentlyContinue
 }
 $secret=$null
}
if(-not $success -or -not $cleanup){exit 1}
Write-Host '[QA] AUTHENTIC HTTP CHECKOUT PASS — verify case count in report.'
exit 0
