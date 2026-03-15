<?php
session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id_number'];
    $last = $_POST['last_name'];
    $first = $_POST['first_name'];
    $middle = $_POST['middle_name'];
    $course = $_POST['course'];
    $year = $_POST['year_level'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $pass = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];

    // Check if passwords match
    if ($pass !== $confirm_pass) {
        echo "<script>alert('Passwords do not match'); window.location='register.php';</script>";
        exit();
    }

    // Hash password
    $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);

    // Check if ID already exists
    $check = $conn->prepare("SELECT id_number FROM students WHERE id_number = ?");
    $check->bind_param("s", $id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "<script>alert('ID Number already exists'); window.location='register.php';</script>";
        exit();
    }

    // Insert into database
    $sql = "INSERT INTO students 
    (id_number, last_name, first_name, middle_name, course, year_level, email, address, password)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssss", $id, $last, $first, $middle, $course, $year, $email, $address, $hashed_pass);

    if ($stmt->execute()) {
        echo "<script>alert('Registered Successfully!'); window.location='login.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
    $stmt->close();
}
?>