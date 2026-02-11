<?php
header('Content-Type: application/json');
$path = __DIR__ . '/../data/incident_log.json';
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET'){
    $log = [];
    if (is_readable($path)) $log = json_decode(@file_get_contents($path), true) ?: [];
    echo json_encode(['log' => $log]);
    exit;
}
if ($method === 'POST'){
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: [];
    $log = [];
    if (is_readable($path)) $log = json_decode(@file_get_contents($path), true) ?: [];
    $entry = ['ts'=>date('c'), 'actor'=>$data['actor'] ?? 'system', 'endpoint'=>$data['endpoint'] ?? null, 'note'=>$data['note'] ?? null];
    $log[] = $entry;
    @file_put_contents($path, json_encode($log));
    echo json_encode(['ok'=>true,'entry'=>$entry]);
    exit;
}
http_response_code(405); echo json_encode(['error'=>'method not allowed']); exit;
