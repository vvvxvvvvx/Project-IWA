\
@echo off
setlocal
cd /d "%~dp0dashboard-app"
php -S localhost:8080 "%cd%\server-router.php"
endlocal
