<?php
    session_start();
    include "db.php";

    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username='$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            $_SESSION['username'] = $username;
            header("Location: index.html");
        } else {
            echo "Wrong password!";
        }
    } else {
        echo "User not found!";
    }

    $conn->close();
?>