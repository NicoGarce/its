<?php
// api/reserve.php - Handle reservation
require_once '../includes/db.php';
require_once '../includes/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = sanitizeInput($_POST['studentId']);
    $resource = sanitizeInput($_POST['emcResource']);
    $time = $_POST['reservationTime'];

    // Check availability (placeholder)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE resource = ? AND datetime = ?");
    $stmt->execute([$resource, $time]);
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        $stmt = $pdo->prepare("INSERT INTO reservations (student_id, resource, datetime, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$studentId, $resource, $time]);

        // Send email confirmation
        sendEmail($studentId . '@example.com', 'EMC Reservation Confirmation', 'Your reservation has been made.');

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Slot not available']);
    }
}
?>