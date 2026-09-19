@echo off
REM G2K Solar Manager - installer for Windows + XAMPP
REM Usage: double-click, or run "install.bat" in this folder. Extra options are passed through.
setlocal
chcp 65001 >nul
cd /d "%~dp0"

set "PHP_EXE="
if defined XAMPP_HOME if exist "%XAMPP_HOME%\php\php.exe" set "PHP_EXE=%XAMPP_HOME%\php\php.exe"
if not defined PHP_EXE if exist "C:\xampp\php\php.exe" set "PHP_EXE=C:\xampp\php\php.exe"
if not defined PHP_EXE if exist "D:\xampp\php\php.exe" set "PHP_EXE=D:\xampp\php\php.exe"
if not defined PHP_EXE (
    for /f "delims=" %%P in ('where php 2^>nul') do if not defined PHP_EXE set "PHP_EXE=%%P"
)
if not defined PHP_EXE (
    echo [FAIL] php.exe not found. Install XAMPP to C:\xampp or set XAMPP_HOME, e.g.:
    echo        set XAMPP_HOME=E:\xampp
    pause
    exit /b 1
)

echo Using PHP: %PHP_EXE%
"%PHP_EXE%" scripts\install.php %*
set "RC=%ERRORLEVEL%"
echo.
pause
exit /b %RC%
