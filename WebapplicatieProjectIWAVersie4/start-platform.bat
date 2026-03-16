\
@echo off
setlocal
start "IWA Dashboard" cmd /k "cd /d %~dp0dashboard-app && php -S localhost:8080 \"%cd%\\server-router.php\""
echo Dashboard gestart op http://localhost:8080
echo Start daarna de generator vanuit generator-java\run.bat
endlocal
