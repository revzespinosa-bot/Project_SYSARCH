<?php
session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id_number'];
    $pass = $_POST['password'];

    // Use Prepared Statement to prevent SQL Injection
    $stmt = $conn->prepare("SELECT * FROM students WHERE id_number = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($pass, $user['password'])) {
            // Set Session
            $_SESSION['id_number'] = $user['id_number'];
            $_SESSION['first_name'] = $user['first_name'];
            
            // Redirect to Profile
            header("Location: profile.php");
            exit();
        } else {
            echo "<script>alert('Wrong Password'); window.location='login.php';</script>";
        }
    } else {
        echo "<script>alert('User Not Found'); window.location='login.php';</script>";
    }
    $stmt->close();
}
?>