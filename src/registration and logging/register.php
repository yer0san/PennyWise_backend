<?php
    require_once(__DIR__ . "/../core/db.php");
    require_once(__DIR__ . "/../utils.php");
    require_once(__DIR__ . "/../controllers/mailService.php");


    //Det Input
    $data = getJsonInput();
    $username = sanitize($data['username'] ?? '');
    $email = sanitize($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $conn = getDB();
    $token = bin2hex(random_bytes(32));
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
            "message" => "Password must be at least 6 characters and a combination of alphabets and numbers"
        ], 400);
    }
    //Check if duplicated user exixts
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    $result = $stmt->fetch();
    if ($result) {
        json([
            "status" => "error",
            "message" => "Username or email already exists"
         ], 409);
    }
    //Insert
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, password_hash, email, verification_token) VALUES (?, ?, ?, ?)");
    $emailSent = true;
    if ($stmt->execute([$username, $hashedPassword, $email, $token])) {
       
    try {
            sendVerificationEmail($email, $token);
        } catch (Exception $e) {
            $emailSent=false;
            error_log('Verification email failed: ' . $e->getMessage());
            json([
                "status" => "success",
                "message" => "User registered successfully, but verification email could not be sent",
            ], 201);
        }

        json([
            "status" => "success",
            "message" => "User registered successfully",
            "email_sent" => $emailSent
        ], 201);

    } else {
        json([
            "status" => "error",
            "message" => "Registration failed,Server error"
        ], 500);
    }
