# Isolated E2E runner for LOCAL use. Do not invoke via remote tooling.
$ErrorActionPreference = 'Stop'
$repo = Split-Path (Split-Path $PSScriptRoot -Parent) -Parent
$port = 18989
$dbName = 'mi_transversal_qa_20261022'
$qaDir = Join-Path ([IO.Path]::GetFullPath($env:TEMP)) $dbName
$dataDir = Join-Path $qaDir 'data'
$ini = Join-Path $dataDir 'my.ini'
$schema = Join-Path $repo 'database\schema.sql'
$fixture = Join-Path $repo 'scripts\dev\transversal_import_category_checkout_isolated.php'
$evidence = Join-Path $repo 'docs\QA_TRANSVERSAL_SQL_RESULT_20261022.log'
$bin = 'C:\xampp\mysql\bin'
$mysql = Join-Path $bin 'mysql.exe'
$bootstrap = Join-Path $bin 'mysql_install_db.exe'
$mysqld = Join-Path $bin 'mysqld.exe'
$php = 'C:\xampp\php\php.exe'
$privateServer = $null
$createdDir = $false
$passed = $false
$cleanupOk = $false

function Require([bool]$ok, [string]$message) {
    if (-not $ok) { throw "REFUSED: $message" }
}
function Canonical([string]$path) {
    return [IO.Path]::GetFullPath($path).TrimEnd('\', '/')
}
function SqlOne([string]$sql) {
    $value = & $mysql @clientArgs --execute $sql
    if ($LASTEXITCODE -ne 0) { throw 'Private MariaDB query failed' }
    return (($value | Out-String).Trim())
}
function StopPrivateServer {
    if ($null -eq $script:privateServer) { return }
    $id = [int]$script:privateServer.Id
    $proc = Get-CimInstance Win32_Process -Filter "ProcessId = $id" -ErrorAction SilentlyContinue
    if ($null -ne $proc) {
        Require ($proc.Name -eq 'mysqld.exe' -and
            $proc.CommandLine -like '*mi_transversal_qa_20261022*' -and
            $proc.CommandLine -like '*18989*') 'private server identity mismatch'
        Stop-Process -Id $id -Force
        Start-Sleep -Milliseconds 1000
    }
    Require (-not (Get-Process -Id $id -ErrorAction SilentlyContinue)) 'private server still alive'
}
try {
    Require ($env:OS -eq 'Windows_NT') 'Windows environment required'
    Require ((Canonical $repo) -ieq (Canonical 'C:\xampp\htdocs\mi-pesca')) 'unexpected project root'
    foreach ($path in @($mysql,$bootstrap,$mysqld,$php,$schema,$fixture)) {
        Require (Test-Path -LiteralPath $path -PathType Leaf) "missing file $path"
    }
    Require (-not (Test-Path -LiteralPath $qaDir)) 'private QA directory already exists'
    Require (-not (Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue)) 'QA port busy'

    # Random test-only credentials; do not use .env or shop database.
    $rng = [Security.Cryptography.RandomNumberGenerator]::Create()
    $bytes = New-Object byte[] 24
    $rng.GetBytes($bytes)
    $rng.Dispose()
    $password = [BitConverter]::ToString($bytes).Replace('-','').ToLowerInvariant()
    $env:MYSQL_PWD = $password
    $env:MI_TRANSVERSAL_QA_DSN = "mysql:host=127.0.0.1;port=$port;dbname=$dbName;charset=utf8mb4"
    $env:MI_TRANSVERSAL_QA_USER = 'root'
    $env:MI_TRANSVERSAL_QA_PASSWORD = $password
    $clientArgs = @('--no-defaults','--protocol=tcp','--host=127.0.0.1',"--port=$port",'--user=root',
        '--batch','--skip-column-names','--default-character-set=utf8mb4')

    New-Item -ItemType Directory -Path $qaDir | Out-Null
    $createdDir = $true
    Write-Host '[QA] Initializing temporary MariaDB.'
    & $bootstrap "--datadir=$dataDir" "--port=$port" "--password=$password" | Out-Null
    Require ($LASTEXITCODE -eq 0 -and (Test-Path -LiteralPath $ini)) 'private MariaDB initialization failed'

    $serverArgs = @('--defaults-file=' + [char]34 + $ini + [char]34,'--bind-address=127.0.0.1',"--port=$port",'--console')
    $privateServer = Start-Process -FilePath $mysqld -ArgumentList $serverArgs -PassThru -RedirectStandardOutput (Join-Path $qaDir 'mysql.out') -RedirectStandardError (Join-Path $qaDir 'mysql.err')
    Require ($privateServer -ne $null) 'could not launch private server'

    $ready = $false
    for($i=0; $i -lt 30; $i++) {
        Start-Sleep -Milliseconds 600
        try {
            $probe = & $mysql @clientArgs --execute 'SELECT @@port' 2>$null
            if ($LASTEXITCODE -eq 0 -and (($probe | Out-String).Trim()) -eq "$port") {
                $ready = $true
                break
            }
        } catch { }
        if ($privateServer.HasExited) { break }
    }
    Require $ready 'private server not ready'

    # Validate the physical datadir BEFORE all SQL writes.
    Require ((Canonical (SqlOne 'SELECT @@datadir')) -ieq (Canonical $dataDir)) 'server datadir not private'
    Require ((SqlOne 'SELECT @@port') -eq "$port") 'wrong port'
    Require ((SqlOne "SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name='$dbName'") -eq '0') 'QA schema already exists'
    Write-Host '[QA] MariaDB identity and datadir verified.'

    [void](SqlOne "CREATE DATABASE $dbName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")
    $schemaArgs = @($clientArgs) + @("--database=$dbName")
    $schemaProc = Start-Process -FilePath $mysql -PassThru -Wait -ArgumentList $schemaArgs -RedirectStandardInput $schema -RedirectStandardOutput (Join-Path $qaDir 'schema.out') -RedirectStandardError (Join-Path $qaDir 'schema.err')
    Require ($schemaProc.ExitCode -eq 0) 'schema import failed'
    $tableCount = SqlOne "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$dbName'"
    Require ([int]$tableCount -ge 60) "incomplete schema: $tableCount tables"
    Require ((SqlOne "SELECT engine FROM information_schema.tables WHERE table_schema='$dbName' AND table_name='products'") -eq 'InnoDB') 'expected InnoDB schema'
    Write-Host "[QA] Synthetic schema: $tableCount tables."

    & $php -l $fixture
    Require ($LASTEXITCODE -eq 0) 'PHP syntax check failed'
    $out = Join-Path $qaDir 'fixture.out'
    $err = Join-Path $qaDir 'fixture.err'
    $fixtureArg = [char]34 + $fixture + [char]34
    $testProc = Start-Process -FilePath $php -ArgumentList @($fixtureArg) -PassThru -Wait -RedirectStandardOutput $out -RedirectStandardError $err
    $stdOut = Get-Content -LiteralPath $out -Raw -ErrorAction SilentlyContinue
    $stdErr = Get-Content -LiteralPath $err -Raw -ErrorAction SilentlyContinue
    $report = @('MiTiendaPesca isolated SQL E2E',"port=$port","database=$dbName",
        "tables=$tableCount","exit=$($testProc.ExitCode)",$stdOut,$stdErr) -join [Environment]::NewLine
    [IO.File]::WriteAllText($evidence,$report,(New-Object Text.UTF8Encoding($false)))
    Write-Host $stdOut
    if ($stdErr) { Write-Warning $stdErr }
    Require ($testProc.ExitCode -eq 0) 'E2E PHP fixture failed'
    Require ($stdOut.Contains('[transversal-import-category-checkout] OK')) 'E2E success marker missing'
    $passed = $true
    Write-Host "[QA] PASS. Evidence saved in $evidence"
} catch {
    $failureMessage = "PRIVATE E2E FAILED: " + $_.Exception.Message
    Write-Warning $failureMessage
    $details = @($failureMessage, "timestamp=$((Get-Date).ToString('o'))")
    foreach ($name in @('schema.err','fixture.err')) {
        $diag = Join-Path $qaDir $name
        if (Test-Path -LiteralPath $diag) {
            $details += "--- $name ---"
            $details += (Get-Content -LiteralPath $diag -Raw -ErrorAction SilentlyContinue)
        }
    }
    [IO.File]::WriteAllText($evidence,($details -join [Environment]::NewLine),(New-Object Text.UTF8Encoding($false)))
} finally {
    try {
        StopPrivateServer
        Require (-not (Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue)) 'private port still listening'
        if ($createdDir) {
            Require ((Canonical $qaDir) -ieq (Canonical (Join-Path $env:TEMP $dbName))) 'cleanup path mismatch'
            Remove-Item -LiteralPath $qaDir -Recurse -Force
            Require (-not (Test-Path -LiteralPath $qaDir)) 'private folder not removed'
        }
        $cleanupOk = $true
        Write-Host '[QA] Temporary database, port and files cleaned.'
    } catch { Write-Warning ('CLEANUP REQUIRES REVIEW: '+$_.Exception.Message) }
    Remove-Item Env:MYSQL_PWD -ErrorAction SilentlyContinue
    Remove-Item Env:MI_TRANSVERSAL_QA_DSN -ErrorAction SilentlyContinue
    Remove-Item Env:MI_TRANSVERSAL_QA_USER -ErrorAction SilentlyContinue
    Remove-Item Env:MI_TRANSVERSAL_QA_PASSWORD -ErrorAction SilentlyContinue
    $password = $null
}
if (-not $passed -or -not $cleanupOk) { exit 1 }
exit 0
