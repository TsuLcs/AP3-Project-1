<?php   
$db_host = "localhost";
$db_name = "approject1";
$db_user = "root";
$db_password = "";

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected successfully";  // optional for debugging
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}