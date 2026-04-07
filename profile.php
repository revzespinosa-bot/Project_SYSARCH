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

// Ensure sit-in reservation table exists
$conn->query("CREATE TABLE IF NOT EXISTS sitin_reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_number VARCHAR(50) NOT NULL,
    student_name VARCHAR(150) NOT NULL,
    purpose VARCHAR(255) NOT NULL,
    lab VARCHAR(50) NOT NULL,
    time_in TIME,
    `date` DATE,
    remaining_sessions INT NOT NULL,
    status ENUM('pending','approved','rejected','cancelled','completed') DEFAULT 'pending',
    time_out TIMESTAMP NULL,
    notified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Check and add status column if not exists
$checkCol = $conn->query("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sitin_reservations' AND COLUMN_NAME = 'status'");
if ($checkCol && $checkCol->fetch_assoc()['cnt'] == 0) {
    @$conn->query("ALTER TABLE sitin_reservations ADD COLUMN status ENUM('pending','approved','rejected','cancelled','completed') DEFAULT 'pending'");
} else {
    @$conn->query("ALTER TABLE sitin_reservations MODIFY COLUMN status ENUM('pending','approved','rejected','cancelled','completed') DEFAULT 'pending'");
}

// Check and add time_out column if not exists
$checkCol2 = $conn->query("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sitin_reservations' AND COLUMN_NAME = 'time_out'");
if ($checkCol2 && $checkCol2->fetch_assoc()['cnt'] == 0) {
    @$conn->query("ALTER TABLE sitin_reservations ADD COLUMN time_out TIMESTAMP NULL AFTER status");
}

// Check and add notified column if not exists
$checkCol3 = $conn->query("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sitin_reservations' AND COLUMN_NAME = 'notified'");
if ($checkCol3 && $checkCol3->fetch_assoc()['cnt'] == 0) {
    @$conn->query("ALTER TABLE sitin_reservations ADD COLUMN notified TINYINT(1) DEFAULT 0 AFTER time_out");
}

$reservationMessage = '';
$stmt = $conn->prepare("SELECT id_number, first_name, last_name, middle_name, course, year_level, email, address, photo, COALESCE(remaining_sessions, 28) as remaining_sessions FROM students WHERE id_number = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if(!$user){
    echo "User not found in database.";
    exit();
}

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
        $insertStmt = $conn->prepare("INSERT INTO sitin_reservations (id_number, student_name, purpose, lab, time_in, `date`, remaining_sessions, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        $insertStmt->bind_param('ssssssi', $id_number, $student_name, $purpose, $lab, $time_in, $date, $remaining_sessions);
        if ($insertStmt->execute()) {
            $reservationMessage = '✅ Sit-in reservation submitted successfully. Wait for admin approval.';
        } else {
            $reservationMessage = '❌ Could not create reservation. Please try again.';
        }
        $insertStmt->close();
    }
}

// Load existing reservations for this user
$reservations = [];
$reservationQuery = $conn->prepare("SELECT id, id_number, student_name, purpose, lab, time_in, `date`, remaining_sessions, status, created_at, notified FROM sitin_reservations WHERE id_number = ? ORDER BY created_at DESC");
$reservationQuery->bind_param('s', $id);
$reservationQuery->execute();
$reservationResult = $reservationQuery->get_result();
if ($reservationResult) {
    while ($row = $reservationResult->fetch_assoc()) {
        $reservations[] = $row;
    }
}
$reservationQuery->close();

// Check for newly approved/rejected reservations to show notification banner
$hasNewNotifications = false;
$notificationMessage = '';
foreach ($reservations as $res) {
    if (($res['status'] == 'approved' || $res['status'] == 'rejected') && empty($res['notified'])) {
        $hasNewNotifications = true;
        $notificationMessage = $res['status'] == 'approved' 
            ? 'Your sit-in request for ' . htmlspecialchars($res['purpose']) . ' has been approved!'
            : 'Your sit-in request for ' . htmlspecialchars($res['purpose']) . ' has been rejected.';
        break;
    }
}

// Load announcements for student view
$announcements = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 10");

// Load student's sit-in history
$studentHistory = $conn->prepare("SELECT * FROM sitin_history WHERE id_number = ? ORDER BY time_out DESC LIMIT 20");
$studentHistory->bind_param("s", $id);
$studentHistory->execute();
$historyResult = $studentHistory->get_result();
$historyRecords = [];
if ($historyResult) {
    while ($row = $historyResult->fetch_assoc()) {
        $historyRecords[] = $row;
    }
}
$studentHistory->close();

// Ensure feedback table exists
$conn->query("CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_number VARCHAR(50) NOT NULL,
    history_id INT DEFAULT NULL,
    rating INT NOT NULL,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Add history_id column if it doesn't exist (for existing tables)
$result = $conn->query("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'feedback' AND COLUMN_NAME = 'history_id'");
$row = $result->fetch_assoc();
if ($row['cnt'] == 0) {
    $conn->query("ALTER TABLE feedback ADD COLUMN history_id INT DEFAULT NULL AFTER id_number");
}

// Handle feedback submission
$feedbackMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_feedback') {
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);
    $history_id = intval($_POST['history_id']);
    
    if ($rating >= 0 && $rating <= 5) {
        // Check if feedback already exists for this history_id
        $checkStmt = $conn->prepare("SELECT id FROM feedback WHERE id_number = ? AND history_id = ?");
        $checkStmt->bind_param("si", $id, $history_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            // Update existing feedback
            $updateStmt = $conn->prepare("UPDATE feedback SET rating = ?, comment = ? WHERE id_number = ? AND history_id = ?");
            $updateStmt->bind_param("isss", $rating, $comment, $id, $history_id);
            $updateStmt->execute();
            $updateStmt->close();
            $feedbackMessage = 'Feedback updated!';
        } else {
            // Insert new feedback
            $insertStmt = $conn->prepare("INSERT INTO feedback (id_number, history_id, rating, comment) VALUES (?, ?, ?, ?)");
            $insertStmt->bind_param("siis", $id, $history_id, $rating, $comment);
            $insertStmt->execute();
            $insertStmt->close();
            $feedbackMessage = 'Thank you for your feedback!';
        }
        $checkStmt->close();
    }
}

// Load user's existing feedback (now per-history, so just check if any exists)
$hasAnyFeedback = $conn->prepare("SELECT COUNT(*) as cnt FROM feedback WHERE id_number = ?");
$hasAnyFeedback->bind_param("s", $id);
$hasAnyFeedback->execute();
$feedbackCount = $hasAnyFeedback->get_result()->fetch_assoc()['cnt'];
$hasAnyFeedback->close();

?>
<!DOCTYPE html>
<html>
<head>
    <title>User Profile</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/script.js"></script>
</head>
<body>

<header class="topbar">
    <div class="topbar-left"><h2>Student Dashboard</h2></div>
    <nav class="topbar-nav">
        <div class="dropdown">
            <button class="dropbtn">Notification ▼</button>
            <div class="dropdown-content">
                <a href="#" onclick="openFeature('notificationsModal')">View notifications</a>
                
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
    <?php if ($hasNewNotifications): ?>
    <div style="background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3); display: flex; align-items: center; gap: 15px; cursor: pointer;" onclick="openFeature('notificationsModal')">
        <span style="font-size: 24px;">🔔</span>
        <div style="flex: 1;">
            <strong>New Notification:</strong> <?php echo $notificationMessage; ?>
        </div>
        <button onclick="event.stopPropagation(); this.parentElement.remove()" style="background: rgba(255,255,255,0.2); border: none; color: white; padding: 5px 10px; border-radius: 5px; cursor: pointer;">✕</button>
    </div>
    <?php endif; ?>
    
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
               <p>📶 <strong>Sessions Left:</strong> <span style="color:#10b981; font-weight:bold; font-size:18px;"><?php echo $user['remaining_sessions']; ?></span></p>
            </div>

            <button class="save-btn" onclick="openModal()">✏️ Edit Profile</button>
            <button class="save-btn" style="margin-top: 10px; background: #0f4ad6;" onclick="openFeature('reservationModal')">🗓 Make Reservation</button>
        </div>
    </section>

    <section class="dashboard-card announcement-card">
        <h3>Announcement</h3>
        <?php if ($announcements && $announcements->num_rows > 0): ?>
            <?php while ($a = $announcements->fetch_assoc()): ?>
                <div class="timeline-item">
                    <div><strong><?php echo htmlspecialchars($a['title']); ?></strong> | <?php echo date('Y-M-d', strtotime($a['created_at'])); ?></div>
                    <p><?php echo htmlspecialchars($a['message']); ?></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="timeline-item">
                <div><strong>CCS Admin</strong> | <?php echo date('Y-M-d'); ?></div>
                <p>No announcements at this time.</p>
            </div>
        <?php endif; ?>
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
    <div class="admin-modal-content" style="max-width:500px; width:95%;">
        <span class="close-modal" onclick="closeFeature('notificationsModal')">&times;</span>
        <h3>🔔 My Notifications</h3>
        <div style="max-height:400px; overflow-y:auto;">
            <?php 
            // Get notifications from reservations
            $notifications = [];
            foreach ($reservations as $res) {
                if ($res['status'] == 'approved' && empty($res['notified'])) {
                    $notifications[] = [
                        'type' => 'success',
                        'icon' => '✅',
                        'title' => 'Approved',
                        'message' => 'Your sit-in request for ' . htmlspecialchars($res['purpose']) . ' has been approved!',
                        'date' => $res['created_at'],
                        'id' => $res['id']
                    ];
                } elseif ($res['status'] == 'rejected' && empty($res['notified'])) {
                    $notifications[] = [
                        'type' => 'error',
                        'icon' => '❌',
                        'title' => 'Rejected',
                        'message' => 'Your sit-in request for ' . htmlspecialchars($res['purpose']) . ' has been rejected.',
                        'date' => $res['created_at'],
                        'id' => $res['id']
                    ];
                }
            }
            ?>
            <?php if (!empty($notifications)): ?>
                <?php foreach ($notifications as $notif): ?>
                    <div style="padding:15px; margin-bottom:12px; border-radius:10px; <?php echo $notif['type'] == 'success' ? 'background:linear-gradient(135deg, #d1fae5, #a7f3d0); border-left:4px solid #10b981;' : 'background:linear-gradient(135deg, #fee2e2, #fecaca); border-left:4px solid #ef4444;'; ?>">
                        <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                            <span style="font-size:20px;"><?php echo $notif['icon']; ?></span>
                            <strong style="color:#1f2937;"><?php echo $notif['title']; ?></strong>
                        </div>
                        <p style="margin:0; color:#4b5563; font-size:14px;"><?php echo $notif['message']; ?></p>
                        <small style="color:#9ca3af;"><?php echo date('M j, Y g:i A', strtotime($notif['date'])); ?></small>
                        <?php 
                        // Mark as notified
                        $notifStmt = $conn->prepare("UPDATE sitin_reservations SET notified = 1 WHERE id = ?");
                        $notifStmt->bind_param("i", $notif['id']);
                        $notifStmt->execute();
                        $notifStmt->close();
                        ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align:center; padding:40px; color:#9ca3af;">
                    <span style="font-size:48px; display:block; margin-bottom:15px;">🔔</span>
                    <p>No new notifications</p>
                </div>
            <?php endif; ?>
        </div>
        <button class="save-btn" onclick="closeFeature('notificationsModal')" style="margin-top:15px;">Close</button>
    </div>
</div>

<div id="historyModal" class="admin-modal">
    <div class="admin-modal-content" style="max-width:700px; width:95%;">
        <span class="close-modal" onclick="closeFeature('historyModal')">&times;</span>
        <h3>📜 Sit-in History</h3>
        <div class="table-wrap" style="max-height:350px; overflow:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Purpose</th>
                        <th>Lab</th>
                        <th>Sessions</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Feedback</th>
                    </tr>
                </thead>
                <?php 
                // Get all feedback for this student
                $allFeedback = [];
                $feedbackStmt = $conn->prepare("SELECT history_id, rating, comment FROM feedback WHERE id_number = ?");
                $feedbackStmt->bind_param("s", $id);
                $feedbackStmt->execute();
                $feedbackResult = $feedbackStmt->get_result();
                while ($f = $feedbackResult->fetch_assoc()) {
                    $allFeedback[$f['history_id']] = $f;
                }
                $feedbackStmt->close();
                ?>
                <tbody>
                <?php if (!empty($historyRecords)): ?>
                    <?php foreach ($historyRecords as $h): 
                        $hasFeedback = isset($allFeedback[$h['id']]);
                        $fb = $hasFeedback ? $allFeedback[$h['id']] : null;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($h['purpose']); ?></td>
                            <td><?php echo htmlspecialchars($h['lab']); ?></td>
                            <td><?php echo $h['sessions_used']; ?></td>
                            <td><?php echo isset($h['time_in']) ? date('g:i A', strtotime($h['time_in'])) : 'N/A'; ?></td>
                            <td><?php echo date('g:i A', strtotime($h['time_out'])); ?></td>
                            <td>
                                <?php if ($hasFeedback): ?>
                                    <span style="font-size:16px;">
                                        <?php for($i=0;$i<=5;$i++): ?>
                                            <?php echo $i <= $fb['rating'] ? '⭐' : '☆'; ?>
                                        <?php endfor; ?>
                                    </span>
                                    <button type="button" onclick="openFeedbackModal('<?php echo $h['id']; ?>')" style="background:#10b981; color:white; border:none; padding:3px 8px; border-radius:4px; cursor:pointer; font-size:12px; margin-left:5px;">
                                        Edit
                                    </button>
                                <?php else: ?>
                                    <button type="button" onclick="openFeedbackModal('<?php echo $h['id']; ?>')" style="background:#8b5cf6; color:white; border:none; padding:5px 10px; border-radius:5px; cursor:pointer;">
                                        💬 Rate
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center; color:#666;">No sit-in history yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <button class="save-btn" onclick="closeFeature('historyModal')" style="margin-top:15px;">Close</button>
    </div>
</div>

<!-- FEEDBACK MODAL -->
<div id="feedbackModal" class="admin-modal">
    <div class="admin-modal-content" style="max-width:400px; width:95%;">
        <span class="close-modal" onclick="closeFeature('feedbackModal')">&times;</span>
        <h3>💬 Rate Your Experience</h3>
        <?php if ($feedbackMessage !== ''): ?>
            <div style="background:#d1fae5; color:#065f46; padding:10px; border-radius:6px; margin-bottom:12px;"><?php echo htmlspecialchars($feedbackMessage); ?></div>
        <?php endif; ?>
        <form method="POST" style="text-align:center;">
            <input type="hidden" name="action" value="submit_feedback" />
            <input type="hidden" name="history_id" id="feedback_history_id" />
            <div style="margin:20px 0;">
                <?php for($i=0;$i<=5;$i++): ?>
                    <button type="button" onclick="setRating(<?php echo $i; ?>)" 
                        style="font-size:32px; background:none; border:none; cursor:pointer; color:#ccc;" 
                        id="starbtn_<?php echo $i; ?>">
                        <?php echo $i === 0 ? '☆' : '⭐'; ?>
                    </button>
                <?php endfor; ?>
            </div>
            <input type="hidden" name="rating" id="feedback_rating" value="0" required>
            <p style="color:#666; font-size:14px;">Click a star to rate (0-5)</p>
            <textarea name="comment" rows="2" placeholder="Optional comment..." style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px; font-family:inherit;"></textarea>
            <button type="submit" class="save-btn" style="margin-top:15px;">Submit</button>
        </form>
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
            <select name="purpose" required>
                <option value="">Select Purpose</option>
                <option value="C Programming">C Programming</option>
                <option value="Java Programming">Java Programming</option>
                <option value="Web Development">Web Development</option>
                <option value="Database">Database</option>
                <option value="Networking">Networking</option>
                <option value="Graphics/Design">Graphics/Design</option>
                <option value="Research/Homework">Research/Homework</option>
                <option value="Practice/Review">Practice/Review</option>
                <option value="Other">Other</option>
            </select>

            <label>Lab</label>
            <select name="lab" required>
                <option value="">Select Lab</option>
                <option value="524">Lab 524</option>
                <option value="525">Lab 525</option>
                <option value="526">Lab 526</option>
                <option value="527">Lab 527</option>
                <option value="528">Lab 528</option>
                <option value="Mac Lab">Mac Lab</option>
                <option value="Network Lab">Network Lab</option>
            </select>

            <label>Time In</label>
            <input type="time" name="time_in" required>

            <label>Date</label>
            <input type="date" name="date" required>

            <label>Remaining Sessions</label>
<input type="number" name="remaining_sessions" value="<?php echo $user['remaining_sessions']; ?>" min="1" max="<?php echo $user['remaining_sessions']; ?>" required readonly>

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
                            <th>Sessions</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $res): 
                            $status_color = $res['status'] == 'approved' ? '#10b981' : ($res['status'] == 'rejected' ? '#ef4444' : '#f59e0b');
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($res['id']); ?></td>
                                <td><?php echo htmlspecialchars($res['purpose']); ?></td>
                                <td><?php echo htmlspecialchars($res['lab']); ?></td>
                                <td><?php echo htmlspecialchars($res['time_in']); ?></td>
                                <td><?php echo htmlspecialchars($res['date']); ?></td>
                                <td><?php echo htmlspecialchars($res['remaining_sessions']); ?></td>
                                <td><span style="background:<?php echo $status_color; ?>; color:white; padding:3px 8px; border-radius:4px; font-size:11px;"><?php echo ucfirst($res['status']); ?></span></td>
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
    
    function openFeedbackModal(historyId) {
        document.getElementById('feedback_history_id').value = historyId;
        document.getElementById('feedback_rating').value = 0;
        // Reset stars
        for(let i=0; i<=5; i++) {
            document.getElementById('starbtn_'+i).style.color = '#ccc';
            document.getElementById('starbtn_'+i).innerHTML = i === 0 ? '☆' : '⭐';
        }
        document.getElementById('feedbackModal').style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
    
    function setRating(rating) {
        document.getElementById('feedback_rating').value = rating;
        for(let i=0; i<=5; i++) {
            if(i <= rating) {
                document.getElementById('starbtn_'+i).style.color = '#f59e0b';
                document.getElementById('starbtn_'+i).innerHTML = '⭐';
            } else {
                document.getElementById('starbtn_'+i).style.color = '#ccc';
                document.getElementById('starbtn_'+i).innerHTML = '☆';
            }
        }
    }
</script>
</body>
</html>