<?php
session_start();
// If already logged in, go to profile
if(isset($_SESSION['id_number'])){
    header("Location: profile.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <a href="index.php" class="back-btn2">⬅ Back to Home</a>

    <div class="card">
        <h2>Student Login</h2>
        <form action="process_login.php" method="POST">
            <input type="text" name="id_number" placeholder="ID Number" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <p>No account? <a href="register.php">Register</a></p>
    </div>
</body>
</html>