<?php
    session_start();
    require_once(__DIR__ . "/../utils.php");
    //Clear all session data
    $_SESSION = [];
    //Destroy Session
    if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}
    session_destroy();
    json([
    "status" => "success",
    "message" => "Logged out successfully"
]);
    header("Location: login.html");
?>
