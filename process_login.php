<?php
session_start();
include "db.php";

$id = $_POST['id_number'];
$pass = $_POST['password'];

$result = $conn->query("SELECT * FROM users WHERE id_number='$id'");

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();

    if (password_verify($pass, $user['password'])) {
        $_SESSION['user'] = $user['first_name'];
        echo "<script>alert('Welcome ".$user['first_name']."'); window.location='index.php';</script>";
    } else {
        echo "<script>alert('Wrong Password'); window.location='login.php';</script>";
    }
} else {
    echo "<script>alert('User Not Found'); window.location='login.php';</script>";
}
?>
