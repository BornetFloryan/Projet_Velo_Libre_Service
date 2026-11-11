@echo off
setlocal
cd /d "%~dp0web"
echo.
echo Interface disponible sur http://localhost:8080
echo Appuyez sur Ctrl+C pour arreter le serveur.
echo.
C:\xampp\php\php.exe -S localhost:8080
endlocal
