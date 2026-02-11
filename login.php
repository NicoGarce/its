<?php
// login.php - Handle login logic
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';
// load base config for redirects
$config = null;
if (is_readable(__DIR__ . '/includes/config.php')) {
    $config = include __DIR__ . '/includes/config.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Defensive DB access: handle missing columns or other DB errors without crashing
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
    } catch (PDOException $e) {
        // Do not expose internal errors to users; show generic message
        $user = false;
        $error = 'An internal error occurred. Please try again later.';
    }

    $pwok = false;
    if ($user) {
        if (isset($user['password_hash'])) {
            $pwok = verifyPassword($password, $user['password_hash']);
        } elseif (isset($user['password'])) {
            // Support older column name: try password_verify, else plain compare
            $pwok = password_verify($password, $user['password']) || ($password === $user['password']);
        }
    }

    if ($user && $pwok) {
        $role = isset($user['role']) ? $user['role'] : null;
        $token = generateJWT($user['id'], $user['username'], $role);
        setcookie('jwt', $token, time() + 3600, '/', '', false, true); // HttpOnly
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['user'] = ['userId' => $user['id'], 'username' => $user['username'], 'role' => $role, 'iat' => time()];
        // Try server redirect first; if headers already sent, fall back to client-side redirect
        if (!headers_sent()) {
            header('Location: dashboard.php');
            exit;
        }
        $d = ($config && isset($config->base)) ? $config->base . 'dashboard.php' : '/dashboard.php';
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Redirecting</title></head><body><script>try{window.location.replace("' . $d . '");}catch(e){window.location.href="' . $d . '";}</script><noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($d) . '"></noscript></body></html>';
        exit;
    } elseif (!isset($error)) {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Processing</title>
</head>
<body>
    <script>
        <?php if (isset($error)): ?>
            alert('<?php echo $error; ?>');
            window.location.href = 'index.php';
        <?php endif; ?>
    </script>
</body>
</html>