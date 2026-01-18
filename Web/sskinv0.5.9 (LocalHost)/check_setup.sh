#!/bin/bash
# ============================================
# Script untuk Quick Test Setup Localhost
# untuk Linux/Mac
# ============================================

echo ""
echo "========================================"
echo "  SSkIN Setup Checker"
echo "========================================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if PHP is available
echo "[1/6] Checking PHP..."
if command -v php &> /dev/null; then
    php -v | head -n 1
    echo -e "${GREEN}[OK] PHP found!${NC}"
else
    echo -e "${RED}[ERROR] PHP not found! Install PHP first.${NC}"
    exit 1
fi

echo ""

# Check if Composer is available
echo "[2/6] Checking Composer..."
if command -v composer &> /dev/null; then
    composer --version | head -n 1
    echo -e "${GREEN}[OK] Composer found!${NC}"
else
    echo -e "${YELLOW}[WARNING] Composer not found!${NC}"
    echo "Install from: https://getcomposer.org"
fi

echo ""

# Check if .env exists
echo "[3/6] Checking .env file..."
if [ -f ".env" ]; then
    echo -e "${GREEN}[OK] .env file found!${NC}"
    echo ""
    echo "--- Current .env Database Config ---"
    grep "^DB_" .env
    echo "------------------------------------"
else
    echo -e "${YELLOW}[WARNING] .env file not found!${NC}"
    if [ -f ".env.example" ]; then
        echo "Creating from .env.example..."
        cp .env.example .env
        echo -e "${GREEN}[OK] .env created! Please edit database credentials.${NC}"
    else
        echo -e "${RED}[ERROR] .env.example not found!${NC}"
    fi
fi

echo ""

# Check vendor folder
echo "[4/6] Checking Composer dependencies..."
if [ -d "vendor" ]; then
    echo -e "${GREEN}[OK] Vendor folder exists!${NC}"
else
    echo -e "${YELLOW}[WARNING] Vendor folder not found!${NC}"
    echo "Run: composer install"
fi

echo ""

# Check if MySQL is running
echo "[5/6] Checking MySQL service..."
if command -v mysql &> /dev/null; then
    if pgrep -x "mysqld" > /dev/null; then
        echo -e "${GREEN}[OK] MySQL service is running!${NC}"
    else
        echo -e "${YELLOW}[WARNING] MySQL process not found!${NC}"
        echo "Start MySQL service first"
    fi
else
    echo -e "${YELLOW}[WARNING] MySQL client not found in PATH${NC}"
fi

echo ""

# Test database connection info
echo "[6/6] Database connection info..."
echo "To test connection, open in browser:"
echo ""

# Detect folder name
FOLDER_NAME=$(basename "$PWD")
echo "http://localhost/$FOLDER_NAME/test_connection.php"

echo ""
echo "========================================"
echo "  Setup Check Complete!"
echo "========================================"
echo ""
echo "Next Steps:"
echo "1. Open the URL above in your browser"
echo "2. If connection fails, edit .env file"
echo "3. Make sure database 'edoc' is created"
echo "4. Import edoc.sql: mysql -u root -p edoc < edoc.sql"
echo "5. Run: composer install (if not done)"
echo ""
echo "Documentation:"
echo "- QUICK_START.md - Quick setup guide"
echo "- SETUP_LOCALHOST.md - Detailed guide"
echo "- TESTING_GUIDE.md - Testing checklist"
echo ""
