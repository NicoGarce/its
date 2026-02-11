<?php
header('Content-Type: application/json');

// Fail early if Composer autoloader is missing to avoid fatal errors returning HTML
if (!is_readable(__DIR__ . '/../vendor/autoload.php')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server configuration error: missing dependencies (vendor).']);
    @file_put_contents(__DIR__ . '/../data/error.log', date('c') . " - missing vendor/autoload.php\n", FILE_APPEND);
    exit;
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Main handler
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }

    $username = isset($_POST['username']) ? sanitizeInput($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Lookup user
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
    } catch (Exception $e) {
        @file_put_contents(__DIR__ . '/../data/error.log', date('c') . " - DB exception: " . $e->getMessage() . "\n", FILE_APPEND);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Internal error']);
        exit;
    }

    // Verify password
    $pwok = false;
    if ($user) {
        if (isset($user['password_hash'])) {
            $pwok = verifyPassword($password, $user['password_hash']);
        } elseif (isset($user['password'])) {
            // Support legacy plain or bcrypt-stored passwords
            $pwok = password_verify($password, $user['password']) || ($password === $user['password']);
        }
    }

    if ($user && $pwok) {
        $role = isset($user['role']) ? $user['role'] : null;
        $token = generateJWT($user['id'], $user['username'], $role);
        setcookie('jwt', $token, time() + 3600, '/', '', false, true);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['user'] = ['userId' => $user['id'], 'username' => $user['username'], 'role' => $role, 'iat' => time()];

        $config = null;
        if (is_readable(__DIR__ . '/../includes/config.php')) {
            $config = include __DIR__ . '/../includes/config.php';
        }
        $redir = ($config && isset($config->base)) ? ($config->base . 'dashboard.php') : '/dashboard.php';
        echo json_encode(['success' => true, 'redirect' => $redir]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
    exit;

} catch (Throwable $t) {
    // Log and return JSON error
    $msg = $t->getMessage();
    $trace = $t->getTraceAsString();
    @file_put_contents(__DIR__ . '/../data/error.log', date('c') . " - Exception: " . $msg . "\n" . $trace . "\nPOST:" . json_encode($_POST) . "\n", FILE_APPEND);
    http_response_code(500);
    $debug = getenv('ITS_DEBUG') === '1';
    echo json_encode(['success' => false, 'message' => $debug ? $msg : 'Internal server error']);
    exit;
}
