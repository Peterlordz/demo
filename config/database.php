
<?php
require_once __DIR__ . '/.env';

$host = "localhost";
$port = "3306";
$dbname = "kilifi_rentals_db";
$username = "root";
$password = "Peterlordz@12248"; // Replace with your actual database password

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

?>
