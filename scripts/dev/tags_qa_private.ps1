$ErrorActionPreference='Stop'
$repo='C:\xampp\htdocs\mi-pesca'
$base=Join-Path $env:TEMP 'mi_tags_private_qa_20261023'
$site=Join-Path $base 'site'
$data=Join-Path $base 'data'
$dbPort=18998
$httpPort=18898
$dbName='mi_tags_private_qa_20261023'
$php='C:\xampp\php\php.exe'
$mysql='C:\xampp\mysql\bin\mysql.exe'
$mysqld='C:\xampp\mysql\bin\mysqld.exe'
$bootstrap='C:\xampp\mysql\bin\mysql_install_db.exe'
$mysqlProc=$null
$httpProc=$null
$created=$false
$passed=$false
$cleanup=$false
function Assert([bool]$ok,[string]$msg){if(-not $ok){throw $msg}}
function Active([int]$port){return [bool](Get-NetTCPConnection -State Listen -LocalPort $port -ErrorAction SilentlyContinue)}
function Query([string]$sql) {
 $result=& $mysql --no-defaults --protocol=tcp --host=127.0.0.1 "--port=$dbPort" --user=root --batch --skip-column-names --execute $sql 2>$null
 if($LASTEXITCODE -ne 0){throw 'Private database query failed'}
 return ($result|Out-String).Trim()
}
try {
 Assert (-not (Test-Path $base)) 'Refusing pre-existing temporary directory'
 Assert (-not (Active $dbPort) -and -not (Active $httpPort)) 'Private QA port busy'
 Assert (Test-Path (Join-Path $repo 'database\schema.sql')) 'schema missing'
 foreach($exe in @($php,$mysql,$mysqld,$bootstrap)){Assert (Test-Path $exe) 'required executable missing'}
 $rng=[Security.Cryptography.RandomNumberGenerator]::Create();$bytes=New-Object byte[] 24;$rng.GetBytes($bytes);$rng.Dispose();$secret=[BitConverter]::ToString($bytes).Replace('-','').ToLowerInvariant()
 $env:MYSQL_PWD=$secret
 $env:TAGS_QA_DB_HOST='127.0.0.1'
 $env:TAGS_QA_DB_PORT="$dbPort"
 $env:TAGS_QA_DB_NAME=$dbName
 $env:TAGS_QA_DB_PASS=$secret
 $env:TAGS_QA_LOGIN_PASSWORD=$secret
 New-Item -ItemType Directory -Path $base | Out-Null;$created=$true
 New-Item -ItemType Directory -Path $site | Out-Null
 $null=& robocopy $repo $site /E /XD .git storage node_modules .vs .idea uploads /XF .env .env.* phpinfo.php /NFL /NDL /NJH /NJS /NP
 Assert ($LASTEXITCODE -le 7) 'copy failed'
 Assert (-not(Test-Path (Join-Path $site '.env'))) 'commercial .env leak'
 Assert (Test-Path (Join-Path $site 'vendor\autoload.php')) 'vendor missing'
 New-Item -ItemType Directory -Path (Join-Path $site 'storage') | Out-Null
 $sessions=Join-Path $base 'sessions';New-Item -ItemType Directory -Path $sessions | Out-Null
 $qaEnv=@('APP_ENV=local','APP_DEBUG=false',"APP_URL=http://127.0.0.1:$httpPort",'DB_HOST=127.0.0.1',"DB_PORT=$dbPort","DB_NAME=$dbName",'DB_USER=root',"DB_PASS=$secret",'SESSION_NAME=MITAGSQA','MAIL_MAILER=log','MAIL_DRIVER=log','MP_ACCESS_TOKEN=','MP_PUBLIC_KEY=') -join [Environment]::NewLine
 [IO.File]::WriteAllText((Join-Path $site '.env'),$qaEnv,(New-Object Text.UTF8Encoding($false)))
 & $bootstrap "--datadir=$data" "--port=$dbPort" "--password=$secret" | Out-Null
 Assert ($LASTEXITCODE -eq 0) 'init failed'
 $ini=Join-Path $data 'my.ini';Assert (Test-Path $ini) 'private DB config missing'
 $mysqlProc=Start-Process -FilePath $mysqld -ArgumentList @('--defaults-file='+[char]34+$ini+[char]34,'--bind-address=127.0.0.1',"--port=$dbPort",'--console') -PassThru -RedirectStandardOutput (Join-Path $base 'mysql.out') -RedirectStandardError (Join-Path $base 'mysql.err')
 $ready=$false
 for($i=0;$i -lt 40;$i++){Start-Sleep -Milliseconds 400;try{if((Query 'SELECT @@port') -eq "$dbPort"){$ready=$true;break}}catch{}}
 Assert $ready 'private DB not ready'
 $actual=[IO.Path]::GetFullPath((Query 'SELECT @@datadir')).TrimEnd('\','/')
 $expected=[IO.Path]::GetFullPath($data).TrimEnd('\','/')
 Assert ($actual -ieq $expected) 'DB DATA DIRECTORY MISMATCH'
 Assert ((Query "SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name='$dbName'") -eq '0') 'nonempty DB name'
 [void](Query "CREATE DATABASE $dbName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")
 $schema=Join-Path $repo 'database\schema.sql'
 $import=Start-Process -FilePath $mysql -ArgumentList @('--no-defaults','--protocol=tcp','--host=127.0.0.1',"--port=$dbPort",'--user=root',"--database=$dbName") -PassThru -Wait -RedirectStandardInput $schema -RedirectStandardOutput (Join-Path $base 'schema.out') -RedirectStandardError (Join-Path $base 'schema.err')
 Assert ($import.ExitCode -eq 0) 'schema import failed'
 Assert ([int](Query "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$dbName'") -ge 60) 'schema too small'
 & $php (Join-Path $repo 'scripts\dev\tags_qa_private_fixture.php') seed
 Assert ($LASTEXITCODE -eq 0) 'seed error'
 $router=Join-Path $base 'http_router.php'
 Copy-Item -LiteralPath (Join-Path $repo 'scripts\dev\tags_qa_router.php') -Destination $router
 $httpProc=Start-Process -FilePath $php -ArgumentList @('-d',"session.save_path=$sessions",'-d','SMTP=127.0.0.1','-d','smtp_port=1','-S',"127.0.0.1:$httpPort",'-t',"$site\public_html",$router) -PassThru -RedirectStandardOutput (Join-Path $base 'http.out') -RedirectStandardError (Join-Path $base 'http.err')
 Start-Sleep -Seconds 1
 Assert (-not $httpProc.HasExited) 'HTTP server failed'
 Write-Host '[TAGS] Private MariaDB/schema/site ready'
 & python (Join-Path $repo 'scripts\dev\tags_qa_http.py')
 Assert ($LASTEXITCODE -eq 0) 'HTTP real failed'
 & $php (Join-Path $repo 'scripts\dev\tags_qa_private_fixture.php') inspect
 Assert ($LASTEXITCODE -eq 0) 'inspection failed'
 & $php (Join-Path $repo 'scripts\dev\tags_qa_integrity.php')
 Assert ($LASTEXITCODE -eq 0) 'MariaDB integrity failed'
 & python (Join-Path $repo 'scripts\dev\tags_qa_consumers.py')
 Assert ($LASTEXITCODE -eq 0) 'Product/variant/public consumer HTTP regression failed'
 $passed=$true
}catch {
 Write-Warning ('TAGS_QA_FAILED '+$_.Exception.Message)
 foreach($f in @('schema.err','mysql.err','http.err')){ $p=Join-Path $base $f;if(Test-Path $p){Write-Host ('---'+$f);Get-Content $p -Tail 13}}
}finally{
 foreach($proc in @($httpProc,$mysqlProc)){
  if($null -ne $proc){$info=Get-CimInstance Win32_Process -Filter "ProcessId = $($proc.Id)" -ErrorAction SilentlyContinue
   if($null -ne $info -and $info.CommandLine -match '18898|18998|mi_tags_private_qa_20261023'){Stop-Process -Id $proc.Id -Force -ErrorAction SilentlyContinue}
  }
 }
 Start-Sleep -Milliseconds 800
 if(-not (Active $httpPort) -and -not (Active $dbPort)){
  if($created -and (Test-Path $base)){Remove-Item -LiteralPath $base -Recurse -Force -ErrorAction SilentlyContinue}
  $cleanup= -not(Test-Path $base)
 }
 foreach($key in @('MYSQL_PWD','TAGS_QA_DB_HOST','TAGS_QA_DB_PORT','TAGS_QA_DB_NAME','TAGS_QA_DB_PASS','TAGS_QA_LOGIN_PASSWORD')){Remove-Item "Env:$key" -ErrorAction SilentlyContinue}
 Write-Host ('[TAGS] Cleanup='+$cleanup)
}
if(-not $passed -or -not $cleanup){exit 1}
Write-Host '[TAGS] REAL_HTTP_MARIADB_PASS'
exit 0
