#!/bin/bash
set -e

echo "Installing PHP MySQL extensions..."

# Try to install via package manager
if command -v apt-get &> /dev/null; then
    apt-get update
    apt-get install -y php-mysql php-mysqli php-pdo
elif command -v apk &> /dev/null; then
    apk add --no-cache php-mysqli php-pdo php-pdo_mysql
fi

# Verify extensions are loaded
echo "Checking PHP modules:"
php -m | grep -i mysql || echo "Warning: MySQL extensions may not be installed"
php -m | grep -i pdo || echo "Warning: PDO extension may not be installed"

echo "Build complete!"