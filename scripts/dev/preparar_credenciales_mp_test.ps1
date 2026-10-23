# Preparacion local. NO inicia servidor ni tunel. NO lee el .env comercial.
$ErrorActionPreference = 'Stop'
$dest = Join-Path $env:LOCALAPPDATA 'MiTiendaPesca\mp-homologacion'
$file = Join-Path $dest 'credenciales-test.env'
New-Item -ItemType Directory -Path $dest -Force | Out-Null
if (-not (Test-Path -LiteralPath $file)) {
    $template = @'
# SOLO credenciales de PRUEBA de Tu integracion Mercado Pago.
# No copiar el .env comercial. No compartir este archivo ni subirlo a Git.
MP_ACCESS_TOKEN=
MP_PUBLIC_KEY=
MP_WEBHOOK_SIGNATURE_SECRET=
'@
    [System.IO.File]::WriteAllText($file, $template, (New-Object System.Text.UTF8Encoding($false)))
    Write-Host '[MP QA] Plantilla TEST creada fuera del repositorio.'
} else {
    Write-Host '[MP QA] Archivo existente conservado sin modificar.'
}
$required = @('MP_ACCESS_TOKEN', 'MP_PUBLIC_KEY', 'MP_WEBHOOK_SIGNATURE_SECRET')
$values = @{}
foreach($line in (Get-Content -LiteralPath $file)) {
    if ($line -match '^([A-Z_]+)=(.*)$') { $values[$matches[1]] = $matches[2].Trim() }
}
foreach($key in $required) {
    $isPresent = $values.ContainsKey($key) -and $values[$key] -ne ''
    $state = if($isPresent){'PRESENTE'}else{'PENDIENTE'}
    Write-Host "[MP QA] $key : $state"
}
Write-Host '[MP QA] No se imprimieron valores de credenciales ni se activo un tunel.'
