<?php
include "db.php";

$id = $_POST['id_number'];
$last = $_POST['last_name'];
$first = $_POST['first_name'];
$middle = $_POST['middle_name'];
$course = $_POST['course'];
$year = $_POST['year_level'];
$email = $_POST['email'];
$address = $_POST['address'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

$sql = "INSERT INTO users 
(id_number,last_name,first_name,middle_name,course,year_level,email,address,password)
VALUES ('$id','$last','$first','$middle','$course','$year','$email','$address','$password')";

if ($conn->query($sql)) {
    echo "<script>alert('Registered Successfully!'); window.location='login.php';</script>";
} else {
    echo "Error: " . $conn->error;
}
?>
