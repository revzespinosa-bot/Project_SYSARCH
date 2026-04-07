<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="register-page">
    <a href="index.php" class="back-btn2">⬅ Back to Home</a>

    <div class="card">
        <div class="register-header">
            <div class="register-icon">
                <svg width="50" height="50" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M12 11C14.2091 11 16 9.20914 16 7C16 4.79086 14.2091 3 12 3C9.79086 3 8 4.79086 8 7C8 9.20914 9.79086 11 12 11Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <h2>Student Registration</h2>
        </div>
        <form action="process_register.php" method="POST">
            <input type="text" name="id_number" placeholder="ID Number" required>
            <input type="text" name="last_name" placeholder="Last Name" required>
            <input type="text" name="first_name" placeholder="First Name" required>
            <input type="text" name="middle_name" placeholder="Middle Name">

            <input type="text" name="course" list="course_list" placeholder="Course" required>
            <datalist id="course_list">
                <option value="BSIT">
                <option value="BSCS">
                <option value="BSBA">
                <option value="BSMA">
                <option value="BSMT">
                <option value="BSHM">
                <option value="BSCRIM">
            </datalist>

            <input type="text" name="year_level" list="year_list" placeholder="Year Level" required>
            <datalist id="year_list">
                <option value="1st Year">
                <option value="2nd Year">
                <option value="3rd Year">
                <option value="4th Year">
            </datalist>

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