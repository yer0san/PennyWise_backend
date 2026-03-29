<?php
// Database connection settings
$host = 'localhost';
$db   = 'your_database';
$user = 'your_username';
$pass = 'your_password';

// Connect to database
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Capture POST data from registration form
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$email    = isset($_POST['email']) ? trim($_POST['email']) : '';

// Basic validation
if (empty($username) || empty($password) || empty($email)) {
    die('Please fill in all required fields.');
}

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Prepare and execute insert statement
$stmt = $conn->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $username, $hashedPassword, $email);

if ($stmt->execute()) {
    echo "Registration successful!";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>