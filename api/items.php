<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';

// Ensure table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS `items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category` VARCHAR(64) NOT NULL,
    `label` VARCHAR(255) NOT NULL,
    `url` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $category = isset($_GET['category']) ? $_GET['category'] : null;
    if ($category) {
        $stmt = $pdo->prepare('SELECT * FROM items WHERE category = :cat ORDER BY id ASC');
        $stmt->execute(['cat' => $category]);
        $rows = $stmt->fetchAll();
        echo json_encode($rows);
        exit;
    }
    // return all grouped by category
    $stmt = $pdo->query('SELECT * FROM items ORDER BY category, id');
    $all = $stmt->fetchAll();
    echo json_encode($all);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'POST') {
    $category = $input['category'] ?? null;
    $label = $input['label'] ?? null;
    $url = $input['url'] ?? null;
    if (!$category || !$label) {
        http_response_code(400);
        echo json_encode(['error' => 'category and label required']);
        exit;
    }
    $stmt = $pdo->prepare('INSERT INTO items (category,label,url) VALUES (:category,:label,:url)');
    $stmt->execute(['category'=>$category,'label'=>$label,'url'=>$url]);
    $id = $pdo->lastInsertId();
    $stmt = $pdo->prepare('SELECT * FROM items WHERE id = :id');
    $stmt->execute(['id'=>$id]);
    $row = $stmt->fetch();
    echo json_encode($row);
    exit;
}

if ($method === 'PUT') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : ($input['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['error'=>'id required']); exit; }
    $label = $input['label'] ?? null;
    $url = $input['url'] ?? null;
    $stmt = $pdo->prepare('UPDATE items SET label = :label, url = :url WHERE id = :id');
    $stmt->execute(['label'=>$label,'url'=>$url,'id'=>$id]);
    $stmt = $pdo->prepare('SELECT * FROM items WHERE id = :id');
    $stmt->execute(['id'=>$id]);
    echo json_encode($stmt->fetch());
    exit;
}

if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : ($input['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['error'=>'id required']); exit; }
    $stmt = $pdo->prepare('DELETE FROM items WHERE id = :id');
    $stmt->execute(['id'=>$id]);
    echo json_encode(['ok'=>true]);
    exit;
}

http_response_code(405);
echo json_encode(['error'=>'method not allowed']);

?>
