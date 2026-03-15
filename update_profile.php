<?php
session_start();
include "db.php";

if(!isset($_SESSION['id_number'])){
    header("Location: login.php");
    exit();
}

$id = $_SESSION['id_number'];

$first = $_POST['first_name'];
$last = $_POST['last_name'];
$course = $_POST['course'];
$year_level = $_POST['year_level'];
$email = $_POST['email'];
$address = $_POST['address'];

// Handle Photo Upload
$photo_uploaded = false;
if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0){
    $file = $_FILES['photo']['name'];
    $tmp = $_FILES['photo']['tmp_name'];
    $error = $_FILES['photo']['error'];
    
    // Basic validation
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = pathinfo($file, PATHINFO_EXTENSION);
    
    if(in_array(strtolower($filename), $allowed)){
        // Check file size (5MB max)
        if($_FILES['photo']['size'] <= 5000000){
            // Generate unique filename
            $new_filename = uniqid() . "." . $filename;
            $upload_path = "uploads/" . $new_filename;
            
            if(move_uploaded_file($tmp, $upload_path)){
                $photo_uploaded = true;
            } else {
                echo "<script>alert('❌ Photo upload failed!'); window.location='profile.php';</script>";
                exit();
            }
        } else {
            echo "<script>alert('❌ Photo is too large! Max 5MB'); window.location='profile.php';</script>";
            exit();
        }
    } else {
        echo "<script>alert('❌ Invalid file type! Allowed: JPG, JPEG, PNG, GIF'); window.location='profile.php';</script>";
        exit();
    }
}

// Update Database
if($photo_uploaded){
    $stmt = $conn->prepare("UPDATE students SET first_name=?, last_name=?, course=?, year_level=?, email=?, address=?, photo=? WHERE id_number=?");
    $stmt->bind_param("ssssssss", $first, $last, $course, $year_level, $email, $address, $new_filename, $id);
} else {
    $stmt = $conn->prepare("UPDATE students SET first_name=?, last_name=?, course=?, year_level=?, email=?, address=? WHERE id_number=?");
    $stmt->bind_param("sssssss", $first, $last, $course, $year_level, $email, $address, $id);
}

if ($stmt->execute()) {
    echo "<script>alert('✅ Profile updated successfully!'); window.location='profile.php';</script>";
} else {
    echo "<script>alert('❌ Error updating profile: " . $stmt->error . "');</script>";
}
$stmt->close();
?>