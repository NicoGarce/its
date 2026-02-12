<?php
// includes/helpers.php - Reusable helper functions
require_once __DIR__ . '/db.php';

// Instruct crawlers not to index any pages from this application.
// Uses HTTP header so APIs and non-HTML responses are also covered.
if (php_sapi_name() !== 'cli' && !headers_sent()) {
    header('X-Robots-Tag: noindex, nofollow', true);
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Google Sheets API helper (placeholder - implement as needed)
function getGoogleSheetsData($spreadsheetId, $range) {
    // Implement Google Sheets API call here
    // Return array of data
    return []; // Placeholder
}

// Google Drive API helper (placeholder - implement as needed)
function getGoogleDriveFiles($folderId) {
    // Implement Google Drive API call here
    // Return array of files
    return []; // Placeholder
}

// Send email using PHPMailer
function sendEmail($to, $subject, $body) {
    require_once __DIR__ . '/../vendor/autoload.php';
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.example.com'; // Configure your SMTP
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@example.com';
        $mail->Password = 'your-password';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('your-email@example.com', 'ITS System');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Simple HTTP health check for a URL. Returns array with keys: ok (bool), http_code (int), time_ms (float), error (string|null)
function checkHttpEndpoint(string $url, int $timeout = 3): array {
    if (function_exists('curl_version')) {
        $start = microtime(true);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOBODY, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($ch);
        $info = curl_getinfo($ch);
        $err = curl_error($ch);
        curl_close($ch);
        $time = isset($info['total_time']) ? $info['total_time'] * 1000 : (microtime(true) - $start) * 1000;
        $http = isset($info['http_code']) ? (int)$info['http_code'] : 0;
        return [
            'ok' => ($http >= 200 && $http < 400),
            'http_code' => $http,
            'time_ms' => round($time, 1),
            'error' => $err ?: null
        ];
    }

    // Fallback using fsockopen
    $parts = parse_url($url);
    $host = $parts['host'] ?? '';
    $port = $parts['port'] ?? (($parts['scheme'] ?? 'http') === 'https' ? 443 : 80);
    $path = ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');
    $start = microtime(true);
    $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if (!$fp) {
        return ['ok' => false, 'http_code' => 0, 'time_ms' => round((microtime(true) - $start) * 1000, 1), 'error' => trim("$errno $errstr")];
    }
    $out = "GET {$path} HTTP/1.1\r\nHost: {$host}\r\nConnection: Close\r\n\r\n";
    fwrite($fp, $out);
    $response = '';
    while (!feof($fp)) {
        $response .= fgets($fp, 128);
    }
    fclose($fp);
    if (preg_match('#HTTP/\d+\.\d+\s+(\d+)#', $response, $m)) {
        $code = (int)$m[1];
        $time = round((microtime(true) - $start) * 1000, 1);
        return ['ok' => ($code >= 200 && $code < 400), 'http_code' => $code, 'time_ms' => $time, 'error' => null];
    }
    return ['ok' => false, 'http_code' => 0, 'time_ms' => round((microtime(true) - $start) * 1000, 1), 'error' => 'no-response'];
}
