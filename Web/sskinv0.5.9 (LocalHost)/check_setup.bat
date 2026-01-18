@echo off
REM ============================================
REM Script untuk Quick Test Setup Localhost
REM ============================================

echo.
echo ========================================
echo   SSkIN Setup Checker
echo ========================================
echo.

REM Check if PHP is available
echo [1/6] Checking PHP...
php -v >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] PHP not found! Install PHP or add to PATH.
    pause
    exit /b 1
) else (
    php -v | findstr "PHP"
    echo [OK] PHP found!
)

echo.

REM Check if Composer is available
echo [2/6] Checking Composer...
composer --version >nul 2>&1
if %errorlevel% neq 0 (
    echo [WARNING] Composer not found!
    echo Install from: https://getcomposer.org
) else (
    composer --version | findstr "Composer"
    echo [OK] Composer found!
)

echo.

REM Check if .env exists
echo [3/6] Checking .env file...
if exist ".env" (
    echo [OK] .env file found!
    echo.
    echo --- Current .env Database Config ---
    findstr /B "DB_" .env
    echo ------------------------------------
) else (
    echo [WARNING] .env file not found!
    echo Creating from .env.example...
    if exist ".env.example" (
        copy ".env.example" ".env"
        echo [OK] .env created! Please edit database credentials.
    ) else (
        echo [ERROR] .env.example not found!
    )
)

echo.

REM Check vendor folder
echo [4/6] Checking Composer dependencies...
if exist "vendor" (
    echo [OK] Vendor folder exists!
) else (
    echo [WARNING] Vendor folder not found!
    echo Run: composer install
)

echo.

REM Check if MySQL is running (Windows)
echo [5/6] Checking MySQL service...
sc query MySQL >nul 2>&1
if %errorlevel% equ 0 (
    sc query MySQL | findstr "RUNNING" >nul
    if %errorlevel% equ 0 (
        echo [OK] MySQL service is running!
    ) else (
        echo [WARNING] MySQL service is not running!
        echo Start MySQL from Laragon/XAMPP Control Panel
    )
) else (
    REM Try MariaDB
    sc query MariaDB >nul 2>&1
    if %errorlevel% equ 0 (
        echo [OK] MariaDB service found!
    ) else (
        echo [WARNING] MySQL/MariaDB service not found as Windows service
        echo Check if running via Laragon/XAMPP manually
    )
)

echo.

REM Check database connection
echo [6/6] Testing database connection...
echo This will open test_connection.php in your browser...
echo.

REM Detect localhost path
set "currentPath=%cd%"
set "folderName="
for %%I in ("%currentPath%") do set "folderName=%%~nxI"

echo Detected folder: %folderName%
echo.

echo Open this URL in your browser:
echo http://localhost/%folderName%/test_connection.php
echo.

REM Try to open in default browser
start "" "http://localhost/%folderName%/test_connection.php"

echo.
echo ========================================
echo   Setup Check Complete!
echo ========================================
echo.
echo Next Steps:
echo 1. Check the browser window for database test
echo 2. If connection fails, edit .env file
echo 3. Make sure database 'edoc' is created
echo 4. Import edoc.sql to database
echo 5. Run: composer install (if not done)
echo.
echo Documentation:
echo - QUICK_START.md - Quick setup guide
echo - SETUP_LOCALHOST.md - Detailed guide
echo - TESTING_GUIDE.md - Testing checklist
echo.

pause
