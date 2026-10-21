@echo off
setlocal
cd /d "%~dp0.."
call scripts\build\gate.bat %*
exit /b %errorlevel%
