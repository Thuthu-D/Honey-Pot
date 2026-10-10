<?php
require_once __DIR__ . '/../config.php';

function hp_get_db(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $isNew = !file_exists(HP_DB_PATH);

    $pdo = new PDO('sqlite:' . HP_DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA journal_mode = WAL;');

    if ($isNew) {
        $pdo->exec("
            CREATE TABLE events (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                created_at TEXT NOT NULL,
                event_type TEXT NOT NULL,
                ip TEXT NOT NULL,
                method TEXT NOT NULL,
                path TEXT NOT NULL,
                user_agent TEXT,
                referer TEXT,
                username_attempt TEXT,
                password_attempt TEXT,
                post_data TEXT,
                headers TEXT,
                notes TEXT
            );
        ");
        $pdo->exec("CREATE INDEX idx_events_created_at ON events(created_at);");
        $pdo->exec("CREATE INDEX idx_events_ip ON events(ip);");
        $pdo->exec("CREATE INDEX idx_events_type ON events(event_type);");
    }

    return $pdo;
}
