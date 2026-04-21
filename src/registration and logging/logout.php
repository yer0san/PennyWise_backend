<?php
    session_start();
    require_once "utils.php";
    //Clear all session data
    $_SESSION = [];
    //Destroy Session
    session_destroy();
    header("Location: login.html");
?>