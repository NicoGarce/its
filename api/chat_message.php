<?php
// Simple JSON-line message logger for chat messages
// POST: accepts JSON { session_id, sender (user|tech), text, ts }
// GET: returns JSON array of messages for ?session_id=... (most recent first)

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';

// Ensure the `chat_messages` table exists so normal DB inserts/reads succeed.
// This avoids the code immediately falling back to JSONL storage when the table
// is missing. If the DB user lacks CREATE TABLE privileges this will silently
// fail and the existing fallback will still apply.
try{
        $pdo->exec("CREATE TABLE IF NOT EXISTS `chat_messages` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `session_id` VARCHAR(64) NOT NULL,
            `sender` VARCHAR(32) NOT NULL,
            `text` TEXT,
            `meta` JSON DEFAULT NULL,
            `received_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_session` (`session_id`),
            KEY `idx_received_at` (`received_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
}catch(Exception $e){
        // Ignore - fallback to file-based storage will handle missing privileges/table
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET','POST'])) {
    http_response_code(405);
    echo json_encode(['error'=>'Method not allowed']); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data) || empty($data['session_id']) || empty($data['sender']) || !isset($data['text'])) {
        http_response_code(400);
        echo json_encode(['error'=>'Missing required fields']); exit;
    }
    $session_id = substr(trim($data['session_id']),0,64);
    $sender = substr(trim($data['sender']),0,32);
    $text = substr(trim($data['text']),0,2000);
    $ts = isset($data['ts']) ? $data['ts'] : date('Y-m-d H:i:s');
    // Only persist user-sent messages. Tech messages should not be inserted here.
    if (strtolower($sender) !== 'user') {
        // Return success so callers (tech UI) continue to work, but skip DB/file writes.
        echo json_encode(['ok' => true, 'skipped' => 'non-user-sender']); exit;
    }

    try{
        $stmt = $pdo->prepare("INSERT INTO chat_messages (session_id, sender, text, meta, received_at) VALUES (:session_id, :sender, :text, :meta, :received_at)");
        $meta = null;
        if (!empty($data['meta']) && is_array($data['meta'])) $meta = json_encode($data['meta'], JSON_UNESCAPED_UNICODE);
        $stmt->execute([
            ':session_id' => $session_id,
            ':sender' => $sender,
            ':text' => $text,
            ':meta' => $meta,
            ':received_at' => date('Y-m-d H:i:s')
        ]);
        echo json_encode(['ok'=>true]); exit;
    }catch(Exception $e){
        // fallback to JSON-line storage if DB insert fails (e.g., table missing)
        try{
            $dir = __DIR__ . '/../data'; if (!is_dir($dir)) @mkdir($dir, 0755, true);
            $file = $dir . '/chat_messages.jsonl';
            $entry = [
                'session_id' => $session_id,
                'sender' => $sender,
                'text' => $text,
                'ts' => isset($data['ts']) ? $data['ts'] : date('c'),
                'received_at' => date('c')
            ];
            file_put_contents($file, json_encode($entry, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL, FILE_APPEND|LOCK_EX);
            echo json_encode(['ok'=>true,'fallback'=>'jsonl','note'=>$e->getMessage()]); exit;
        }catch(Exception $e2){
            http_response_code(500);
            echo json_encode(['error'=>'DB insert failed and fallback failed','message'=>$e->getMessage(),'fallback_error'=>$e2->getMessage()]); exit;
        }
    }
}

// GET: return messages for session_id
$session = isset($_GET['session_id']) ? trim($_GET['session_id']) : '';
if (!$session){ echo json_encode([]); exit; }
try{
    // Return only user messages to clients
    $stmt = $pdo->prepare("SELECT sender, text, received_at FROM chat_messages WHERE session_id = :sid AND LOWER(sender) = 'user' ORDER BY received_at ASC");
    $stmt->execute([':sid' => $session]);
    $rows = $stmt->fetchAll();
    $out = [];
    foreach($rows as $r){
        $out[] = [ 'session_id' => $session, 'sender' => $r['sender'], 'text' => $r['text'], 'ts' => $r['received_at'], 'received_at' => $r['received_at'] ];
    }
    echo json_encode($out, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}catch(Exception $e){
    // fallback to JSON-line read if DB read fails
    $dir = __DIR__ . '/../data'; $file = $dir . '/chat_messages.jsonl'; $out = [];
    if ($session && is_readable($file)) {
        $fh = fopen($file, 'r');
        if ($fh) {
            while (($line = fgets($fh)) !== false) {
                $line = trim($line); if ($line === '') continue;
                $entry = json_decode($line, true); if (!is_array($entry)) continue;
                // only include user messages from the fallback file
                if (isset($entry['session_id']) && $entry['session_id'] === $session && isset($entry['sender']) && strtolower($entry['sender']) === 'user') $out[] = $entry;
            }
            fclose($fh);
        }
    }
    // include an error field to indicate DB read failed
    $resp = array_values($out);
    header('X-Chat-Message-DB-Error: ' . substr($e->getMessage(),0,200));
    echo json_encode($resp); exit;
}
