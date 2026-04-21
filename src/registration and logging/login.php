<?php
    session_start();
    require_once(__DIR__ . "/../core/db.php");
    require_once(__DIR__ . "/../utils.php");
    //Get Input
    $data = getJsonInput();
    $username = $_POST['username'];
    $password = $_POST['password'];
    //Validation
    if (isEmpty($username) || isEmpty($password)) {
    json([
        "status" => "error",
        "message" => "All fields are required"
    ], 400);
    }
    //DB Check
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    //User Check
    if ($result->num_rows === 0) {
    json([
        "status" => "error",
        "message" => "User not found"
    ], 404);
    }
    $user = $result->fetch_assoc();
    //Password Check
    if (!password_verify($password, $user['password'])) {
    json([
        "status" => "error",
        "message" => "Wrong password"
    ], 401);
    }
    
