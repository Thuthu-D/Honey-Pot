<?php
require_once __DIR__ . '/db.php';

function hp_client_ip(): string {
    if (HP_TRUST_PROXY_HEADERS && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($parts[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function hp_all_headers(): array {
    if (function_exists('getallheaders')) {
        return getallheaders() ?: [];
    }
    $headers = [];
    foreach ($_SERVER as $key => $value) {
        if (str_starts_with($key, 'HTTP_')) {
            $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
            $headers[$name] = $value;
        }
    }
    return $headers;
}

/**
 * Logs a single honeypot event.
 *
 * @param string $eventType e.g. 'login_attempt', 'trap_hit', 'page_view'
 * @param array $extra optional overrides: username_attempt, password_attempt, notes
 */
function hp_log_event(string $eventType, array $extra = []): void {
    $pdo = hp_get_db();

    $stmt = $pdo->prepare("
        INSERT INTO events
            (created_at, event_type, ip, method, path, user_agent, referer,
             username_attempt, password_attempt, post_data, headers, notes)
        VALUES
            (:created_at, :event_type, :ip, :method, :path, :user_agent, :referer,
             :username_attempt, :password_attempt, :post_data, :headers, :notes)
    ");

    // Never store the whole raw POST if it's huge/binary; cap it.
    $postData = $_POST ? json_encode($_POST) : null;
    if ($postData !== null && strlen($postData) > 4000) {
        $postData = substr($postData, 0, 4000) . '...[truncated]';
    }

    $stmt->execute([
        ':created_at' => (new DateTime())->format(DateTime::ATOM),
        ':event_type' => $eventType,
        ':ip' => hp_client_ip(),
        ':method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
        ':path' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ':referer' => $_SERVER['HTTP_REFERER'] ?? null,
        ':username_attempt' => $extra['username_attempt'] ?? null,
        ':password_attempt' => $extra['password_attempt'] ?? null,
        ':post_data' => $postData,
        ':headers' => json_encode(hp_all_headers()),
        ':notes' => $extra['notes'] ?? null,
    ]);
}
