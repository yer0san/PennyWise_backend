<?php
    require_once(__DIR__ . "/../core/db.php");
    require_once(__DIR__ . "/../utils.php");
    //Det Input
    $data = getJsonInput();
    $username = sanitize($data['username'] ?? '');
    $email = sanitize($data['email'] ?? '');
    $password = $data['password'] ?? '';
    //Validation
    if (isEmpty($username) || isEmpty($email) || isEmpty($password)) {
        json([
            "status" => "error",
            "message" => "All fields are required"
        ], 400);
    }
    if (!validateText($username, 3, 50)) {
        json([
             "status" => "error",
             "message" => "Username must be 3–50 characters"
        ], 400);
    }
    if (!validateEmail($email)) {
        json([
             "status" => "error",
              "message" => "Invalid email format"
         ], 400);
    }
    if (!validatePassword($password)) {
         json([
            "status" => "error",
            "message" => "Password must be at least 6 characters"
        ], 400);
    }
    //Check if duplicated user exixts
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        json([
            "status" => "error",
            "message" => "Username or email already exists"
         ], 409);
    }
    //Insert
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $hashedPassword, $email);

    if ($stmt->execute()) {
         json([
             "status" => "success",
             "message" => "User registered successfully"
        ], 201);
    } else {
        json([
            "status" => "error",
            "message" => "Registration failed"
        ], 500);
    }
