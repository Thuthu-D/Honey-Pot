<?php
require_once __DIR__ . '/../includes/logger.php';

$path = $_SERVER['REQUEST_URI'] ?? '';

hp_log_event('trap_hit', [
    'notes' => 'baited path probed',
]);

// Serve a response that matches what the attacker expects to see,
// so automated scanners don't immediately flag this as a honeypot.

if (str_contains($path, '.env')) {
    header('Content-Type: text/plain');
    http_response_code(200);
    // Fake, harmless-looking env content - never real secrets.
    echo "APP_ENV=production\n";
    echo "APP_DEBUG=false\n";
    echo "DB_CONNECTION=mysql\n";
    echo "DB_HOST=127.0.0.1\n";
    exit;
}

if (str_contains($path, '.git/config')) {
    header('Content-Type: text/plain');
    http_response_code(200);
    echo "[core]\n\trepositoryformatversion = 0\n\tfilemode = true\n\tbare = false\n";
    exit;
}

if (str_contains($path, 'wp-login') || str_contains($path, 'wp-admin')) {
    // Redirect WordPress scanners into the same decoy login form.
    header('Location: /index.php');
    exit;
}

if (str_contains($path, 'phpmyadmin') || str_contains($path, 'pma')) {
    header('Content-Type: text/html');
    http_response_code(200);
    echo '<!DOCTYPE html><html><head><title>phpMyAdmin</title></head><body>'
       . '<h1>phpMyAdmin</h1><p>Please log in.</p></body></html>';
    exit;
}

// Default: generic 404 so it doesn't look suspicious for anything unmatched.
http_response_code(404);
header('Content-Type: text/html');
echo '<!DOCTYPE html><html><head><title>404 Not Found</title></head>'
   . '<body><h1>Not Found</h1><p>The requested URL was not found on this server.</p></body></html>';
