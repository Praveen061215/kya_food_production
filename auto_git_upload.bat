@echo off
echo ========================================
echo KYA Food Production - Auto Git Upload
echo ========================================
echo.

:: Change to project directory
cd /d "c:\xampp\htdocs\kya-food-production"

:: Check if git is installed
git --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Git is not installed or not in PATH
    echo Please install Git from https://git-scm.com/
    pause
    exit /b 1
)

echo [1/7] Initializing Git repository...
git init
if errorlevel 1 (
    echo ERROR: Failed to initialize Git repository
    pause
    exit /b 1
)

echo [2/7] Adding remote repository...
git remote add origin https://github.com/Praveen061215/kya_food_production.git
if errorlevel 1 (
    echo WARNING: Remote might already exist, continuing...
    git remote set-url origin https://github.com/Praveen061215/kya_food_production.git
)

echo [3/7] Creating .gitignore file...
(
echo # Sensitive configuration files
echo config/database.php
echo .env
echo.
echo # Log files
echo *.log
echo logs/
echo.
echo # Temporary files
echo temp/
echo tmp/
echo.
echo # IDE files
echo .vscode/
echo .idea/
echo.
echo # System files
echo Thumbs.db
echo .DS_Store
echo.
echo # Backup files
echo *.bak
echo *.backup
) > .gitignore

echo [4/7] Staging all files...
git add .
if errorlevel 1 (
    echo ERROR: Failed to stage files
    pause
    exit /b 1
)

echo [5/7] Creating initial commit...
git commit -m "Initial commit: KYA Food Production Management System

Features:
- Complete inventory management system with multi-section support
- User authentication and role-based access control  
- Dashboard with real-time analytics and reporting
- Temperature monitoring and quality control systems
- Financial and processing reports with interactive charts
- Comprehensive notification system
- Responsive web interface with modern UI
- Multi-section workflow: Raw Materials, Processing, Packaging, Quality Control, Storage, Distribution, Reports
- Export capabilities (PDF, Excel, CSV)
- Real-time monitoring and alerts
- Audit trails and activity logging

Sections:
- Section 1: Raw Material Handling
- Section 2: Processing  
- Section 3: Packaging
- Section 4: Quality Control
- Section 5: Storage Management
- Section 6: Distribution
- Section 7: Reports & Analytics

Technology Stack:
- PHP 7.4+
- MySQL/MariaDB
- Bootstrap 5
- Chart.js
- FontAwesome
- jQuery"

if errorlevel 1 (
    echo ERROR: Failed to create commit
    pause
    exit /b 1
)

echo [6/7] Pushing to GitHub...
echo NOTE: You may need to enter your GitHub credentials
git push -u origin main
if errorlevel 1 (
    echo.
    echo ERROR: Failed to push to GitHub
    echo.
    echo Possible solutions:
    echo 1. Check your internet connection
    echo 2. Verify GitHub repository exists and you have access
    echo 3. Ensure GitHub credentials are correct
    echo 4. Try using a Personal Access Token instead of password
    echo.
    echo Manual push command:
    echo git push -u origin main
    pause
    exit /b 1
)

echo [7/7] Upload completed successfully!
echo.
echo ========================================
echo SUCCESS: Project uploaded to GitHub!
echo Repository: https://github.com/Praveen061215/kya_food_production.git
echo ========================================
echo.
echo Next steps:
echo - Visit your GitHub repository to verify upload
echo - Set up branch protection rules if needed
echo - Configure GitHub Pages if you want web hosting
echo - Add collaborators if working in a team
echo.

:: Show git status
echo Current Git Status:
git status --short
echo.

:: Show remote info
echo Remote Repository:
git remote -v
echo.

echo Upload completed! Press any key to exit...
pause >nul
