@echo off
setlocal
cd /d "%~dp0"
echo.
echo Interface disponible sur http://localhost:8080
echo Appuyez sur Ctrl+C pour arreter le serveur.
echo.
npm start
endlocal
