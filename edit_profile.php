<?php
session_start();
include "db.php";

if(!isset($_SESSION['id_number'])){
    header("Location: login.php");
    exit();
}

$id = $_SESSION['id_number'];

$stmt = $conn->prepare("SELECT * FROM students WHERE id_number = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Profile</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <a href="profile.php" class="back-btn2">⬅ Back to Profile</a>

    <div class="card">
        <h2>Edit Profile</h2>
        <form action="update_profile.php" method="POST">
            <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
            <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
            <input type="text" name="course" value="<?php echo htmlspecialchars($user['course']); ?>">
            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            <button type="submit">Update</button>
        </form>
    </div>
</body>
</html>