#!/bin/sh
# Start the local development site at http://localhost:8000
# Requires XAMPP's MySQL to be running (XAMPP manager-osx -> Start MySQL).
cd "$(dirname "$0")"
PHP=/Applications/XAMPP/xamppfiles/bin/php
[ -x "$PHP" ] || PHP=php
exec "$PHP" -d memory_limit=256M -S localhost:8000 dev-router.php
