$ErrorActionPreference = 'Stop'
$path = 'C:\xampp\htdocs\mi-pesca\.env'
if (-not (Test-Path -LiteralPath $path)) { Write-Output 'ENV_MISSING'; exit 1 }
$lines = Get-Content -LiteralPath $path
$keys = @('MP_ACCESS_TOKEN','MP_PUBLIC_KEY','MP_SANDBOX','MP_WEBHOOK_SIGNATURE_SECRET','MP_WEBHOOK_SECRET')
foreach ($key in $keys) {
  $matched = @($lines | Where-Object { $_ -match ('^\s*' + [regex]::Escape($key) + '\s*=') })
  if ($matched.Count -eq 0) { Write-Output ($key + ':ABSENT'); continue }
  $value = (($matched[-1] -split '=',2)[1]).Trim().Trim('"',"'")
  if ($value.Length -eq 0) { Write-Output ($key + ':EMPTY'); continue }
  if ($key -eq 'MP_SANDBOX') {
    Write-Output ($key + ':SET_BOOLEAN_' + ([string]($value -match '^(1|true|yes|on)$')))
  } elseif ($key -eq 'MP_ACCESS_TOKEN') {
    $class = if ($value -match '^TEST-') { 'TEST_PREFIX' } elseif ($value -match '^APP_USR-') { 'PRODUCTION_PREFIX' } else { 'UNKNOWN_PREFIX' }
    Write-Output ($key + ':SET_' + $class)
  } elseif ($key -eq 'MP_PUBLIC_KEY') {
    $class = if ($value -match '^TEST-') { 'TEST_PREFIX' } elseif ($value -match '^APP_USR-') { 'PRODUCTION_PREFIX' } else { 'UNKNOWN_PREFIX' }
    Write-Output ($key + ':SET_' + $class)
  } else {
    Write-Output ($key + ':SET')
  }
}
