<?php
// includes/auth.php - JWT authentication functions
require_once __DIR__ . '/../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('JWT_SECRET', 'your-secret-key-here'); // Change this to a secure key

function generateJWT($userId, $username, $role = null) {
    $issuedAt = time();
    $expirationTime = $issuedAt + 3600; // 1 hour
    $payload = [
        'iat' => $issuedAt,
        'exp' => $expirationTime,
        'userId' => $userId,
        'username' => $username,
        'role' => $role
    ];
    return JWT::encode($payload, JWT_SECRET, 'HS256');
}

function validateJWT($token) {
    try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
        return (array) $decoded;
    } catch (Exception $e) {
        return false;
    }
}

function requireAuth() {
    global $config;
    // Prefer server-side session if available
    if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
        return $_SESSION['user'];
    }

        // Fallback to JWT cookie validation
        if (!isset($_COOKIE['jwt'])) {
            $loc = ($config && isset($config->base)) ? $config->base . 'index.php' : '/index.php';
            header('Location: ' . $loc);
            exit;
    }
    $token = $_COOKIE['jwt'];
    $payload = validateJWT($token);
    if (!$payload) {
            $loc = ($config && isset($config->base)) ? $config->base . 'index.php' : '/index.php';
            header('Location: ' . $loc);
            exit;
    }

    // Save into session for subsequent page loads
    $_SESSION['user'] = $payload;
    return $payload;
}

function logout() {
    global $config;
    // Clear session and cookie
    if (session_status() !== PHP_SESSION_NONE) {
        $_SESSION = [];
        session_destroy();
    }
    setcookie('jwt', '', time() - 3600, '/');
        // Use a relative redirect so logout.php -> index.php works regardless of app base.
        // Absolute redirects (starting with '/') can point to the server root and break when
        // the app is hosted under a subpath in production or locally.
        header('Location: index.php');
    exit;
}
