<?php

require_once __DIR__ . '/config/auth.php';

$user = kcrpms_require_login();
$csrfToken = $_SESSION['csrf_token'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KCRPMS | Account</title>
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
                <h2>Signed in</h2>
                <p class="login-description">
                    Welcome, <?= htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') ?>.
                </p>
                <p>
                    Account role:
                    <?= htmlspecialchars(str_replace('_', ' ', $user['role']), ENT_QUOTES, 'UTF-8') ?>
                </p>
                <form action="logout.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" id="loginButton">Sign Out</button>
                </form>
            </div>
        </section>
    </main>
    <footer class="footer">
        <p>&copy; 2026 KCRPMS. All rights reserved.</p>
    </footer>
</body>
</html>