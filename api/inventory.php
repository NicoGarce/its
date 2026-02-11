<?php
// api/inventory.php - API endpoint for inventory data
require_once '../includes/helpers.php';

header('Content-Type: application/json');

// Placeholder data - replace with actual Google Sheets API call
$data = [
    ['name' => 'Item 1', 'quantity' => 10, 'status' => 'Available', 'url' => 'https://example.com', 'documents' => 'https://drive.google.com'],
    ['name' => 'Item 2', 'quantity' => 5, 'status' => 'Low Stock', 'url' => 'https://example.com', 'documents' => 'https://drive.google.com'],
];

echo json_encode($data);
?>