@echo off
setlocal EnableExtensions EnableDelayedExpansion
cd /d "%~dp0"

docker info >nul 2>&1
if not errorlevel 1 goto AFTER_DOCKER_OK

echo [0] Docker parado. A abrir Docker Desktop...
set "_DD="
if exist "%ProgramFiles%\Docker\Docker\Docker Desktop.exe" set "_DD=%ProgramFiles%\Docker\Docker\Docker Desktop.exe"
if not defined _DD if exist "%ProgramFiles(x86)%\Docker\Docker\Docker Desktop.exe" set "_DD=%ProgramFiles(x86)%\Docker\Docker\Docker Desktop.exe"
if not defined _DD (
    echo ERRO: Docker Desktop nao encontrado em Program Files.
    echo        Instala: https://docs.docker.com/desktop/setup/install/windows-install/
    goto FALHA
)

start "" "!_DD!"
echo     A aguardar o motor Docker ^(ate 3 minutos^)...
set /a _d=0

:DOCKER_WAIT
timeout /t 3 /nobreak >nul
docker info >nul 2>&1
if not errorlevel 1 goto DOCKER_READY
set /a _d+=1
if !_d! GEQ 60 goto DOCKER_TIMEOUT
echo       ... !_d! / 60
goto DOCKER_WAIT

:DOCKER_TIMEOUT
echo ERRO: Docker nao ficou pronto a tempo. Abre o Docker Desktop manualmente e volta a executar este BAT.
goto FALHA

:DOCKER_READY
echo     Docker a responder.

:AFTER_DOCKER_OK
echo [1/4] MySQL ^(docker compose^)...
docker compose up -d mysql
if errorlevel 1 (
    echo ERRO: docker compose up falhou.
    goto FALHA
)

echo [2/4] A aguardar MySQL 127.0.0.1:3306 ...
set /a _w=0

:WAIT_DB
docker exec omega286-mysql mysqladmin ping -h localhost -uroot -proot --silent 2>nul
if not errorlevel 1 goto DB_OK
set /a _w+=1
if !_w! GEQ 50 goto MYSQL_TIMEOUT
echo       ... !_w! / 50
timeout /t 2 /nobreak >nul
goto WAIT_DB

:MYSQL_TIMEOUT
echo ERRO: MySQL no contentor nao respondeu. Comando: docker compose logs mysql
goto FALHA

:DB_OK
echo     MySQL pronto.

echo [3/4] Laravel :2080 e Vite...
start "Omega286 - Laravel" cmd /k "cd /d %~dp0 && C:\xampp\php\php.exe artisan config:clear && C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=2080"
timeout /t 2 /nobreak >nul
start "Omega286 - Vite" cmd /k "cd /d %~dp0 && npm run dev"

echo [4/4] A aguardar HTTP e a abrir o navegador...
timeout /t 5 /nobreak >nul
start "" "http://127.0.0.1:2080/"
echo.
echo Pronto. Mantem abertas as janelas "Laravel" e "Vite". Esta pode fechar.
timeout /t 4 /nobreak >nul
exit /b 0

:FALHA
echo.
echo Esta janela fecha em 45 segundos ^(tempo para ler a mensagem acima^).
timeout /t 45 /nobreak
exit /b 1
