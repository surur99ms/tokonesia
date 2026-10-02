#!/bin/bash
# ============================================================
# Tokonesia — Deployment Migration Script
# ============================================================
DEPLOYPATH="$HOME/tokonesia.corefive.my.id"
LOGFILE="$DEPLOYPATH/migration.log"

echo "=== [START] Deployment Migration: $(date) ===" > "$LOGFILE"

# 1. Cari binary PHP yang aktif di cPanel
PHP_BIN=""
for candidate in "/usr/local/bin/php" "php" "/usr/bin/php" "/opt/cpanel/ea-php81/root/usr/bin/php" "/opt/alt/php81/usr/bin/php"; do
    if command -v "$candidate" >/dev/null 2>&1; then
        PHP_BIN="$candidate"
        break
    fi
done

if [ -z "$PHP_BIN" ]; then
    PHP_BIN="/usr/local/bin/php"
fi

echo "PHP Binary: $PHP_BIN" >> "$LOGFILE"

# 2. Jalankan migrate.php
if [ -f "$DEPLOYPATH/migrate.php" ]; then
    echo "Menjalankan migrate.php..." >> "$LOGFILE"
    $PHP_BIN "$DEPLOYPATH/migrate.php" migrate >> "$LOGFILE" 2>&1
    STATUS=$?
    echo "Status Code: $STATUS" >> "$LOGFILE"
else
    echo "ERROR: File $DEPLOYPATH/migrate.php tidak ditemukan!" >> "$LOGFILE"
fi

echo "=== [END] Deployment Migration: $(date) ===" >> "$LOGFILE"
