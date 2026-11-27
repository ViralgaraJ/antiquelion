<?php
// Router for PHP's built-in server (local development only).
// Replaces the Apache .htaccess rewrite rules: serve real files as-is,
// send everything else to index.php so Osclass permalinks work.

// Never run on a real web server: the file is deployed with the repo (ANTIQUE-7).
if (PHP_SAPI !== 'cli-server') {
    http_response_code(404);
    exit;
}

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$decoded = rawurldecode($path);

// Same as the server rules: the .htaccess in uploads/al-private (private files),
// docs/.htaccess, .github/.htaccess, tests/.htaccess and the root snippet in
// docs/server/root-htaccess-snippet.conf (developer files).
$denied_dirs = array('/oc-content/uploads/al-private/', '/docs/', '/.github/', '/tests/', '/.git/', '/_local_tools/');
foreach ($denied_dirs as $dir) {
    if (strpos($decoded, $dir) === 0 || $decoded === rtrim($dir, '/')) {
        http_response_code(403);
        exit('Forbidden');
    }
}
if (preg_match('#(^|/)(CLAUDE\.md|README\.md|config-sample\.php|dev-router\.php|dev-server\.sh|CHANGELOG\.txt|\.gitignore)$#', $decoded)) {
    http_response_code(403);
    exit('Forbidden');
}

if ($path !== '/' && file_exists(__DIR__ . $path)) {
    return false;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';
