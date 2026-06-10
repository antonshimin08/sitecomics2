#!/bin/bash
# Quick Deployment Script
# Setup and deploy Comic Universe application
# Usage: bash deploy.sh

set -e

echo "🚀 Comic Universe - Quick Deployment Script"
echo "==========================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running as root (not required but recommended)
if [ "$EUID" -eq 0 ]; then 
    echo "⚠️  Running as root. Some commands may need adjustment."
fi

# Step 1: Check PHP version
echo -e "${YELLOW}Step 1: Checking PHP version...${NC}"
php_version=$(php -v | head -n 1 | awk '{print $2}')
echo "PHP version: $php_version"

if php -v | grep -q "PHP 8"; then
    echo -e "${GREEN}✓ PHP 8.x detected${NC}"
else
    echo -e "${RED}✗ PHP 8.x required${NC}"
    exit 1
fi

echo ""

# Step 2: Check required extensions
echo -e "${YELLOW}Step 2: Checking PHP extensions...${NC}"
required_extensions=("pdo" "json" "curl" "mbstring")

for ext in "${required_extensions[@]}"; do
    if php -m | grep -q "$ext"; then
        echo -e "${GREEN}✓ $ext extension installed${NC}"
    else
        echo -e "${RED}✗ $ext extension missing${NC}"
        exit 1
    fi
done

echo ""

# Step 3: Create .env file if not exists
echo -e "${YELLOW}Step 3: Setting up environment...${NC}"
if [ ! -f ".env" ]; then
    cp .env.example .env
    echo -e "${GREEN}✓ .env file created${NC}"
    echo -e "${YELLOW}⚠️  Please edit .env with your settings${NC}"
else
    echo -e "${GREEN}✓ .env file exists${NC}"
fi

echo ""

# Step 4: Create required directories
echo -e "${YELLOW}Step 4: Creating required directories...${NC}"
mkdir -p logs
mkdir -p cache
mkdir -p uploads
chmod 755 logs cache uploads
echo -e "${GREEN}✓ Directories created${NC}"

echo ""

# Step 5: Install Composer dependencies
echo -e "${YELLOW}Step 5: Installing Composer dependencies...${NC}"
if command -v composer &> /dev/null; then
    composer install --no-dev --optimize-autoloader
    echo -e "${GREEN}✓ Dependencies installed${NC}"
else
    echo -e "${YELLOW}⚠️  Composer not found. Skipping dependency installation${NC}"
fi

echo ""

# Step 6: Initialize database
echo -e "${YELLOW}Step 6: Initializing database...${NC}"
php setup-db.php

echo ""

# Step 7: Set file permissions
echo -e "${YELLOW}Step 7: Setting file permissions...${NC}"
chmod 755 .
chmod 644 *.php *.css
chmod 755 includes api logs cache uploads
chmod 644 .htaccess 2>/dev/null || true
echo -e "${GREEN}✓ Permissions set${NC}"

echo ""

# Step 8: Run tests
echo -e "${YELLOW}Step 8: Running deployment checks...${NC}"
php deploy-status.php

echo ""

# Summary
echo "==========================================="
echo -e "${GREEN}✅ Deployment completed successfully!${NC}"
echo ""
echo "Next steps:"
echo "1. Edit .env file with your production settings"
echo "2. Configure web server (Apache/Nginx)"
echo "3. Set up HTTPS certificate"
echo "4. Run functional tests: php tests/functional-test.php"
echo "5. Monitor logs: tail -f logs/app_*.log"
echo ""
echo "For detailed instructions, see: PRODUCTION_DEPLOYMENT.md"
