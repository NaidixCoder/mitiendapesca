@echo off
setlocal
cd /d "%~dp0\..\.."
php scripts\build\gate.php
exit /b %errorlevel%
