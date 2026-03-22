<?php
session_start();
include 'db.php';

if (!isset($_SESSION['id_number']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit();
}

// Ensure the admin profile table exists
$conn->query("CREATE TABLE IF NOT EXISTS admin_profile (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Create default admin row if missing
$adminUsername = 'admin';
$defaultName = 'Administrator';
$defaultEmail = 'admin@sitin_system.local';
$defaultPhone = 'N/A';
$stmt = $conn->prepare("INSERT IGNORE INTO admin_profile (username, full_name, email, phone) VALUES (?, ?, ?, ?)");
$stmt->bind_param('ssss', $adminUsername, $defaultName, $defaultEmail, $defaultPhone);
$stmt->execute();
$stmt->close();

// Handle profile update submit
$adminMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_admin_profile') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    if ($full_name === '' || $email === '') {
        $adminMessage = 'Full name and email are required.';
    } else {
        $updateStmt = $conn->prepare("INSERT INTO admin_profile (username, full_name, email, phone) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), email=VALUES(email), phone=VALUES(phone)");
        $updateStmt->bind_param('ssss', $adminUsername, $full_name, $email, $phone);
        $updateStmt->execute();
        $updateStmt->close();
        $adminMessage = 'Admin profile updated successfully.';
    }
}

// Load admin profile
$profileStmt = $conn->prepare("SELECT username, full_name, email, phone FROM admin_profile WHERE username = ? LIMIT 1");
$profileStmt->bind_param('s', $adminUsername);
$profileStmt->execute();
$profileResult = $profileStmt->get_result();
$adminProfile = $profileResult->fetch_assoc();
$profileStmt->close();

// Student search listing
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search !== '') {
    $stmt = $conn->prepare("SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address FROM students WHERE id_number LIKE ? OR last_name LIKE ? OR first_name LIKE ?");
    $like = "%{$search}%";
    $stmt->bind_param('sss', $like, $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address FROM students ORDER BY last_name, first_name");
}

$total_students = $result ? $result->num_rows : 0;

// Load current sit-in reservations for admin view
$sitinRes = $conn->query("SELECT id, id_number, student_name, purpose, lab, remaining_sessions, created_at FROM sitin_reservations ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header class="topbar">
    <div class="topbar-left"><h2>Admin: Sit-in Monitoring</h2></div>
    <nav class="topbar-nav">
        <a href="admin_dashboard.php" class="active">Dashboard</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="admin-layout">
    <aside class="admin-sidebar">
        <h3>Admin Menu</h3>
        <ul>
            <li><button type="button" onclick="openFeature('homeModal')">Home</button></li>
            <li><button type="button" onclick="scrollToSearch()">Search Students</button></li>
            <li><button type="button" onclick="openFeature('sitInModal')">Sit-in</button></li>
            <li><button type="button" onclick="openFeature('recordsModal')">View Sit-in Records</button></li>
            <li><button type="button" onclick="openFeature('reportsModal')">Sit-in Reports</button></li>
            <li><button type="button" onclick="openFeature('feedbackModal')">Feedback Reports</button></li>
            <li><button type="button" onclick="openFeature('reservationModal')">Reservation</button></li>
            <li><button type="button" onclick="openFeature('profileModal')">Edit Admin Profile</button></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </aside>

    <main class="admin-main">
        <section class="dashboard-card">
            <h3>📊 Overview</h3>
            <p><strong>Admin:</strong> <?php echo htmlspecialchars($adminProfile['full_name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($adminProfile['email']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($adminProfile['phone']); ?></p>
            <p><strong>Total Registered Students:</strong> <?php echo $total_students; ?></p>
        </section>

        <section id="student-search" class="dashboard-card">
            <h3>🧑‍🎓 Student Monitoring List</h3>
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search by ID/Name" value="<?php echo htmlspecialchars($search); ?>" />
                <button type="submit">Search</button>
                <?php if ($search !== '') : ?>
                    <a class="clear-link" href="admin_dashboard.php">Clear</a>
                <?php endif; ?>
            </form>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Course</th>
                            <th>Year</th>
                            <th>Email</th>
                            <th>Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($student = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['id_number']); ?></td>
                                    <td><?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name'] . ' ' . $student['middle_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['course']); ?></td>
                                    <td><?php echo htmlspecialchars($student['year_level']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo htmlspecialchars($student['address']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center;color:#555;">No students found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="dashboard-card">
            <h3>🕒 Current Sit-in</h3>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Sit ID</th>
                            <th>ID Number</th>
                            <th>Name</th>
                            <th>Purpose</th>
                            <th>Sit Lab</th>
                            <th>Session</th>
                            <th>Status</th>
                            <th>Reserved At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($sitinRes && $sitinRes->num_rows > 0): ?>
                            <?php while ($sit = $sitinRes->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sit['id']); ?></td>
                                    <td><?php echo htmlspecialchars($sit['id_number']); ?></td>
                                    <td><?php echo htmlspecialchars($sit['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($sit['purpose']); ?></td>
                                    <td><?php echo htmlspecialchars($sit['lab']); ?></td>
                                    <td><?php echo htmlspecialchars($sit['remaining_sessions']); ?></td>
                                    <td>Active</td>
                                    <td><?php echo htmlspecialchars($sit['created_at']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8" style="text-align:center;color:#555;">No current sit-in records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<div class="admin-modal" id="homeModal">
    <div class="admin-modal-content">
        <span class="close-modal" onclick="closeFeature('homeModal')">&times;</span>
        <h3>Home</h3>
        <p>Welcome to the admin dashboard. Use menu buttons to work with reports and data.</p>
    </div>
</div>

<div class="admin-modal" id="sitInModal">
    <div class="admin-modal-content" style="max-width: 900px; width: 95%;">
        <span class="close-modal" onclick="closeFeature('sitInModal')">&times;</span>
        <h3>Current Sit-in Records</h3>
        <p style="margin-bottom: 12px;">Showing active sit-in reservations in the system.</p>
        <div class="table-wrap" style="max-height: 420px; overflow-y: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Sit ID</th>
                        <th>ID Number</th>
                        <th>Name</th>
                        <th>Purpose</th>
                        <th>Lab</th>
                        <th>Session</th>
                        <th>Reserved At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($sitinRes && $sitinRes->num_rows > 0): ?>
                        <?php while ($sit = $sitinRes->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sit['id']); ?></td>
                                <td><?php echo htmlspecialchars($sit['id_number']); ?></td>
                                <td><?php echo htmlspecialchars($sit['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($sit['purpose']); ?></td>
                                <td><?php echo htmlspecialchars($sit['lab']); ?></td>
                                <td><?php echo htmlspecialchars($sit['remaining_sessions']); ?></td>
                                <td><?php echo htmlspecialchars($sit['created_at']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center;color:#555;">No current sit-in records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <button class="save-btn" style="margin-top: 14px;" onclick="closeFeature('sitInModal')">Close</button>
    </div>
</div>

<div class="admin-modal" id="recordsModal">
    <div class="admin-modal-content">
        <span class="close-modal" onclick="closeFeature('recordsModal')">&times;</span>
        <h3>View Sit-in Records</h3>
        <p>Records can be shown here once you implement sit-in history table and queries.</p>
    </div>
</div>

<div class="admin-modal" id="reportsModal">
    <div class="admin-modal-content">
        <span class="close-modal" onclick="closeFeature('reportsModal')">&times;</span>
        <h3>Sit-in Reports</h3>
        <p>Wrap reporting queries to show summaries by student, course, or period.</p>
    </div>
</div>

<div class="admin-modal" id="feedbackModal">
    <div class="admin-modal-content">
        <span class="close-modal" onclick="closeFeature('feedbackModal')">&times;</span>
        <h3>Feedback Reports</h3>
        <p>Student feedback points can be listed here once you add a feedback table.</p>
    </div>
</div>

<div class="admin-modal" id="reservationModal">
    <div class="admin-modal-content">
        <span class="close-modal" onclick="closeFeature('reservationModal')">&times;</span>
        <h3>Reservation</h3>
        <p>Manage lab reservations and time slots here (future implementation).</p>
    </div>
</div>

<div class="admin-modal" id="profileModal">
    <div class="admin-modal-content">
        <span class="close-modal" onclick="closeFeature('profileModal')">&times;</span>
        <h3>Edit Admin Profile</h3>
        <?php if ($adminMessage !== ''): ?>
            <div class="success-message"><?php echo htmlspecialchars($adminMessage); ?></div>
        <?php endif; ?>
        <form method="POST" class="profile-update-form">
            <input type="hidden" name="action" value="update_admin_profile" />
            <label>Full Name</label>
            <input type="text" name="full_name" value="<?php echo htmlspecialchars($adminProfile['full_name']); ?>" required>
            <label>Email</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($adminProfile['email']); ?>" required>
            <label>Phone</label>
            <input type="text" name="phone" value="<?php echo htmlspecialchars($adminProfile['phone']); ?>">
            <button type="submit">Save</button>
        </form>
    </div>
</div>

<script src="js/script.js"></script>
</body>
</html>