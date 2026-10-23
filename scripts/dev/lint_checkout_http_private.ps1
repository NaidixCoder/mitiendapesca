$tokens=$null
$parseErrors=$null
[System.Management.Automation.Language.Parser]::ParseFile('C:\xampp\htdocs\mi-pesca\scripts\dev\run_checkout_http_private.ps1',[ref]$tokens,[ref]$parseErrors) | Out-Null
if ($parseErrors.Count -gt 0) {
  foreach ($problem in $parseErrors) { Write-Host $problem.Message }
  exit 1
}
Write-Host 'POWERSHELL_SYNTAX_PASS'
exit 0
