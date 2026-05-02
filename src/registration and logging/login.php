<?php
    session_start();
    require_once(__DIR__ . "/../core/db.php");
    require_once(__DIR__ . "/../utils.php");
    //Get Input
    $conn = getDB();
    $data = getJsonInput();
    $user_input = '';
    $password = isset($data['password']) ? trim($data['password']) : '';

    if (isset($data['username']) && !isEmpty($data['username'])) {
        $user_input = trim($data['username']);
    } elseif (isset($data['email']) && !isEmpty($data['email'])) {
        $user_input = trim($data['email']);
    }
    if (isEmpty($user_input) || isEmpty($password)) {
        json([
            "status" => "error",
            "message" => "All fields are required"
        ], 400);
    }
    try {
        // Prepare query
        $stmt = $conn->prepare("SELECT id, username, password_hash, is_verified FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$user_input, $user_input]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check user
        if (!$user || !password_verify($password, $user['password_hash'])) {
            json([
                "status" => "error",
                "message" => "Invalid username or password"
            ], 401);
        }

        if (!$user['is_verified']) {
            json([
                "status" => "error",
                "message" => "Please verify your email first"
            ], 403);
        }

        //SESSION
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        session_regenerate_id(true);

        json([
            "status" => "success",
            "message" => "Login successful",
            "user" => [
                "id" => $user['id'],
                "username" => $user['username']
            ]
        ]);
    } catch (Exception $e) {
        json([
            "status" => "error",
            "message" => "Server error"
        ], 500);
    }
