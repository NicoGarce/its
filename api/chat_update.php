<?php
// Update chat session status: on_the_way, pending, done
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data) || empty($data['session_id']) || empty($data['status'])) { http_response_code(400); echo json_encode(['error'=>'Missing parameters']); exit; }
$session_id = substr(trim($data['session_id']),0,64);
$status = substr(trim($data['status']),0,40);
$operator = isset($data['operator']) ? substr(trim($data['operator']),0,200) : null;

require_once __DIR__ . '/../includes/db.php';
try{
	if ($status === 'done' || $status === 'ended'){
		$stmt = $pdo->prepare('UPDATE chat_sessions SET ended = 1, ended_at = :ended_at, status = :status WHERE session_id = :sid');
		$stmt->execute([':ended_at' => date('Y-m-d H:i:s'), ':status' => 'done', ':sid' => $session_id]);
	} elseif ($status === 'on_the_way' || $status === 'pending'){
		$stmt = $pdo->prepare('UPDATE chat_sessions SET status = :status WHERE session_id = :sid');
		$stmt->execute([':status' => $status, ':sid' => $session_id]);
	} elseif ($status === 'flagged'){
		$reason = !empty($data['reason']) ? substr(trim($data['reason']),0,500) : null;
		$stmt = $pdo->prepare('UPDATE chat_sessions SET flagged = 1, flagged_reason = :reason WHERE session_id = :sid');
		$stmt->execute([':reason'=>$reason, ':sid'=>$session_id]);
	} elseif ($status === 'unflag'){
		$stmt = $pdo->prepare('UPDATE chat_sessions SET flagged = 0, flagged_reason = NULL WHERE session_id = :sid');
		$stmt->execute([':sid'=>$session_id]);
	} else {
		// default: set status field
		$stmt = $pdo->prepare('UPDATE chat_sessions SET status = :status WHERE session_id = :sid');
		$stmt->execute([':status'=>$status, ':sid'=>$session_id]);
	}
	echo json_encode(['ok'=>true]); exit;
}catch(Exception $e){ http_response_code(500); echo json_encode(['error'=>'DB update failed','message'=>$e->getMessage()]); exit; }
