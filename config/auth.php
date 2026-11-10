<?php

const KCRPMS_ROLES = [
    'ADMIN',
    'LANDLORD',
    'PROPERTY_MANAGER',
    'TENANT',
];

function kcrpms_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $isSecure,
        'samesite' => 'Lax',
        'path' => '/',
    ]);

    if (!session_start()) {
        throw new RuntimeException('Unable to start the KCRPMS session.');
    }
}

function kcrpms_current_user(): ?array
{
    $user = $_SESSION['user'] ?? null;

    if (
        !is_array($user)
        || !isset($user['user_id'], $user['full_name'], $user['email'], $user['role'])
        || !is_int($user['user_id'])
        || !is_string($user['full_name'])
        || !is_string($user['email'])
        || !in_array($user['role'], KCRPMS_ROLES, true)
    ) {
        return null;
    }

    return $user;
}

function kcrpms_require_login(): array
{
    kcrpms_start_session();
    $user = kcrpms_current_user();

    if ($user === null) {
        header('Location: index.html');
        exit;
    }

    return $user;
}

function kcrpms_require_role(string ...$allowedRoles): array
{
    if (array_diff($allowedRoles, KCRPMS_ROLES) !== []) {
        throw new InvalidArgumentException('An unknown KCRPMS role was requested.');
    }

    $user = kcrpms_require_login();

    if (!in_array($user['role'], $allowedRoles, true)) {
        http_response_code(403);
        exit('You do not have permission to access this page.');
    }

    return $user;
}
