<?php
    include "db.php";

    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $email = $_POST['email'];

    $sql = "INSERT INTO users (username, password, email) VALUES ('$username', '$password', '$email')";

    if ($conn->query($sql) === TRUE) {
        echo "Registered successfully. <a href='login.html'>Login here</a>";
    } else {
        echo "Error: " . $conn->error;
    }

    $conn->close();
?>