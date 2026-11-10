<?php

require_once __DIR__ . '/config/auth.php';

kcrpms_start_session();

if (kcrpms_current_user() !== null) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

$email = $_POST['email'] ?? null;
$password = $_POST['password'] ?? null;

if (
    !is_string($email)
    || !is_string($password)
    || strlen(trim($email)) > 150
    || filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false
    || $password === ''
) {
    header('Location: index.html?error=invalid_input');
    exit;
}

$email = trim($email);

require_once __DIR__ . '/config/database.php';

try {
    $statement = $pdo->prepare(
        'SELECT user_id, full_name, email, password_hash, role, account_status
         FROM users
         WHERE email = :email
         LIMIT 1'
    );
    $statement->execute(['email' => $email]);
    $user = $statement->fetch();
} catch (PDOException $exception) {
    error_log('KCRPMS login database error: ' . $exception->getMessage());
    header('Location: index.html?error=server_error');
    exit;
}

if (
    !$user
    || $user['account_status'] !== 'ACTIVE'
    || !password_verify($password, $user['password_hash'])
    || !in_array($user['role'], KCRPMS_ROLES, true)
) {
    header('Location: index.html?error=login_failed');
    exit;
}

session_regenerate_id(true);
$_SESSION['user'] = [
    'user_id' => (int) $user['user_id'],
    'full_name' => $user['full_name'],
    'email' => $user['email'],
    'role' => $user['role'],
];
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

header('Location: index.php');
exit;