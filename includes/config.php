<?php
// App configuration
// Toggle this to true to require users to be authenticated before starting a chat.
$CHAT_REQUIRE_LOGIN = false;

// Minimum issue length to accept without flagging
$CHAT_MIN_ISSUE_LENGTH = 15;

// Per-IP session limit within minutes window
$CHAT_IP_LIMIT = 3;
$CHAT_IP_WINDOW_MINUTES = 60;

// Environment detection: allow override via ITS_ENV environment variable.
$APP_ENV = getenv('ITS_ENV') ?: (isset($_SERVER['SERVER_ADDR']) && ($_SERVER['SERVER_ADDR'] !== '127.0.0.1' && $_SERVER['SERVER_ADDR'] !== '::1') ? 'production' : 'development');

// Base path for URLs (allow override via ITS_BASE_PATH env var)
// Read environment override, but sanitize so only a path (not full URL) is used
$envBase = getenv('ITS_BASE_PATH');
$APP_BASE = ($envBase === false) ? null : trim($envBase);

if ($APP_BASE === null || $APP_BASE === '') {
    // Heuristic: on localhost, app likely sits in /its/ locally; on production default to '/'
    $host = $_SERVER['HTTP_HOST'] ?? '';
    // Treat localhost, 127.0.0.1 and common local LAN addresses as development installs
    if (stripos($host, 'localhost') !== false
        || stripos($host, '127.0.0.1') !== false
        || (isset($_SERVER['SERVER_ADDR']) && preg_match('/^192\\.168\\./', $_SERVER['SERVER_ADDR']))
    ) {
        $APP_BASE = '/its/';
    } else {
        $APP_BASE = '/';
    }
}

// If an env var was provided and looks like a full URL, extract only the path component
if (!empty($envBase)) {
    if (strpos($envBase, '://') !== false) {
        $p = parse_url($envBase, PHP_URL_PATH);
        $p = $p ?: '/';
        $APP_BASE = '/' . trim($p, '/') . '/';
    } else {
        // Ensure it starts and ends with '/'
        $APP_BASE = '/' . trim($APP_BASE, '/') . '/';
    }
}

return (object)[
    'require_login' => $CHAT_REQUIRE_LOGIN,
    'min_issue_length' => $CHAT_MIN_ISSUE_LENGTH,
    'ip_limit' => $CHAT_IP_LIMIT,
    'ip_window_minutes' => $CHAT_IP_WINDOW_MINUTES,
    'env' => $APP_ENV,
    'base' => $APP_BASE,
];
