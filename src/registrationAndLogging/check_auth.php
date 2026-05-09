<?php
// check_auth.php
// Called by the frontend on every protected page load.
// Returns the current user if the session is valid, 401 otherwise.
session_start();
require_once(__DIR__ . "/../utils.php");

if (isset($_SESSION['user_id'])) {
    json([
        "status" => "success",
        "user"   => [
            "id"       => $_SESSION['user_id'],
            "username" => $_SESSION['username']
        ]
    ]);
} else {
    json([
        "status"  => "error",
        "message" => "Not authenticated"
    ], 401);
}
