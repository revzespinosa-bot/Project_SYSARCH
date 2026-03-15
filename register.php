<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <a href="index.php" class="back-btn2">⬅ Back to Home</a>

    <div class="card">
        <h2>Student Registration</h2>
        <form action="process_register.php" method="POST">
            <input type="text" name="id_number" placeholder="ID Number" required>
            <input type="text" name="last_name" placeholder="Last Name" required>
            <input type="text" name="first_name" placeholder="First Name" required>
            <input type="text" name="middle_name" placeholder="Middle Name">

            <input type="text" name="course" placeholder="Course (BSIT, BSCS)">
            <input type="text" name="year_level" placeholder="Year Level">

            <input type="email" name="email" placeholder="Email" required>
            <input type="text" name="address" placeholder="Address">

            <input type="password" name="password" placeholder="Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>

            <button type="submit">Register</button>
        </form>
        <p>Already have account? <a href="login.php">Login</a></p>
    </div>
</body>
</html>