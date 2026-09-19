#!/bin/bash
set -e

echo "🚀 Starting SolarManager..."

# Wait for MySQL
echo "⏳ Waiting for MySQL..."
until php -r "
    try {
        new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT'), getenv('DB_USER'), getenv('DB_PASS'));
        echo 'Connected!';
    } catch (PDOException \$e) {
        echo 'Waiting...';
        exit(1);
    }
" 2>/dev/null; do
    sleep 2
done
echo "✅ MySQL is ready!"

# Create database if not exists
echo "📦 Creating database if not exists..."
php -r "
    \$pdo = new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT'), getenv('DB_USER'), getenv('DB_PASS'));
    \$pdo->exec('CREATE DATABASE IF NOT EXISTS solar_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    echo 'Database ready';
" 2>/dev/null && echo "✅ Database ready" || echo "⚠️ Database creation skipped"

# Import schema if database is empty
TABLE_COUNT=$(php -r "
    \$pdo = new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=solar_manager', getenv('DB_USER'), getenv('DB_PASS'));
    echo \$pdo->query('SHOW TABLES')->rowCount();
" 2>/dev/null || echo "0")

if [ "$TABLE_COUNT" = "0" ]; then
    echo "📦 Importing database schema..."
    php -r "
        \$pdo = new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=solar_manager', getenv('DB_USER'), getenv('DB_PASS'));
        \$sql = file_get_contents('/var/www/sql/schema.sql');
        \$pdo->exec(\$sql);
    " 2>/dev/null && echo "✅ Schema imported!" || echo "⚠️ Schema import failed"
else
    echo "✅ Database already has $TABLE_COUNT tables"
fi

# Create upload directory
mkdir -p /var/www/html/assets/img/uploads
chmod 777 /var/www/html/assets/img/uploads

echo "🎉 SolarManager is ready at http://localhost:8088"

# Start Apache
exec apache2-foreground
