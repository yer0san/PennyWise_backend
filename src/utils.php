<?php

//JSON Response
function json($data, $status = 200) {
    http_response_code($status);
    header("Content-Type: application/json;charset=utf-8");
    echo json_encode($data,JSON_UNESCAPED_UNICODE);
    exit;
}
//Get JSON Input
function getJsonInput() {
     $data = json_decode(file_get_contents('php://input'), true);
     if (json_last_error() !== JSON_ERROR_NONE) {
             json([
                 "status" => "error",
                 "message" => "Invalid JSON input"
             ], 400);
         }
     
    return $data ?? [];}

//Sanitization
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)));
}

//Check Empty
function isEmpty($value) {
    return !isset($value) || trim($value) === '';
}

//Email Validation
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

//Password Validation
function validatePassword($password) {
    return strlen($password) >= 6 && preg_match('/[A-Z]/', $password) &&
           preg_match('/[0-9]/', $password); ;
}

//Ampunt Validation
function validateAmount($amount) {
    return is_numeric($amount) && $amount > 0;
}

//Text Validation
function validateText($text, $min = 2, $max = 255) {
    $length = strlen(trim($text));
    return $length >= $min && $length <= $max;
}
function requireAuth() {
    session_start();

    if (!isset($_SESSION['user_id'])) {
        json([
            "status" => "error",
            "message" => "Unauthorized"
        ], 401);
    }
}
function currentUser() {
    return [
        "id" => $_SESSION['user_id'] ?? null,
        "username" => $_SESSION['username'] ?? null
    ];
}
