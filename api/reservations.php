<?php
// api/reservations.php - Get reservations
require_once '../includes/db.php';

header('Content-Type: application/json');

$stmt = $pdo->prepare("SELECT * FROM reservations WHERE datetime > NOW() ORDER BY datetime ASC LIMIT 10");
$stmt->execute();
$reservations = $stmt->fetchAll();

echo json_encode($reservations);
?>