@echo off
setlocal
cd /d C:\xampp\htdocs\mi-pesca
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "C:\xampp\htdocs\mi-pesca\scripts\dev\run_checkout_http_private.ps1" -RaceOnly
set "QA_EXIT=%ERRORLEVEL%"
echo.
if "%QA_EXIT%"=="0" (echo CHECKOUT QA OK - El resultado queda guardado en docs\QA_CHECKOUT_HTTP_PRIVATE_RESULT.log) else (echo CHECKOUT QA FALLO - El diagnostico queda guardado en docs\QA_CHECKOUT_HTTP_PRIVATE_RESULT.log)
echo No hace falta copiar la salida. Avisa en ChatGPT: ejecutado.
pause
exit /b %QA_EXIT%
