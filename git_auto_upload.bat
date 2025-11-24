@echo off
echo ========================================
echo KYA Food Production - Auto Git Upload
echo ========================================

:: Check if git is installed
git --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Git is not installed or not in PATH
    echo Please install Git from https://git-scm.com/download/win
    pause
    exit /b 1
)

:: Navigate to project directory
cd /d "c:\xampp\htdocs\kya-food-production"

:: Initialize git if not already done
if not exist ".git" (
    echo Initializing Git repository...
    git init
    echo Git repository initialized.
)

:: Configure git user (update with your details)
echo Configuring Git user...
git config user.name "Praveen061215"
git config user.email "YOUR_EMAIL_ADDRESS_HERE"

:: Add remote origin if not exists
git remote get-url origin >nul 2>&1
if %errorlevel% neq 0 (
    echo Adding remote origin...
    git remote add origin https://github.com/Praveen061215/kya_food_production.git
) else (
    echo Remote origin already exists.
)

:: Create .gitignore if not exists
if not exist ".gitignore" (
    echo Creating .gitignore file...
    (
        echo # KYA Food Production - Git Ignore
        echo.
        echo # System files
        echo .DS_Store
        echo Thumbs.db
        echo desktop.ini
        echo.
        echo # IDE files
        echo .vscode/
        echo .idea/
        echo *.swp
        echo *.swo
        echo.
        echo # Logs
        echo *.log
        echo logs/
        echo.
        echo # Temporary files
        echo tmp/
        echo temp/
        echo cache/
        echo.
        echo # Environment files
        echo .env
        echo .env.local
        echo.
        echo # Database backups
        echo *.sql.backup
        echo database/backups/
        echo.
        echo # Uploaded files
        echo uploads/
        echo files/
        echo.
        echo # Vendor dependencies
        echo vendor/
        echo node_modules/
        echo.
        echo # Configuration files with sensitive data
        echo config/database_local.php
        echo config/secrets.php
    ) > .gitignore
    echo .gitignore created.
)

:: Add all files
echo Adding files to Git...
git add .

:: Get current timestamp for commit message
for /f "tokens=2 delims==" %%a in ('wmic OS Get localdatetime /value') do set "dt=%%a"
set "YY=%dt:~2,2%" & set "YYYY=%dt:~0,4%" & set "MM=%dt:~4,2%" & set "DD=%dt:~6,2%"
set "HH=%dt:~8,2%" & set "Min=%dt:~10,2%" & set "Sec=%dt:~12,2%"
set "timestamp=%YYYY%-%MM%-%DD% %HH%:%Min%:%Sec%"

:: Commit changes
echo Committing changes...
git commit -m "Auto upload: KYA Food Production updates - %timestamp%"

:: Push to GitHub
echo Pushing to GitHub...
git push -u origin main

if %errorlevel% equ 0 (
    echo.
    echo ========================================
    echo SUCCESS: Project uploaded to GitHub!
    echo Repository: https://github.com/Praveen061215/kya_food_production.git
    echo ========================================
) else (
    echo.
    echo ========================================
    echo ERROR: Failed to push to GitHub
    echo Please check your internet connection and GitHub credentials
    echo ========================================
)

echo.
echo Press any key to exit...
pause >nul
