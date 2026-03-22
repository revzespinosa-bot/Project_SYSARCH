<?php
session_start();
include "db.php";

// Check if user is logged in
if(!isset($_SESSION['id_number'])){
    header("Location: login.php");
    exit();
}

// Redirect admins to the admin dashboard
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
    header("Location: admin_dashboard.php");
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

// Ensure sit-in reservation table exists
$conn->query("CREATE TABLE IF NOT EXISTS sitin_reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_number VARCHAR(50) NOT NULL,
    student_name VARCHAR(150) NOT NULL,
    purpose VARCHAR(255) NOT NULL,
    lab VARCHAR(50) NOT NULL,
    time_in TIME NOT NULL,
    `date` DATE NOT NULL,
    remaining_sessions INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$reservationMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reserve_sitin') {
    $id_number = $conn->real_escape_string($_POST['id_number']);
    $student_name = $conn->real_escape_string($_POST['student_name']);
    $purpose = $conn->real_escape_string($_POST['purpose']);
    $lab = $conn->real_escape_string($_POST['lab']);
    $time_in = $conn->real_escape_string($_POST['time_in']);
    $date = $conn->real_escape_string($_POST['date']);
    $remaining_sessions = intval($_POST['remaining_sessions']);

    if ($id_number === '' || $student_name === '' || $purpose === '' || $lab === '' || $time_in === '' || $date === '') {
        $reservationMessage = 'Please fill all reservation fields.';
    } else {
        $insertStmt = $conn->prepare("INSERT INTO sitin_reservations (id_number, student_name, purpose, lab, time_in, `date`, remaining_sessions) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insertStmt->bind_param('ssssssi', $id_number, $student_name, $purpose, $lab, $time_in, $date, $remaining_sessions);
        if ($insertStmt->execute()) {
            $reservationMessage = '✅ Sit-in reservation created successfully.';
        } else {
            $reservationMessage = '❌ Could not create reservation. Please try again.';
        }
        $insertStmt->close();
    }
}

// Load existing reservations for this user
$reservations = [];
$reservationQuery = $conn->prepare("SELECT id, id_number, student_name, purpose, lab, time_in, `date`, remaining_sessions, created_at FROM sitin_reservations WHERE id_number = ? ORDER BY created_at DESC");
$reservationQuery->bind_param('s', $id);
$reservationQuery->execute();
$reservationResult = $reservationQuery->get_result();
if ($reservationResult) {
    while ($row = $reservationResult->fetch_assoc()) {
        $reservations[] = $row;
    }
}
$reservationQuery->close();

?>
<!DOCTYPE html>
<html>
<head>
    <title>User Profile</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="topbar">
    <div class="topbar-left"><h2>Student Dashboard</h2></div>
    <nav class="topbar-nav">
        <div class="dropdown">
            <button class="dropbtn">Notification ▼</button>
            <div class="dropdown-content">
                <a href="#" onclick="openFeature('notificationsModal')">View notifications</a>
                <a href="#" onclick="closeFeature('notificationsModal')">Clear all</a>
            </div>
        </div>
        <a href="Profile.php">Home</a>
        <a href="#" id="profileButton">Edit Profile</a>
        <a href="#" onclick="openFeature('historyModal')">History</a>
        <a href="#" onclick="openFeature('reservationModal')">Reservation</a>
        <a href="logout.php" class="logout-btn">Log out</a>
    </nav>
</header>

<main class="dashboard-grid">
    <section class="dashboard-card student-info-card">
        <h3>Student Information</h3>
        <div class="dashboard-card-content">
            <?php $hasPhoto = isset($user['photo']) && !empty($user['photo']); ?>
            <div class="profile-avatar-wrapper">
                <img src="<?php echo $hasPhoto ? 'uploads/'.htmlspecialchars($user['photo']) : 'https://via.placeholder.com/160?text=No+Photo'; ?>" alt="Student Photo" class="profile-photo profile-avatar">
            </div>
            <div class="profile-fields">
                <p>👤 <strong>Name:</strong> <?php echo htmlspecialchars(trim($user['first_name'].' '.$user['middle_name'].' '.$user['last_name'])); ?></p>
                <p>🆔 <strong>ID Number:</strong> <?php echo htmlspecialchars($user['id_number']); ?></p>
                <p>🎓 <strong>Course:</strong> <?php echo htmlspecialchars($user['course']); ?></p>
                <p>🔢 <strong>Year Level:</strong> <?php echo htmlspecialchars($user['year_level']); ?></p>
                <p>✉️ <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p>🏠 <strong>Address:</strong> <?php echo htmlspecialchars($user['address']); ?></p>
                <p>📶 <strong>Session:</strong> <?php echo isset($user['session']) ? htmlspecialchars($user['session']) : 'N/A'; ?></p>
            </div>

            <button class="save-btn" onclick="openModal()">✏️ Edit Profile</button>
            <button class="save-btn" style="margin-top: 10px; background: #0f4ad6;" onclick="openFeature('reservationModal')">🗓 Make Reservation</button>
        </div>
    </section>

    <section class="dashboard-card announcement-card">
        <h3>Announcement</h3>
        <div class="timeline-item">
            <div><strong>CCS Admin</strong> | 2026-Feb-11</div>
            <p>Important Announcement: We are excited to announce the launch of our new student dashboard with improved sit-in monitoring and profile features.</p>
        </div>
        <div class="timeline-item">
            <div><strong>CCS Admin</strong> | 2024-May-08</div>
            <p>Important Announcement: Please observe the laboratory rules and stand-by for the reservation schedule announcements.</p>
        </div>
    </section>

    <section class="dashboard-card rules-card">
        <h3>Rules and Regulation</h3>
        <div class="rules-title">University of Cebu</div>
        <div class="rules-subtitle">COLLEGE OF INFORMATION & COMPUTER STUDIES</div>
        <div class="rules-scroll">
            <ol>
                <li>Maintain silence, proper decorum, and discipline inside the laboratory.</li>
                <li>No food/drink; keep equipment clean and off personal devices.</li>
                <li>Games not allowed inside the lab—no disturbances permitted.</li>
                <li>Surfing only with instructor's permission; no unauthorized installs.</li>
            </ol>
        </div>
    </section>

</main>

<!-- MODAL START -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>✏️ Edit Student Information</h2>
        
        <!-- Current Photo Display -->
        <div class="current-photo">
            <?php $hasPhoto = isset($user['photo']) && !empty($user['photo']); ?>
            <?php if($hasPhoto){ ?>
                <img src="uploads/<?php echo htmlspecialchars($user['photo']); ?>" width="150" class="profile-photo">
                <p class="photo-label">Current Photo</p>
                <div class="photo-actions">
                    <form action="update_profile.php" method="POST" style="display:inline-block;">
                        <input type="hidden" name="id_number" value="<?php echo htmlspecialchars($user['id_number']); ?>">
                        <input type="hidden" name="delete_photo" value="1">
                        <button type="submit" class="cancel-btn" style="margin-top: 10px;">🗑 Delete Photo</button>
                    </form>
                </div>
            <?php } else { ?>
                <img src="https://via.placeholder.com/150?text=No+Photo" width="150" class="profile-photo">
                <p class="photo-label">No Photo</p>
            <?php } ?>
        </div>

        <!-- Photo Upload Form -->
        <form action="update_profile.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_number" value="<?php echo htmlspecialchars($user['id_number']); ?>">
            
<!-- Photo Upload Section (optional, only if your database supports photo) -->
                <div class="photo-upload-section">
                    <label>📷 Upload New Photo:</label>
                    <input type="file" name="photo" accept="image/*">
                    <small>Allowed: JPG, JPEG, PNG, GIF (Max 5MB). Optional.</small>
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

<div id="notificationsModal" class="admin-modal">
    <div class="admin-modal-content">
        <span class="close-modal" onclick="closeFeature('notificationsModal')">&times;</span>
        <h3>🔔 Notifications</h3>
        <ul>
            <li>New sit-in schedule posted for next week.</li>
            <li>Lab maintenance alert: Monday 8:00-11:00 AM.</li>
            <li>Reminder: Update your profile before the deadline.</li>
        </ul>
        <button class="save-btn" onclick="closeFeature('notificationsModal')">Close</button>
    </div>
</div>

<div id="historyModal" class="admin-modal">
    <div class="admin-modal-content">
        <span class="close-modal" onclick="closeFeature('historyModal')">&times;</span>
        <h3>📜 History</h3>
        <p>Recent activity:</p>
        <ol>
            <li>Logged in at <?php echo date('Y-m-d H:i:s'); ?></li>
            <li>Profile updated last week.</li>
            <li>One sit-in reservation completed.</li>
        </ol>
        <button class="save-btn" onclick="closeFeature('historyModal')">Close</button>
    </div>
</div>

<div id="reservationModal" class="admin-modal">
    <div class="admin-modal-content">
        <span class="close-modal" onclick="closeFeature('reservationModal')">&times;</span>
        <h3>🗓 Student Sit-in Reservation</h3>

        <?php if ($reservationMessage !== ''): ?>
            <div class="success-message" style="margin-bottom:12px; font-weight:bold; color:#065f46;"><?php echo htmlspecialchars($reservationMessage); ?></div>
        <?php endif; ?>

        <form method="POST" class="reservation-form">
            <input type="hidden" name="action" value="reserve_sitin">

            <label>ID Number</label>
            <input type="text" name="id_number" value="<?php echo htmlspecialchars($user['id_number']); ?>" readonly>

            <label>Student Name</label>
            <input type="text" name="student_name" value="<?php echo htmlspecialchars($user['first_name'].' '.$user['middle_name'].' '.$user['last_name']); ?>" readonly>

            <label>Purpose</label>
            <input type="text" name="purpose" placeholder="e.g. C Programming" required>

            <label>Lab</label>
            <input type="text" name="lab" placeholder="e.g. 524" required>

            <label>Time In</label>
            <input type="time" name="time_in" required>

            <label>Date</label>
            <input type="date" name="date" required>

            <label>Remaining Session</label>
            <input type="number" name="remaining_sessions" value="<?php echo isset($user['session']) ? (int)$user['session'] : 28; ?>" min="0" required>

            <div style="display:flex; gap:10px; margin-top:12px;">
                <button type="submit" class="save-btn">Reserve</button>
                <button type="button" class="cancel-btn" onclick="closeFeature('reservationModal')">Cancel</button>
            </div>
        </form>

        <?php if (!empty($reservations)): ?>
            <h4 style="margin-top: 18px;">Your Recent Reservations</h4>
            <div class="table-wrap" style="margin-top: 8px; max-height: 220px; overflow-y: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Purpose</th>
                            <th>Lab</th>
                            <th>Time</th>
                            <th>Date</th>
                            <th>Remaining</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $res): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($res['id']); ?></td>
                                <td><?php echo htmlspecialchars($res['purpose']); ?></td>
                                <td><?php echo htmlspecialchars($res['lab']); ?></td>
                                <td><?php echo htmlspecialchars($res['time_in']); ?></td>
                                <td><?php echo htmlspecialchars($res['date']); ?></td>
                                <td><?php echo htmlspecialchars($res['remaining_sessions']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>No reservation history yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL END -->

<script src="js/script.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const profileButton = document.getElementById('profileButton');
        if (profileButton) {
            profileButton.addEventListener('click', function(e) {
                e.preventDefault();
                openModal();
            });
        }
    });
</script>
</body>
</html>