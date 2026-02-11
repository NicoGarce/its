<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/db.php';

$out = [];
$filterSession = isset($_GET['session_id']) ? trim($_GET['session_id']) : null;
try{
    // By default, return only active sessions (ended = 0). Use ?show_ended=1 to include ended sessions.
    $showEnded = isset($_GET['show_ended']) && ($_GET['show_ended'] === '1' || $_GET['show_ended'] === 'true');
    if ($filterSession) {
        if ($showEnded) {
            $stmt = $pdo->prepare("SELECT * FROM chat_sessions WHERE session_id = :sid LIMIT 1");
            $stmt->execute([':sid' => $filterSession]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM chat_sessions WHERE session_id = :sid AND (ended = 0 OR ended IS NULL) LIMIT 1");
            $stmt->execute([':sid' => $filterSession]);
        }
        $rows = $stmt->fetchAll();
    } else {
        if ($showEnded) {
            $stmt = $pdo->query("SELECT * FROM chat_sessions ORDER BY created_at DESC LIMIT 1000");
            $rows = $stmt->fetchAll();
        } else {
            $stmt = $pdo->prepare("SELECT * FROM chat_sessions WHERE (ended = 0 OR ended IS NULL) ORDER BY created_at DESC LIMIT 1000");
            $stmt->execute();
            $rows = $stmt->fetchAll();
        }
    }
    foreach($rows as $r){
        if (!empty($r['auth_user']) && is_string($r['auth_user'])){
            $r['auth_user'] = json_decode($r['auth_user'], true);
        }
        // Normalize boolean/flag fields to consistent types so JSON consumers
        // don't receive string values like "0" which can be truthy in JS.
        if (array_key_exists('ended', $r)) {
            $r['ended'] = $r['ended'] === null ? 0 : (int)$r['ended'];
        }
        if (array_key_exists('flagged', $r)) {
            $r['flagged'] = $r['flagged'] === null ? 0 : (int)$r['flagged'];
        }
        $out[$r['session_id']] = $r;
    }
    $count = count($out);
    echo json_encode(['sessions' => $out, 'count' => $count], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    exit;
}catch(Exception $e){
    http_response_code(500);
    // Log server-side error but do not expose internal messages to clients.
    error_log('api/chat_sessions.php exception: ' . $e->getMessage());
    echo json_encode(['sessions' => new stdClass(), 'count' => 0], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    exit;
}
