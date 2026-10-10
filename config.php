<?php
/**
 * Honeypot configuration.
 * Edit the values below before deploying.
 */

// Timezone used for all logged timestamps
date_default_timezone_set('UTC');

// Display name shown in the fake decoy panel (make it look like a real internal tool)
define('HP_SITE_NAME', 'Internal Admin Portal');

// Dashboard login credentials.
// Generate a hash with: php -r "echo password_hash('yourpassword', PASSWORD_DEFAULT);"
define('HP_DASHBOARD_USER', 'admin');
define('HP_DASHBOARD_PASS_HASH', '$2y$10$CHANGE.ME.RUN.THE.COMMAND.ABOVE.TO.GENERATE.A.REAL.HASH');

// Path to the SQLite database file. MUST live outside the web root in production,
// or be protected via .htaccess (already handled for the default location below).
define('HP_DB_PATH', __DIR__ . '/data/honeypot.sqlite');

// Session name for the dashboard (kept distinct so it doesn't collide with anything else)
define('HP_SESSION_NAME', 'hp_dash_sess');

// If true, real client IP is taken from X-Forwarded-For when present.
// Only enable this if you KNOW your deployment sits behind a trusted proxy/CDN,
// otherwise attackers can spoof their logged IP.
define('HP_TRUST_PROXY_HEADERS', false);
