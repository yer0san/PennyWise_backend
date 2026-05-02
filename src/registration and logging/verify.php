<?php

require_once(__DIR__ . "/../core/db.php");
require_once(__DIR__ . "/../utils.php");
$conn = getDB();

$token = $_GET['token'] ?? '';

if (!$token) {
    die("Invalid token");
}

$stmt = $conn->prepare("
    SELECT id FROM users WHERE verification_token = :token LIMIT 1
");

$stmt->execute([':token' => $token]);
$user = $stmt->fetch();

if (!$user) {
    die("Invalid or expired token");
}


$stmt = $conn->prepare("
    UPDATE users 
    SET is_verified = 1, verification_token = NULL 
    WHERE id = :id
");

$stmt->execute([':id' => $user['id']]);

echo "Email verified successfully!";
?>
