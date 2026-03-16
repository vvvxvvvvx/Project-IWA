\
@echo off
setlocal
cd /d "%~dp0"
if not exist out (
  call build.bat
)
java -cp out app.WeatherGeneratorApplication
endlocal
