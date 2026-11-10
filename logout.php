<?php

require_once __DIR__ . '/config/auth.php';

kcrpms_start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed.');
}

$submittedToken = $_POST['csrf_token'] ?? null;
$sessionToken = $_SESSION['csrf_token'] ?? null;

if (
    !is_string($submittedToken)
    || !is_string($sessionToken)
    || !hash_equals($sessionToken, $submittedToken)
) {
    http_response_code(403);
    exit('Invalid request token.');
}

$_SESSION = [];
$cookie = session_get_cookie_params();
setcookie(session_name(), '', [
    'expires' => time() - 42000,
    'path' => $cookie['path'],
    'domain' => $cookie['domain'],
    'secure' => $cookie['secure'],
    'httponly' => $cookie['httponly'],
    'samesite' => $cookie['samesite'] ?? 'Lax',
]);
session_destroy();

header('Location: index.html');
exit;
