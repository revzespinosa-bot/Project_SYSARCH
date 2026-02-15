<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

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
