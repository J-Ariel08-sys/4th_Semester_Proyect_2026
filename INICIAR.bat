@echo off
where py >nul 2>nul
if errorlevel 1 (
    python "%~dp0scripts\dev-up.py"
) else (
    py -3 "%~dp0scripts\dev-up.py"
)
if errorlevel 1 (
    echo Revisa el error y confirma que Python 3 y Docker Desktop esten instalados y en ejecucion.
    pause
    exit /b 1
)
pause
