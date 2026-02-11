<?php
// Central include to require authentication for protected pages
// Usage: require_once __DIR__ . '/require_login.php'; then use $user
require_once __DIR__ . '/auth.php';
// This will redirect to login if not authenticated
$user = requireAuth();
