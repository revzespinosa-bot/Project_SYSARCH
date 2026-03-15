<?php
session_start();
include "db.php";

if(!isset($_SESSION['id_number'])){
    header("Location: login.php");
    exit();
}

$id = $_SESSION['id_number'];

// Debug: Check if uploads folder exists
$upload_dir = "uploads/";
if (!is_dir($upload_dir)) {
    echo "<script>alert('ERROR: uploads folder does not exist!'); window.location='profile.php';</script>";
    exit();
}

if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0){
    $file = $_FILES['photo']['name'];
    $tmp = $_FILES['photo']['tmp_name'];
    $error = $_FILES['photo']['error'];
    
    echo "<script>alert('File: " . $file . " | Error: " . $error . " | Temp: " . $tmp . "');</script>";
    
    // Basic validation
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = pathinfo($file, PATHINFO_EXTENSION);
    
    if(in_array(strtolower($filename), $allowed)){
        // Generate unique filename
        $new_filename = uniqid() . "." . $filename;
        $full_path = $upload_dir . $new_filename;
        
        echo "<script>alert('Trying to upload to: " . $full_path . "');</script>";
        
        if(move_uploaded_file($tmp, $full_path)){
            $stmt = $conn->prepare("UPDATE students SET photo=? WHERE id_number=?");
            $stmt->bind_param("ss", $new_filename, $id);
            
            if($stmt->execute()){
                echo "<script>alert('Photo uploaded successfully!'); window.location='profile.php';</script>";
            } else {
                echo "<script>alert('Database error: " . $stmt->error . "');</script>";
            }
            $stmt->close();
        } else {
            echo "<script>alert('File upload failed! Check uploads folder permissions.');</script>";
        }
    } else {
        echo "<script>alert('Invalid file type. Allowed: jpg, jpeg, png, gif');</script>";
    }
} else {
    echo "<script>alert('No file selected or upload error: " . $error . "');</script>";
}
?>