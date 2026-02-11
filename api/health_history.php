<?php
header('Content-Type: application/json');
$path = __DIR__ . '/../data/health_history.json';
$hist = [];
if (is_readable($path)){
    $raw = @file_get_contents($path);
    $hist = json_decode($raw, true) ?: [];
}
// return only the last N points (configurable) and ensure recent window
$maxPoints = 720; // keep up to ~6 hours at 30s resolution
if (count($hist) > $maxPoints) $hist = array_slice($hist, -$maxPoints);
echo json_encode(['history' => $hist]);
exit;
