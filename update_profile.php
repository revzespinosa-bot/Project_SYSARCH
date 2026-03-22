<?php
session_start();
include "db.php";

if(!isset($_SESSION['id_number'])){
    header("Location: login.php");
    exit();
}

$id = $_SESSION['id_number'];

$first = isset($_POST['first_name']) ? $_POST['first_name'] : '';
$last = isset($_POST['last_name']) ? $_POST['last_name'] : '';
$course = isset($_POST['course']) ? $_POST['course'] : '';
$year_level = isset($_POST['year_level']) ? $_POST['year_level'] : '';
$email = isset($_POST['email']) ? $_POST['email'] : '';
$address = isset($_POST['address']) ? $_POST['address'] : '';

// Photo column availability check
$photoColumnExists = false;
$photoColumnQuery = $conn->query("SHOW COLUMNS FROM students LIKE 'photo'");
if ($photoColumnQuery && $photoColumnQuery->num_rows > 0) {
    $photoColumnExists = true;
}

// Handle Photo Upload (optional)
$delete_photo = isset($_POST['delete_photo']) && $_POST['delete_photo'] == '1';

$photo_uploaded = false;
$new_filename = '';
if (!$delete_photo && isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
    $file = $_FILES['photo']['name'];
    $tmp = $_FILES['photo']['tmp_name'];

    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = pathinfo($file, PATHINFO_EXTENSION);

    if (in_array(strtolower($filename), $allowed)) {
        if ($_FILES['photo']['size'] <= 5000000) {
            $new_filename = uniqid() . "." . $filename;
            $upload_path = "uploads/" . $new_filename;

            if (move_uploaded_file($tmp, $upload_path)) {
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

// Handle delete-photo action (supersedes photo upload)
if ($delete_photo && $photoColumnExists) {
    // Clear file from server if exists
    $existingPhotoStmt = $conn->prepare("SELECT photo FROM students WHERE id_number = ?");
    $existingPhotoStmt->bind_param("s", $id);
    $existingPhotoStmt->execute();
    $existingPhotoResult = $existingPhotoStmt->get_result();
    if ($existingPhotoResult && $existingPhotoRow = $existingPhotoResult->fetch_assoc()) {
        if (!empty($existingPhotoRow['photo'])) {
            $existingPhotoPath = 'uploads/' . $existingPhotoRow['photo'];
            if (file_exists($existingPhotoPath)) {
                unlink($existingPhotoPath);
            }
        }
    }
    $existingPhotoStmt->close();

    $stmt = $conn->prepare("UPDATE students SET photo=NULL WHERE id_number=?");
    $stmt->bind_param("s", $id);
    if ($stmt->execute()) {
        echo "<script>alert('✅ Photo deleted successfully!'); window.location='profile.php';</script>";
        exit();
    } else {
        echo "<script>alert('❌ Error deleting photo: " . $stmt->error . "'); window.location='profile.php';</script>";
        exit();
    }
} elseif ($photo_uploaded && $photoColumnExists) {
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