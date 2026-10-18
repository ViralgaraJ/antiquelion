<?php
// Router for PHP's built-in server (local development only).
// Replaces the Apache .htaccess rewrite rules: serve real files as-is,
// send everything else to index.php so Osclass permalinks work.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && file_exists(__DIR__ . $path)) {
    return false;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';
