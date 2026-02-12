<?php
// api/accounts.php - JSON API for account add/edit/delete
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$user = null;
if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
    $user = $_SESSION['user'];
} elseif (isset($_COOKIE['jwt'])) {
    $payload = validateJWT($_COOKIE['jwt']);
    if ($payload) { $user = $payload; $_SESSION['user'] = $payload; }
}

if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
if (!isset($user['role']) || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden — admins only']);
    exit;
}

$action = $_POST['action'] ?? null;
if (!$action) {
    echo json_encode(['success' => false, 'message' => 'Missing action']);
    exit;
}

try {
    if ($action === 'add') {
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = sanitizeInput($_POST['role'] ?? 'user');
        if (!$username || !$password) {
            echo json_encode(['success' => false, 'message' => 'Username and password are required.']);
            exit;
        }
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) { echo json_encode(['success' => false, 'message' => 'Username already exists.']); exit; }
        $ins = $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)');
        $ins->execute([$username, hashPassword($password), $role]);
        $newId = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'message' => 'User created.', 'id' => (int)$newId, 'username' => $username, 'role' => $role]);
        exit;
    }

    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $username = sanitizeInput($_POST['username'] ?? '');
        $role = sanitizeInput($_POST['role'] ?? 'user');
        $password = $_POST['password'] ?? '';
        if (!$id || !$username) { echo json_encode(['success' => false, 'message' => 'Invalid data.']); exit; }
        $chk = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
        $chk->execute([$username, $id]);
        if ($chk->fetch()) { echo json_encode(['success' => false, 'message' => 'Username already exists.']); exit; }
        $params = [$username, $role];
        $sql = 'UPDATE users SET username = ?, role = ?';
        if ($password) { $sql .= ', password_hash = ?'; $params[] = hashPassword($password); }
        $sql .= ' WHERE id = ?'; $params[] = $id;
        $upd = $pdo->prepare($sql);
        $upd->execute($params);
        echo json_encode(['success' => true, 'message' => 'User updated.', 'id' => $id, 'username' => $username, 'role' => $role]);
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => 'Invalid user id.']); exit; }
        if ($id == $user['userId']) { echo json_encode(['success' => false, 'message' => 'You cannot delete your own account.']); exit; }
        $del = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $del->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'User deleted.', 'id' => $id]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
} catch (PDOException $e) {
    error_log('api/accounts.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

?>
