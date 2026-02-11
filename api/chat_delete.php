<?php
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data) || empty($data['session_ids']) || !is_array($data['session_ids'])) { http_response_code(400); echo json_encode(['error'=>'Missing session_ids']); exit; }
$ids = array_values(array_filter(array_map(function($v){ return is_string($v) ? trim($v) : null; }, $data['session_ids'])));
if (count($ids) === 0){ http_response_code(400); echo json_encode(['error'=>'No valid session ids']); exit; }

require_once __DIR__ . '/../includes/db.php';
try{
    // Build placeholders
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $pdo->beginTransaction();
    // Delete messages
    $stmt = $pdo->prepare("DELETE FROM chat_messages WHERE session_id IN ($placeholders)");
    $stmt->execute($ids);
    // Delete sessions
    $stmt2 = $pdo->prepare("DELETE FROM chat_sessions WHERE session_id IN ($placeholders)");
    $stmt2->execute($ids);
    $pdo->commit();
    echo json_encode(['ok'=>true, 'deleted_sessions' => $stmt2->rowCount(), 'deleted_messages' => $stmt->rowCount()]);
    exit;
}catch(Exception $e){
    if($pdo && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error'=>'DB delete failed','message'=>$e->getMessage()]);
    exit;
}
