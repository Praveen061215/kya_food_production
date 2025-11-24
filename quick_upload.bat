@echo off
echo Quick Git Upload - KYA Food Production
echo ========================================

cd /d "c:\xampp\htdocs\kya-food-production"

:: Add all changes
git add .

:: Create commit with timestamp
for /f "tokens=2 delims==" %%a in ('wmic OS Get localdatetime /value') do set "dt=%%a"
set "timestamp=%dt:~0,4%-%dt:~4,2%-%dt:~6,2% %dt:~8,2%:%dt:~10,2%"

git commit -m "Daily update: %timestamp%"

:: Push to GitHub
git push origin main

if %errorlevel% equ 0 (
    echo SUCCESS: Changes uploaded to GitHub!
) else (
    echo ERROR: Upload failed. Check connection and credentials.
)

timeout /t 3 >nul
