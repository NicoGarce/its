<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET'){
    http_response_code(405);
    echo json_encode(['error'=>'Method not allowed']); exit;
}
$session = isset($_GET['session_id']) ? trim($_GET['session_id']) : '';
if (!$session){ echo json_encode(['error'=>'missing session_id']); exit; }
try{
    $stmt = $pdo->prepare("SELECT sender, text, meta, received_at FROM chat_messages WHERE session_id = :sid ORDER BY received_at ASC");
    $stmt->execute([':sid' => $session]);
    $rows = $stmt->fetchAll();
    $out = [];
    foreach($rows as $r){
        $out[] = [ 'sender' => $r['sender'], 'text' => $r['text'], 'meta' => $r['meta'], 'ts' => $r['received_at'] ];
    }
    echo json_encode($out, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}catch(Exception $e){
    http_response_code(500);
    echo json_encode(['error'=>'db_error','message'=>substr($e->getMessage(),0,200)]); exit;
}
