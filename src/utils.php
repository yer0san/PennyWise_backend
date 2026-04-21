<?php

//JSON Response
function json($data, $status = 200) {
    http_response_code($status);
    header("Content-Type: application/json");
    echo json_encode($data);
    exit;
}
//Get JSON Input
function getJsonInput() {
    return json_decode(file_get_contents('php://input'), true);
}

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
    return strlen($password) >= 6;
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