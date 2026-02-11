<?php
// Simple chat session start logger
// Accepts JSON POST with: name, location, issue, contact
// Stores a JSON-line record in ../data/chat_sessions.jsonl

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Basic sanitization
$name = isset($data['name']) ? substr(trim($data['name']), 0, 200) : '';
$location = isset($data['location']) ? substr(trim($data['location']), 0, 200) : '';
$issue = isset($data['issue']) ? substr(trim($data['issue']), 0, 1000) : '';
$contact = isset($data['contact']) ? substr(trim($data['contact']), 0, 200) : '';

// Client ip detection
function get_client_ip() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($parts[0]);
    }
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

$client_ip = get_client_ip();
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

// Load optional config and auth helper
$config = null;
if (is_readable(__DIR__ . '/../includes/config.php')) {
    $config = include __DIR__ . '/../includes/config.php';
}
$auth_user = null;
if (is_readable(__DIR__ . '/../includes/auth.php')) {
    include_once __DIR__ . '/../includes/auth.php';
    if (isset($_COOKIE['jwt'])) {
        $payload = validateJWT($_COOKIE['jwt']);
        if ($payload && is_array($payload)) {
            $auth_user = [
                'user_id' => $payload['userId'] ?? null,
                'username' => $payload['username'] ?? null,
            ];
        }
    }
}

// Enforce authentication if configured
if ($config && ($config->require_login ?? false)) {
    if (!$auth_user || empty($auth_user['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
}

// Count recent session starts from this IP in the last N minutes
function count_recent_starts($ip, $minutes = 60){
    $file = __DIR__ . '/../data/chat_sessions.jsonl';
    if (!is_readable($file)) return 0;
    $fh = fopen($file, 'r'); if (!$fh) return 0;
    $now = time(); $count = 0;
    while (($line = fgets($fh)) !== false){
        $line = trim($line); if ($line === '') continue;
        $entry = json_decode($line, true); if (!is_array($entry)) continue;
        if (isset($entry['client_ip']) && $entry['client_ip'] === $ip && !empty($entry['received_at'])){
            $t = strtotime($entry['received_at']); if ($t && ($now - $t) <= ($minutes * 60)) $count++;
        }
    }
    fclose($fh);
    return $count;
}

// Basic heuristic flags (configurable)
$flagged = false;
$flag_reasons = [];
$minLen = 15; $ipWindow = 60; $ipLimit = 3;
if ($config) {
    $minLen = $config->min_issue_length ?? $minLen;
    $ipWindow = $config->ip_window_minutes ?? $ipWindow;
    $ipLimit = $config->ip_limit ?? $ipLimit;
}
if (mb_strlen($issue) < $minLen) {
    $flagged = true;
    $flag_reasons[] = 'issue too short';
}
$recent = count_recent_starts($client_ip, $ipWindow);
if ($recent >= $ipLimit) {
    $flagged = true;
    $flag_reasons[] = 'multiple sessions from same IP';
}

// Generate server-side id
$session_id = 's' . time() . bin2hex(random_bytes(4));

$identifier_type = isset($data['identifier_type']) ? substr(trim($data['identifier_type']),0,50) : null;
$identifier = isset($data['identifier']) ? substr(trim($data['identifier']),0,200) : null;

$record = [
    'session_id' => $session_id,
    'name' => $name,
    'location' => $location,
    'issue' => $issue,
    'contact' => $contact,
    'identifier_type' => $identifier_type,
    'identifier' => $identifier,
    'auth_user' => $auth_user,
    'flagged' => $flagged,
    'flagged_reason' => implode('; ', $flag_reasons),
    'client_ip' => $client_ip,
    'user_agent' => $ua,
    'received_at' => date('c')
];
// Persist to database
require_once __DIR__ . '/../includes/db.php';
try {
    $stmt = $pdo->prepare("INSERT INTO chat_sessions (session_id, name, identifier_type, identifier, location, issue, contact, auth_user, flagged, flagged_reason, client_ip, user_agent, received_at)
        VALUES (:session_id, :name, :identifier_type, :identifier, :location, :issue, :contact, :auth_user, :flagged, :flagged_reason, :client_ip, :user_agent, :received_at)");
    $stmt->execute([
        ':session_id' => $session_id,
        ':name' => $name,
        ':identifier_type' => $identifier_type,
        ':identifier' => $identifier,
        ':location' => $location,
        ':issue' => $issue,
        ':contact' => $contact,
        ':auth_user' => $auth_user ? json_encode($auth_user, JSON_UNESCAPED_UNICODE) : null,
        ':flagged' => $flagged ? 1 : 0,
        ':flagged_reason' => implode('; ', $flag_reasons),
        ':client_ip' => $client_ip,
        ':user_agent' => $ua,
        ':received_at' => date('Y-m-d H:i:s')
    ]);
    $resp = ['ok' => true, 'session_id' => $session_id, 'client_ip' => $client_ip];
    if ($flagged) { $resp['flagged'] = true; $resp['flagged_reason'] = implode('; ', $flag_reasons); }
    echo json_encode($resp);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
    exit;
}
