# Quick Deployment Script for Windows
# Setup and deploy Comic Universe application
# Usage: powershell -ExecutionPolicy Bypass -File deploy.ps1

Write-Host "🚀 Comic Universe - Quick Deployment Script (Windows)" -ForegroundColor Cyan
Write-Host "=====================================================" -ForegroundColor Cyan
Write-Host ""

# Function to check command
function Test-Command {
    param([string]$Command)
    $null = Get-Command $Command -ErrorAction SilentlyContinue
    return $?
}

# Step 1: Check PHP version
Write-Host "Step 1: Checking PHP version..." -ForegroundColor Yellow
$phpVersion = php -v 2>$null | Select-Object -First 1

if ($phpVersion) {
    Write-Host "PHP version: $phpVersion" -ForegroundColor Green
    if ($phpVersion -match "PHP 8") {
        Write-Host "✓ PHP 8.x detected" -ForegroundColor Green
    } else {
        Write-Host "✗ PHP 8.x required" -ForegroundColor Red
        exit 1
    }
} else {
    Write-Host "✗ PHP not found. Please install PHP 8.1+" -ForegroundColor Red
    exit 1
}

Write-Host ""

# Step 2: Check required extensions
Write-Host "Step 2: Checking PHP extensions..." -ForegroundColor Yellow
$requiredExtensions = @("pdo", "json", "curl", "mbstring")

foreach ($ext in $requiredExtensions) {
    $extensions = php -m 2>$null
    if ($extensions -match $ext) {
        Write-Host "✓ $ext extension installed" -ForegroundColor Green
    } else {
        Write-Host "✗ $ext extension missing" -ForegroundColor Red
    }
}

Write-Host ""

# Step 3: Create .env file if not exists
Write-Host "Step 3: Setting up environment..." -ForegroundColor Yellow
if (-Not (Test-Path ".env")) {
    if (Test-Path ".env.example") {
        Copy-Item ".env.example" ".env"
        Write-Host "✓ .env file created" -ForegroundColor Green
        Write-Host "⚠️  Please edit .env with your settings" -ForegroundColor Yellow
    } else {
        Write-Host "✗ .env.example not found" -ForegroundColor Red
    }
} else {
    Write-Host "✓ .env file exists" -ForegroundColor Green
}

Write-Host ""

# Step 4: Create required directories
Write-Host "Step 4: Creating required directories..." -ForegroundColor Yellow
@("logs", "cache", "uploads") | ForEach-Object {
    if (-Not (Test-Path $_)) {
        New-Item -ItemType Directory -Path $_ | Out-Null
        Write-Host "✓ Created directory: $_" -ForegroundColor Green
    } else {
        Write-Host "✓ Directory exists: $_" -ForegroundColor Green
    }
}

Write-Host ""

# Step 5: Install Composer dependencies
Write-Host "Step 5: Installing Composer dependencies..." -ForegroundColor Yellow
if (Test-Command composer) {
    composer install --no-dev --optimize-autoloader
    Write-Host "✓ Dependencies installed" -ForegroundColor Green
} else {
    Write-Host "⚠️  Composer not found. Skipping dependency installation" -ForegroundColor Yellow
    Write-Host "   Download from: https://getcomposer.org/download/" -ForegroundColor Yellow
}

Write-Host ""

# Step 6: Initialize database
Write-Host "Step 6: Initializing database..." -ForegroundColor Yellow
php setup-db.php

Write-Host ""

# Step 7: Run deployment checks
Write-Host "Step 7: Running deployment checks..." -ForegroundColor Yellow
php deploy-status.php

Write-Host ""

# Summary
Write-Host "=====================================================" -ForegroundColor Cyan
Write-Host "✅ Deployment completed successfully!" -ForegroundColor Green
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "1. Edit .env file with your production settings"
Write-Host "2. Configure web server (IIS or Apache)"
Write-Host "3. Set up HTTPS certificate"
Write-Host "4. Run functional tests: php tests/functional-test.php"
Write-Host "5. Monitor logs: tail -f logs/app_*.log"
Write-Host ""
Write-Host "For detailed instructions, see: PRODUCTION_DEPLOYMENT.md"
Write-Host ""
