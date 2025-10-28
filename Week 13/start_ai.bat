@echo off
echo ================================================
echo Starting HomeHub AI Server...
echo ================================================
cd /d "%~dp0"
call ai_env\Scripts\activate.bat
python ai\api_server.py
pause
