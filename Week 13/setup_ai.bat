@echo off
REM HomeHub AI Setup Script for Windows
REM Run this script to set up the AI environment

echo ================================
echo HomeHub AI Setup Script
echo ================================
echo.

REM Check if Python is installed
python --version >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Python is not installed or not in PATH
    echo Please install Python 3.8+ from https://www.python.org/downloads/
    echo Make sure to check "Add Python to PATH" during installation
    pause
    exit /b 1
)

echo [OK] Python is installed
python --version
echo.

REM Check if we're in the correct directory
if not exist "ai\requirements.txt" (
    echo [ERROR] Please run this script from the HomeHub root directory
    echo Current directory: %CD%
    pause
    exit /b 1
)

echo [OK] Found ai\requirements.txt
echo.

REM Create virtual environment
echo Creating Python virtual environment...
if exist "ai_env" (
    echo Virtual environment already exists
) else (
    python -m venv ai_env
    if errorlevel 1 (
        echo [ERROR] Failed to create virtual environment
        pause
        exit /b 1
    )
    echo [OK] Virtual environment created
)
echo.

REM Activate virtual environment and install dependencies
echo Installing Python dependencies...
call ai_env\Scripts\activate.bat

python -m pip install --upgrade pip
pip install -r ai\requirements.txt

if errorlevel 1 (
    echo [ERROR] Failed to install dependencies
    pause
    exit /b 1
)

echo [OK] Dependencies installed
echo.

REM Create necessary directories
echo Creating required directories...
if not exist "ai\logs" mkdir ai\logs
if not exist "ai\models" mkdir ai\models
if not exist "ai\cache" mkdir ai\cache
echo [OK] Directories created
echo.

REM Create .env file if it doesn't exist
if not exist "ai\.env" (
    echo Creating default .env file...
    (
        echo DB_HOST=localhost
        echo DB_USER=root
        echo DB_PASSWORD=
        echo DB_NAME=homehub
        echo.
        echo FLASK_DEBUG=False
        echo FLASK_PORT=5000
        echo.
        echo REDIS_HOST=localhost
        echo REDIS_PORT=6379
        echo REDIS_DB=0
    ) > ai\.env
    echo [OK] .env file created - please review and update if needed
) else (
    echo .env file already exists
)
echo.

REM Test database connection
echo Testing database connection...
python -c "from ai.config import DB_CONFIG; print('Database config loaded successfully')" 2>nul
if errorlevel 1 (
    echo [WARNING] Could not load database config - check ai\config.py
) else (
    echo [OK] Database config loaded
)
echo.

echo ================================
echo Setup Complete!
echo ================================
echo.
echo Next steps:
echo 1. Import the database schema:
echo    mysql -u root -p homehub ^< sql\ai_features_schema.sql
echo.
echo 2. Activate the virtual environment:
echo    ai_env\Scripts\activate.bat
echo.
echo 3. Start the AI API server:
echo    python ai\api_server.py
echo.
echo 4. In another terminal, start Apache (XAMPP)
echo.
echo For more details, see AI_IMPLEMENTATION_GUIDE.md
echo.
pause
