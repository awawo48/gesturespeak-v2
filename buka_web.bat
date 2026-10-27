@echo off
setlocal enabledelayedexpansion
title GestureSpeak Web Launcher

echo ===================================================
echo   Memulakan Server GestureSpeak...
echo ===================================================
echo.

:: 1. Cari lokasi PHP secara automatik (Laragon / System / XAMPP)
set "PHP_CMD=php"

where php >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    if exist "C:\laragon\bin\php\php-8.3.36-Win32-vs16-x64\php.exe" (
        set "PHP_CMD=C:\laragon\bin\php\php-8.3.36-Win32-vs16-x64\php.exe"
    ) else (
        for /d %%D in ("C:\laragon\bin\php\*") do (
            if exist "%%D\php.exe" set "PHP_CMD=%%D\php.exe"
        )
    )
)

if "%PHP_CMD%"=="php" (
    where php >nul 2>&1
    if %ERRORLEVEL% NEQ 0 (
        if exist "C:\Program Files\php\php.exe" (
            set "PHP_CMD=C:\Program Files\php\php.exe"
        ) else if exist "C:\xampp\php\php.exe" (
            set "PHP_CMD=C:\xampp\php\php.exe"
        )
    )
)

echo Menggunakan PHP: %PHP_CMD%
echo.

:: 2. Masuk ke folder projek
cd /d "%~dp0"
if exist gesturespeak-v2 cd gesturespeak-v2

if not exist artisan (
    echo [RALAT] Fail artisan tidak dijumpai.
    echo Sila pastikan fail ini berada dalam folder projek GestureSpeak.
    pause
    exit /b
)

:: 3. Tunggu 2 saat dan buka browser secara automatik
start "" cmd /c "ping 127.0.0.1 -n 3 >nul & start http://127.0.0.1:8000"

echo ===================================================
echo   GestureSpeak sedang dibuka di: http://127.0.0.1:8000
echo   (JANGAN tutup tetingkap ini semasa menggunakan web)
echo ===================================================
echo.

:: 4. Jalankan Laravel server
"%PHP_CMD%" artisan serve --port=8000

echo.
echo Server telah ditutup.
pause
