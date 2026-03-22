<?php
session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = trim($_POST['id_number']);
    $pass = $_POST['password'];

    // Admin credentials (hard-coded fallback)
    $adminId = "admin";
    $adminPass = "admin123";

    if ($id === $adminId) {
        if ($pass === $adminPass) {
            $_SESSION['id_number'] = $adminId;
            $_SESSION['first_name'] = "Administrator";
            $_SESSION['is_admin'] = true;
            header("Location: admin_dashboard.php");
            exit();
        } else {
            echo "<script>alert('Wrong Admin Password'); window.location='login.php';</script>";
            exit();
        }
    }

    // Student login
    $stmt = $conn->prepare("SELECT * FROM students WHERE id_number = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($pass, $user['password'])) {
            $_SESSION['id_number'] = $user['id_number'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['is_admin'] = false;
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