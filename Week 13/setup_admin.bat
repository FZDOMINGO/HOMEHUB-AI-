@echo off
echo ===============================================
echo   HomeHub Admin System Database Setup
echo ===============================================
echo.
echo This script will create the admin system tables
echo and insert a default admin user.
echo.
echo Default Admin Credentials:
echo   Username: admin
echo   Password: admin123
echo.
echo WARNING: Change the default password after first login!
echo.
pause

echo.
echo Setting up admin tables...
mysql -u root -p homehub < sql/admin_schema.sql

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ===============================================
    echo   Setup completed successfully!
    echo ===============================================
    echo.
    echo You can now access the admin panel at:
    echo   http://localhost/HomeHub/admin/login.php
    echo.
    echo Default login:
    echo   Username: admin
    echo   Password: admin123
    echo.
    echo Please change the password after first login!
    echo ===============================================
) else (
    echo.
    echo ===============================================
    echo   Setup failed!
    echo ===============================================
    echo Please check your MySQL connection and try again.
)

echo.
pause