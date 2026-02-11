<?php
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
// Require an explicit confirmation string to avoid accidental flushes
if (!is_array($data) || !isset($data['confirm']) || $data['confirm'] !== 'FLUSH') { http_response_code(400); echo json_encode(['error'=>'Missing or invalid confirmation']); exit; }

require_once __DIR__ . '/../includes/db.php';
try{
    $pdo->beginTransaction();
    // Delete all messages
    $stmt = $pdo->prepare("DELETE FROM chat_messages");
    $stmt->execute();
    $deleted_messages = $stmt->rowCount();
    // Delete all sessions
    $stmt2 = $pdo->prepare("DELETE FROM chat_sessions");
    $stmt2->execute();
    $deleted_sessions = $stmt2->rowCount();
    $pdo->commit();
    echo json_encode(['ok'=>true, 'deleted_sessions' => $deleted_sessions, 'deleted_messages' => $deleted_messages]);
    exit;
}catch(Exception $e){
    if($pdo && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error'=>'DB flush failed','message'=>$e->getMessage()]);
    exit;
}
