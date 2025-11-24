# KYA Food Production - Auto Git Upload Script
# PowerShell version for better Windows compatibility

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "KYA Food Production - Auto Git Upload" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# Set project directory
$projectPath = "c:\xampp\htdocs\kya-food-production"
Set-Location $projectPath

# Check if git is installed
try {
    $gitVersion = git --version
    Write-Host "Git found: $gitVersion" -ForegroundColor Green
} catch {
    Write-Host "ERROR: Git is not installed or not in PATH" -ForegroundColor Red
    Write-Host "Please install Git from https://git-scm.com/download/win" -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
    exit 1
}

# Initialize git if not already done
if (-not (Test-Path ".git")) {
    Write-Host "Initializing Git repository..." -ForegroundColor Yellow
    git init
    Write-Host "Git repository initialized." -ForegroundColor Green
} else {
    Write-Host "Git repository already exists." -ForegroundColor Green
}

# Configure git user (update with your details)
Write-Host "Configuring Git user..." -ForegroundColor Yellow
git config user.name "Praveen061215"
git config user.email "{{ YOUR_EMAIL_ADDRESS_HERE }}"

# Check if remote origin exists
try {
    $remoteUrl = git remote get-url origin 2>$null
    if ($remoteUrl) {
        Write-Host "Remote origin already exists: $remoteUrl" -ForegroundColor Green
    }
} catch {
    Write-Host "Adding remote origin..." -ForegroundColor Yellow
    git remote add origin https://github.com/Praveen061215/kya_food_production.git
    Write-Host "Remote origin added." -ForegroundColor Green
}

# Create .gitignore if not exists
if (-not (Test-Path ".gitignore")) {
    Write-Host "Creating .gitignore file..." -ForegroundColor Yellow
    @"
# KYA Food Production - Git Ignore

# System files
.DS_Store
Thumbs.db
desktop.ini

# IDE files
.vscode/
.idea/
*.swp
*.swo

# Logs
*.log
logs/

# Temporary files
tmp/
temp/
cache/

# Environment files
.env
.env.local

# Database backups
*.sql.backup
database/backups/

# Uploaded files
uploads/
files/

# Vendor dependencies
vendor/
node_modules/

# Configuration files with sensitive data
config/database_local.php
config/secrets.php
"@ | Out-File -FilePath ".gitignore" -Encoding UTF8
    Write-Host ".gitignore created." -ForegroundColor Green
}

# Add all files
Write-Host "Adding files to Git..." -ForegroundColor Yellow
git add .

# Create commit message with timestamp
$timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
$commitMessage = "Auto upload: KYA Food Production updates - $timestamp"

# Commit changes
Write-Host "Committing changes..." -ForegroundColor Yellow
try {
    git commit -m $commitMessage
    Write-Host "Changes committed successfully." -ForegroundColor Green
} catch {
    Write-Host "No changes to commit or commit failed." -ForegroundColor Yellow
}

# Push to GitHub
Write-Host "Pushing to GitHub..." -ForegroundColor Yellow
try {
    git push -u origin main
    Write-Host "" 
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "SUCCESS: Project uploaded to GitHub!" -ForegroundColor Green
    Write-Host "Repository: https://github.com/Praveen061215/kya_food_production.git" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
} catch {
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Red
    Write-Host "ERROR: Failed to push to GitHub" -ForegroundColor Red
    Write-Host "Possible reasons:" -ForegroundColor Yellow
    Write-Host "1. Repository doesn't exist or you don't have access" -ForegroundColor Yellow
    Write-Host "2. Authentication required (use GitHub Desktop or setup SSH keys)" -ForegroundColor Yellow
    Write-Host "3. Network connectivity issues" -ForegroundColor Yellow
    Write-Host "========================================" -ForegroundColor Red
}

Write-Host ""
Read-Host "Press Enter to exit"
