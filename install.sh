#!/usr/bin/env bash
# G2K Solar Manager - installer for macOS / Linux (XAMPP or system PHP)
# Usage: ./install.sh [options]   (see: ./install.sh --help)
set -euo pipefail
cd "$(dirname "$0")"

PHP_BIN="${PHP_BIN:-}"
for candidate in \
    "/Applications/XAMPP/xamppfiles/bin/php" \
    "/opt/lampp/bin/php" \
    "$(command -v php || true)"; do
    if [ -z "$PHP_BIN" ] && [ -n "$candidate" ] && [ -x "$candidate" ]; then
        PHP_BIN="$candidate"
    fi
done

if [ -z "$PHP_BIN" ]; then
    echo "[FAIL] php not found. Install XAMPP or set PHP_BIN=/path/to/php" >&2
    exit 1
fi

echo "Using PHP: $PHP_BIN"
exec "$PHP_BIN" scripts/install.php "$@"
