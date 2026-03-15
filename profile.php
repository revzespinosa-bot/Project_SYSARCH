<?php
session_start();
include "db.php";

// Check if user is logged in
if(!isset($_SESSION['id_number'])){
    header("Location: login.php");
    exit();
}

$id = $_SESSION['id_number'];

// Use Prepared Statement
$stmt = $conn->prepare("SELECT * FROM students WHERE id_number = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if(!$user){
    echo "User not found in database.";
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Profile</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header>    
    <h2></h2>
    <nav>
        <a href="profile.php" class="active">Profile</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<!-- Empty Content Area -->
<div class="profile-container">
    <div class="empty-state">
        <h2>👤 Student Profile</h2>
        <p>Click the button below to view and edit your information</p>
        <button class="btn" onclick="openModal()">📋 View Profile</button>
    </div>
</div>

<!-- MODAL START -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>✏️ Edit Student Information</h2>
        
        <!-- Current Photo Display -->
        <div class="current-photo">
            <?php if($user['photo']){ ?>
                <img src="uploads/<?php echo htmlspecialchars($user['photo']); ?>" width="150" class="profile-photo">
                <p class="photo-label">Current Photo</p>
            <?php } else { ?>
                <img src="https://via.placeholder.com/150?text=No+Photo" width="150" class="profile-photo">
                <p class="photo-label">No Photo</p>
            <?php } ?>
        </div>

        <!-- Photo Upload Form -->
        <form action="update_profile.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_number" value="<?php echo htmlspecialchars($user['id_number']); ?>">
            
            <!-- Photo Upload Section -->
            <div class="photo-upload-section">
                <label>📷 Upload New Photo:</label>
                <input type="file" name="photo" accept="image/*" required>
                <small>Allowed: JPG, JPEG, PNG (Max 5MB)</small>
            </div>
            
            <hr style="margin: 20px 0; border: 1px solid #ddd;">
            
            <!-- Student Info Section -->
            <div class="form-group">
                <label>First Name:</label>
                <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Last Name:</label>
                <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Course:</label>
                <input type="text" name="course" value="<?php echo htmlspecialchars($user['course']); ?>">
            </div>
            
            <div class="form-group">
                <label>Year Level:</label>
                <input type="text" name="year_level" value="<?php echo htmlspecialchars($user['year_level']); ?>">
            </div>
            
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Address:</label>
                <input type="text" name="address" value="<?php echo htmlspecialchars($user['address']); ?>">
            </div>
            
            <div class="modal-buttons">
                <button type="submit" class="save-btn">💾 Save Changes</button>
                <button type="button" class="cancel-btn" onclick="closeModal()">❌ Cancel</button>
            </div>
        </form>
    </div>
</div>
<!-- MODAL END -->

<script src="js/script.js"></script>
</body>
</html>