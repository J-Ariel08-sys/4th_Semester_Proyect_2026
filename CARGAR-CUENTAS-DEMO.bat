@echo off
setlocal
set "SICLIN_DIR=%USERPROFILE%\Downloads\SICLIN-Hito4-Programa\SICLIN"
if exist "%~dp0docker-compose.yml" set "SICLIN_DIR=%~dp0"
if not exist "%SICLIN_DIR%\docker-compose.yml" (
    echo Extrae estos tres archivos dentro de la carpeta SICLIN, junto a INICIAR.bat.
    pause
    exit /b 1
)
if not exist "%~dp0CUENTAS_DEMO.sql" (
    echo Extrae todos los archivos del ZIP en una misma carpeta.
    pause
    exit /b 1
)
pushd "%SICLIN_DIR%" || exit /b 1
docker compose -p siclin-hito4-datos exec -T postgres-db psql -X -1 -v ON_ERROR_STOP=1 -U unifranz_user -d rotes_clinicos_db < "%~dp0CUENTAS_DEMO.sql"
set "RESULTADO=%ERRORLEVEL%"
popd
if not "%RESULTADO%"=="0" (
    echo La carga se detuvo. Confirma que SICLIN abre en http://localhost:18010 y revisa el mensaje de arriba.
    pause
    exit /b %RESULTADO%
)
echo.
echo Carga terminada. Recarga la pantalla de usuarios en SICLIN.
pause
