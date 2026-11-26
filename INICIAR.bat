@echo off
setlocal
set "PYTHONUTF8=1"
where py >nul 2>nul
if errorlevel 1 (
    python "%~dp0scripts\iniciar-windows.py"
) else (
    py -3 "%~dp0scripts\iniciar-windows.py"
)
if errorlevel 1 (
    pause
    exit /b 1
)
echo.
echo SICLIN: http://localhost:18010
echo Buzon local: http://localhost:18036
start "" "http://localhost:18010"
pause
