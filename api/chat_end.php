<?php
// Mark a chat session ended (append to chat_sessions.jsonl)
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data) || empty($data['session_id'])) { http_response_code(400); echo json_encode(['error'=>'Missing session_id']); exit; }
$session_id = substr(trim($data['session_id']),0,64);
$operator = isset($data['operator']) ? substr(trim($data['operator']),0,200) : null;

require_once __DIR__ . '/../includes/db.php';
try{
	$stmt = $pdo->prepare('UPDATE chat_sessions SET ended = 1, ended_at = :ended_at, status = :status WHERE session_id = :sid');
	$stmt->execute([':ended_at' => date('Y-m-d H:i:s'), ':status' => 'ended', ':sid' => $session_id]);
	echo json_encode(['ok'=>true]); exit;
}catch(Exception $e){ http_response_code(500); echo json_encode(['error'=>'DB update failed','message'=>$e->getMessage()]); exit; }
