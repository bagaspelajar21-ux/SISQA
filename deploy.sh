
#!/bin/bash

set -e

# =====================================
# CONFIGURATION
# =====================================

PROJECT_DIR="/var/www/SISQA"
BRANCH="main"

echo "====================================="
echo "       DEPLOYMENT SISQA"
echo "====================================="

cd "$PROJECT_DIR"

# 1. Ambil update dari GitHub
echo "[1/8] Mengambil kode dari GitHub..."
git fetch origin "$BRANCH"
git reset --hard "origin/$BRANCH"

# 2. Install dependency PHP Laravel
echo "[2/8] Install Composer..."
composer install --no-dev --prefer-dist --optimize-autoloader

# 3. Install dependency Node.js Vue
echo "[3/8] Install NPM..."
npm ci

# 4. Build Vue + Vite
echo "[4/8] Build Vue..."
npm run build

# 5. Clear cache Laravel
echo "[5/8] Clear Laravel Cache..."
php artisan optimize:clear

# 6. Database migration
echo "[6/8] Database Migration..."
php artisan migrate --force

# 7. Optimasi Laravel
echo "[7/8] Optimize Laravel..."
php artisan optimize

# 8. Permission
echo "[8/8] Setting Permission..."
chmod -R 775 storage bootstrap/cache

echo "====================================="
echo "       DEPLOYMENT BERHASIL!"
echo "====================================="
