<?php

require_once __DIR__ . '/config/auth.php';

kcrpms_start_session();

if (!isset($_SESSION['admin_setup_csrf'])) {
    $_SESSION['admin_setup_csrf'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = false;
$adminExists = false;
$fullName = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: GET, POST');
    http_response_code(405);
    exit('Method not allowed.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? null;

    if (
        !is_string($submittedToken)
        || !hash_equals($_SESSION['admin_setup_csrf'], $submittedToken)
    ) {
        $errors[] = 'Your form session expired. Reload the page and try again.';
    }

    $fullNameInput = $_POST['full_name'] ?? null;
    $emailInput = $_POST['email'] ?? null;
    $phoneInput = $_POST['phone'] ?? null;
    $password = $_POST['password'] ?? null;

    if (!is_string($fullNameInput)) {
        $errors[] = 'Enter a valid full name.';
    } else {
        $fullName = trim($fullNameInput);
        $nameLength = preg_match_all('/./us', $fullName);

        if ($nameLength === false || $nameLength < 1 || $nameLength > 150) {
            $errors[] = 'Full name must be between 1 and 150 characters.';
        }
    }

    if (!is_string($emailInput)) {
        $errors[] = 'Enter a valid email address.';
    } else {
        $email = trim($emailInput);

        if (
            strlen($email) > 150
            || filter_var($email, FILTER_VALIDATE_EMAIL) === false
        ) {
            $errors[] = 'Enter a valid email address (maximum 150 characters).';
        }
    }

    if (!is_string($phoneInput)) {
        $errors[] = 'Enter a valid phone number.';
    } else {
        $phone = trim($phoneInput);

        if (
            strlen($phone) > 20
            || ($phone !== '' && !preg_match('/^\+?[0-9() -]+$/', $phone))
        ) {
            $errors[] = 'Phone number must be at most 20 characters and contain only digits, spaces, +, -, or parentheses.';
        }
    }

    if (
        !is_string($password)
        || strlen($password) < 12
        || strlen($password) > 72
    ) {
        $errors[] = 'Password must be between 12 and 72 bytes.';
    }
}

require_once __DIR__ . '/config/database.php';

$lockAcquired = false;

try {
    $lockStatement = $pdo->prepare("SELECT GET_LOCK('kcrpms_first_admin_setup', 10)");
    $lockStatement->execute();

    if ((int) $lockStatement->fetchColumn() !== 1) {
        throw new RuntimeException('Could not acquire the administrator setup lock.');
    }

    $lockAcquired = true;

    $adminStatement = $pdo->prepare(
        'SELECT user_id FROM users WHERE role = :role LIMIT 1'
    );
    $adminStatement->execute(['role' => 'ADMIN']);
    $adminExists = $adminStatement->fetchColumn() !== false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$adminExists && $errors === []) {
        $duplicateStatement = $pdo->prepare(
            'SELECT user_id FROM users WHERE email = :email LIMIT 1'
        );
        $duplicateStatement->execute(['email' => $email]);

        if ($duplicateStatement->fetchColumn() !== false) {
            $errors[] = 'An account with that email address already exists.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $insertStatement = $pdo->prepare(
                'INSERT INTO users
                    (full_name, email, phone, password_hash, role, account_status)
                 VALUES
                    (:full_name, :email, :phone, :password_hash, :role, :account_status)'
            );
            $insertStatement->execute([
                'full_name' => $fullName,
                'email' => $email,
                'phone' => $phone === '' ? null : $phone,
                'password_hash' => $passwordHash,
                'role' => 'ADMIN',
                'account_status' => 'ACTIVE',
            ]);

            $success = true;
            $adminExists = true;
            unset($_SESSION['admin_setup_csrf']);
        }
    }
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000') {
        $errors[] = 'An account with that email address already exists.';
    } else {
        error_log('KCRPMS first administrator setup error: ' . $exception->getMessage());
        $errors[] = 'Administrator setup failed. Check the server logs and try again.';
    }
} catch (RuntimeException $exception) {
    error_log('KCRPMS first administrator setup error: ' . $exception->getMessage());
    $errors[] = 'Administrator setup is temporarily unavailable. Please try again.';
} finally {
    if ($lockAcquired) {
        try {
            $releaseStatement = $pdo->prepare(
                "SELECT RELEASE_LOCK('kcrpms_first_admin_setup')"
            );
            $releaseStatement->execute();
        } catch (PDOException $exception) {
            error_log('KCRPMS administrator setup lock release error: ' . $exception->getMessage());
        }
    }
}

function escape_admin_setup_value(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KCRPMS | Create First Administrator</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="header">
        <div class="logo">
            <h2>KCRPMS</h2>
            <p>KILIFI COUNTY RENTAL PROPERTY MANAGEMENT SYSTEM</p>
        </div>
    </header>
    <main class="login-container">
        <section class="login-section">
            <div class="login-box">
                <h2>Create First Administrator</h2>
                <?php if ($success): ?>
                    <p role="status">The administrator account was created successfully. You can now sign in.</p>
                    <p><a href="index.html">Go to sign in</a></p>
                <?php elseif ($adminExists): ?>
                    <p role="status">An administrator account already exists. This setup page cannot create another administrator.</p>
                <?php else: ?>
                    <p class="login-description">Use this one-time form to initialize KCRPMS administration.</p>
                    <?php if ($errors !== []): ?>
                        <div role="alert">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?= escape_admin_setup_value($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <form action="create_admin.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?= escape_admin_setup_value($_SESSION['admin_setup_csrf']) ?>">
                        <div class="form-group">
                            <label for="full_name">Administrator name</label>
                            <input type="text" id="full_name" name="full_name" maxlength="150" autocomplete="name" value="<?= escape_admin_setup_value($fullName) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email address</label>
                            <input type="email" id="email" name="email" maxlength="150" autocomplete="email" value="<?= escape_admin_setup_value($email) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone (optional)</label>
                            <input type="tel" id="phone" name="phone" maxlength="20" autocomplete="tel" value="<?= escape_admin_setup_value($phone) ?>">
                        </div>
                        <div class="form-group">
                            <label for="password">Password (12-72 bytes)</label>
                            <input type="password" id="password" name="password" minlength="12" maxlength="72" autocomplete="new-password" required>
                        </div>
                        <button type="submit" id="loginButton">Create Administrator</button>
                    </form>
                <?php endif; ?>
            </div>
        </section>
    </main>
    <footer class="footer">
        <p>&copy; 2026 KCRPMS. All rights reserved.</p>
    </footer>
</body>
</html>
