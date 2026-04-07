<?php
session_start();
include 'db.php';

if (!isset($_SESSION['id_number']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit();
}

if (isset($_GET['export']) && $_GET['export'] !== '') {
    $export = $_GET['export'];
    $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
    $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-t');
    $date_from_safe = $conn->real_escape_string($date_from);
    $date_to_safe = $conn->real_escape_string($date_to);
    
    $query = "SELECT id_number, student_name, purpose, lab, sessions_used, time_out 
              FROM sitin_history 
              WHERE time_out BETWEEN '$date_from_safe 00:00:00' AND '$date_to_safe 23:59:59'
              ORDER BY time_out DESC";
    $result = $conn->query($query);
    
    $data = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    if ($export === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="sit_in_report_' . date('Y-m-d') . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID Number', 'Name', 'Purpose', 'Lab', 'Sessions Used', 'Time Out']);
        foreach ($data as $row) {
            fputcsv($output, [
                $row['id_number'],
                $row['student_name'],
                $row['purpose'],
                $row['lab'],
                $row['sessions_used'],
                date('Y-m-d H:i:s', strtotime($row['time_out']))
            ]);
        }
        fclose($output);
        exit();
    } elseif ($export === 'excel') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="sit_in_report_' . date('Y-m-d') . '.xls"');
        echo '<table border="1">';
        echo '<tr><th>ID Number</th><th>Name</th><th>Purpose</th><th>Lab</th><th>Sessions Used</th><th>Time Out</th></tr>';
        foreach ($data as $row) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['id_number']) . '</td>';
            echo '<td>' . htmlspecialchars($row['student_name']) . '</td>';
            echo '<td>' . htmlspecialchars($row['purpose']) . '</td>';
            echo '<td>' . htmlspecialchars($row['lab']) . '</td>';
            echo '<td>' . $row['sessions_used'] . '</td>';
            echo '<td>' . date('Y-m-d H:i:s', strtotime($row['time_out'])) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        exit();
    } elseif ($export === 'pdf') {
        header('Content-Type: text/html');
        header('Content-Disposition: attachment; filename="sit_in_report_' . date('Y-m-d') . '.html"');
        echo '<!DOCTYPE html>';
        echo '<html><head><title>Sit-in Report</title>';
        echo '<style>body{font-family:Arial,sans-serif;padding:20px;}h1{color:#333;}table{border-collapse:collapse;width:100%;margin-top:20px;}th,td{border:1px solid #ddd;padding:10px;text-align:left;}th{background:#4f46e5;color:white;}</style>';
        echo '</head><body>';
        echo '<h1>Sit-in Report</h1>';
        echo '<p>Date Range: ' . date('M j, Y', strtotime($date_from)) . ' to ' . date('M j, Y', strtotime($date_to)) . '</p>';
        echo '<table><tr><th>ID Number</th><th>Name</th><th>Purpose</th><th>Lab</th><th>Sessions</th><th>Time Out</th></tr>';
        foreach ($data as $row) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['id_number']) . '</td>';
            echo '<td>' . htmlspecialchars($row['student_name']) . '</td>';
            echo '<td>' . htmlspecialchars($row['purpose']) . '</td>';
            echo '<td>' . htmlspecialchars($row['lab']) . '</td>';
            echo '<td>' . $row['sessions_used'] . '</td>';
            echo '<td>' . date('M j, Y g:i A', strtotime($row['time_out'])) . '</td>';
            echo '</tr>';
        }
        echo '</table></body></html>';
        exit();
    }
}

    // Ensure the admin profile table exists
    $conn->query("CREATE TABLE IF NOT EXISTS admin_profile (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(50) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Ensure sit-in reservations table exists with all columns
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
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Check and add time_out column if not exists
    $checkCol = $conn->query("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sitin_reservations' AND COLUMN_NAME = 'time_out'");
    $checkResult = $checkCol ? $checkCol->fetch_assoc() : null;
    if ($checkResult && $checkResult['cnt'] == 0) {
        @$conn->query("ALTER TABLE sitin_reservations ADD COLUMN time_out TIMESTAMP NULL AFTER status");
    }

    // Add 'completed' to status enum if not present
    @$conn->query("ALTER TABLE sitin_reservations MODIFY COLUMN status ENUM('pending','approved','rejected','cancelled','completed') DEFAULT 'pending'");

    // Check and add notified column if not exists
    $checkColNotif = $conn->query("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sitin_reservations' AND COLUMN_NAME = 'notified'");
    if ($checkColNotif && $checkColNotif->fetch_assoc()['cnt'] == 0) {
        @$conn->query("ALTER TABLE sitin_reservations ADD COLUMN notified TINYINT(1) DEFAULT 0");
    }

    // Create default admin row if missing
    $adminUsername = 'admin';
    $defaultName = 'Administrator';
    $defaultEmail = 'admin@sitin_system.local';
    $defaultPhone = 'N/A';
    $stmt = $conn->prepare("INSERT IGNORE INTO admin_profile (username, full_name, email, phone) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssss', $adminUsername, $defaultName, $defaultEmail, $defaultPhone);
    $stmt->execute();
    $stmt->close();

    // Ensure feedback table exists
    $conn->query("CREATE TABLE IF NOT EXISTS feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_number VARCHAR(50) NOT NULL,
        history_id INT DEFAULT NULL,
        rating INT NOT NULL,
        comment TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Ensure computers table exists
    $conn->query("CREATE TABLE IF NOT EXISTS computers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lab_name VARCHAR(50) NOT NULL,
        computer_name VARCHAR(50) NOT NULL,
        status ENUM('available', 'in_use', 'maintenance') DEFAULT 'available',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Ensure reservations table exists
    $conn->query("CREATE TABLE IF NOT EXISTS reservations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_number VARCHAR(50) NOT NULL,
        student_name VARCHAR(100) NOT NULL,
        lab_name VARCHAR(50) NOT NULL,
        computer_name VARCHAR(50) NOT NULL,
        purpose VARCHAR(100) NOT NULL,
        reservation_date DATE NOT NULL,
        time_in TIME NOT NULL,
        time_out TIME,
        status ENUM('pending', 'approved', 'rejected', 'completed', 'cancelled') DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Ensure announcements table exists
    $conn->query("CREATE TABLE IF NOT EXISTS announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Handle reservation approval/rejection
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'approve_reservation') {
            $resid = intval($_POST['reservation_id']);
            $stmt = $conn->prepare("UPDATE reservations SET status = 'approved' WHERE id = ?");
            $stmt->bind_param("i", $resid);
            $stmt->execute();
            $stmt->close();
        } elseif ($_POST['action'] === 'reject_reservation') {
            $resid = intval($_POST['reservation_id']);
            $stmt = $conn->prepare("UPDATE reservations SET status = 'rejected' WHERE id = ?");
            $stmt->bind_param("i", $resid);
            $stmt->execute();
            $stmt->close();
        } elseif ($_POST['action'] === 'complete_reservation') {
            $resid = intval($_POST['reservation_id']);
            $stmt = $conn->prepare("UPDATE reservations SET status = 'completed', time_out = NOW() WHERE id = ?");
            $stmt->bind_param("i", $resid);
            $stmt->execute();
            $stmt->close();
        } elseif ($_POST['action'] === 'add_computer') {
            $lab_name = trim($_POST['lab_name']);
            $computer_name = trim($_POST['computer_name']);
            if ($lab_name && $computer_name) {
                $stmt = $conn->prepare("INSERT INTO computers (lab_name, computer_name) VALUES (?, ?)");
                $stmt->bind_param("ss", $lab_name, $computer_name);
                $stmt->execute();
                $stmt->close();
            }
        } elseif ($_POST['action'] === 'delete_computer') {
            $compid = intval($_POST['computer_id']);
            $stmt = $conn->prepare("DELETE FROM computers WHERE id = ?");
            $stmt->bind_param("i", $compid);
            $stmt->execute();
            $stmt->close();
        } elseif ($_POST['action'] === 'create_announcement') {
            $title = trim($_POST['title']);
            $message = trim($_POST['message']);
            if ($title && $message) {
                $stmt = $conn->prepare("INSERT INTO announcements (title, message) VALUES (?, ?)");
                $stmt->bind_param("ss", $title, $message);
                $stmt->execute();
                $stmt->close();
                header("Location: admin_dashboard.php?announcement_posted=1");
                exit();
            }
        }
    }

    // Handle sit-in reservation approval/rejection
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'approve_sitin') {
            $sitid = intval($_POST['sitin_id']);
            $stmt = $conn->prepare("UPDATE sitin_reservations SET status = 'approved', notified = 0 WHERE id = ?");
            $stmt->bind_param("i", $sitid);
            $stmt->execute();
            $stmt->close();
            header("Location: admin_dashboard.php?approved=1");
            exit();
        } elseif ($_POST['action'] === 'reject_sitin') {
            $sitid = intval($_POST['sitin_id']);
            $stmt = $conn->prepare("UPDATE sitin_reservations SET status = 'rejected', notified = 0 WHERE id = ?");
            $stmt->bind_param("i", $sitid);
            $stmt->execute();
            $stmt->close();
            header("Location: admin_dashboard.php?rejected=1");
            exit();
        } elseif ($_POST['action'] === 'start_sitin') {
            $id_number = trim($_POST['id_number']);
            $student_name = trim($_POST['student_name']);
            $purpose = trim($_POST['purpose']);
            $lab = trim($_POST['lab']);
            $remaining_sessions = intval($_POST['remaining_sessions']);
            
            if ($id_number && $student_name && $purpose && $lab && $remaining_sessions > 0) {
                $insertStmt = $conn->prepare("INSERT INTO sitin_reservations (id_number, student_name, purpose, lab, remaining_sessions, status, time_in, `date`) VALUES (?, ?, ?, ?, ?, 'approved', NOW(), CURDATE())");
                $insertStmt->bind_param('ssssi', $id_number, $student_name, $purpose, $lab, $remaining_sessions);
                $insertStmt->execute();
                $insertStmt->close();
                header("Location: admin_dashboard.php?sitin_started=1");
                exit();
            }
        }
    }

    // Handle student logout (end sit-in) - DEDUCTS SESSIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout_student_id'])) {
    $sit_id = intval($_POST['logout_student_id']);
    
    // Get sit-in details first
    $sitStmt = $conn->prepare("SELECT id_number, student_name, purpose, lab, remaining_sessions FROM sitin_reservations WHERE id = ? AND status = 'approved'");
    $sitStmt->bind_param("i", $sit_id);
    $sitStmt->execute();
    $sitDetails = $sitStmt->get_result()->fetch_assoc();
    $sitStmt->close();
    
    if ($sitDetails) {
        $student_id = $sitDetails['id_number'];
        $sessions_used = 1; // Deduct 1 session per sit-in
        
        // 1. Move to history table
        $historyStmt = $conn->prepare("INSERT INTO sitin_history (id_number, student_name, purpose, lab, sessions_used, time_out) VALUES (?, ?, ?, ?, ?, NOW())");
        $historyStmt->bind_param("sssii", $student_id, $sitDetails['student_name'], $sitDetails['purpose'], $sitDetails['lab'], $sessions_used);
        $historyStmt->execute();
        $historyStmt->close();
        
        // 2. Deduct from student's remaining sessions
        $updateStmt = $conn->prepare("UPDATE students SET remaining_sessions = GREATEST(0, remaining_sessions - ?) WHERE id_number = ?");
        $updateStmt->bind_param("is", $sessions_used, $student_id);
        $updateStmt->execute();
        $updateStmt->close();
        
        // 3. Update status to completed instead of deleting
        $completeStmt = $conn->prepare("UPDATE sitin_reservations SET status = 'completed', time_out = NOW() WHERE id = ?");
        $completeStmt->bind_param("i", $sit_id);
        $completeStmt->execute();
        $completeStmt->close();
    }
    
    header("Location: admin_dashboard.php?session_ended=1");
    exit();
}

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

    // Handle update student
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_student') {
        $original_id_number = trim($_POST['original_id_number']);
        $id_number = trim($_POST['id_number']);
        $last_name = trim($_POST['last_name']);
        $first_name = trim($_POST['first_name']);
        $middle_name = trim($_POST['middle_name']);
        $course = trim($_POST['course']);
        $year_level = trim($_POST['year_level']);
        $email = trim($_POST['email']);
        $address = trim($_POST['address']);
        
        if ($id_number && $last_name && $first_name && $course && $year_level) {
            // If ID changed, update the record with new ID
            if ($original_id_number !== $id_number) {
                // Check if new ID already exists
                $checkStmt = $conn->prepare("SELECT id_number FROM students WHERE id_number = ?");
                $checkStmt->bind_param('s', $id_number);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                
                if ($checkResult->num_rows > 0) {
                    $checkStmt->close();
                    header("Location: admin_dashboard.php?search=" . urlencode($search) . "&error=exists");
                    exit();
                }
                $checkStmt->close();
            }
            
            $updateStmt = $conn->prepare("UPDATE students SET id_number=?, last_name=?, first_name=?, middle_name=?, course=?, year_level=?, email=?, address=? WHERE id_number=?");
            $updateStmt->bind_param('sssssssss', $id_number, $last_name, $first_name, $middle_name, $course, $year_level, $email, $address, $original_id_number);
            $updateStmt->execute();
            $updateStmt->close();
            header("Location: admin_dashboard.php?search=" . urlencode($search) . "&updated=1");
            exit();
        }
    }
    
    // Handle delete student
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_student') {
        $id_number = trim($_POST['id_number']);
        if ($id_number) {
            $deleteStmt = $conn->prepare("DELETE FROM students WHERE id_number=?");
            $deleteStmt->bind_param('s', $id_number);
            $deleteStmt->execute();
            $deleteStmt->close();
            header("Location: admin_dashboard.php?search=" . urlencode($search) . "&deleted=1");
            exit();
        }
    }

    // Student search listing
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    if ($search !== '') {
        // Update search query to include sessions
    $stmt = $conn->prepare("SELECT id_number, last_name, first_name, middle_name, course, year_level, email, COALESCE(remaining_sessions, 28) as remaining_sessions FROM students WHERE id_number LIKE ? OR last_name LIKE ? OR first_name LIKE ?");
        $like = "%{$search}%";
        $stmt->bind_param('sss', $like, $like, $like);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query("SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address FROM students ORDER BY last_name, first_name");
    }

    $total_students = $result ? $result->num_rows : 0;

    // Load current sit-in reservations for admin view (only approved = active)
    // Join with students table
$sitinRes = $conn->query("
    SELECT sr.*, COALESCE(s.remaining_sessions, 28) as student_sessions_left 
    FROM sitin_reservations sr 
    LEFT JOIN students s ON sr.id_number = s.id_number 
    WHERE sr.status = 'approved'
    ORDER BY sr.created_at DESC
");

$leaderboardQuery = "SELECT 
    id_number,
    student_name,
    COUNT(*) as sitin_count,
    SUM(sessions_used) as total_sessions
FROM sitin_history 
GROUP BY id_number, student_name
ORDER BY sitin_count DESC
LIMIT 10";
$leaderboardResult = $conn->query($leaderboardQuery);

$totalSitinsAllTime = $conn->query("SELECT COUNT(*) as total FROM sitin_history")->fetch_assoc()['total'];
$totalSessionsUsed = $conn->query("SELECT COALESCE(SUM(sessions_used), 0) as total FROM sitin_history")->fetch_assoc()['total'];
$totalStudentsSitin = $conn->query("SELECT COUNT(DISTINCT id_number) as total FROM sitin_history")->fetch_assoc()['total'];


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
                <li><button type="button" onclick="openFeature('leaderboardModal')">Leaderboard</button></li>
                <li><button type="button" onclick="openFeature('searchModal')">Search Students</button></li>
                <li><button type="button" onclick="openFeature('currentSitInModal')">Current Sit-in</button></li>
                <li><button type="button" onclick="openFeature('recordsModal')">View Sit-in Records</button></li>
                <li><button type="button" onclick="openFeature('reportsModal')">Sit-in Reports</button></li>
                <li><button type="button" onclick="openFeature('announcementModal')">Announcements</button></li>
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

            <section class="leaderboard-section">
                <div class="leaderboard-header">
                    <h3>🏆 Top Sit-in Students</h3>
                    <span class="leaderboard-subtitle">All-time leaderboard</span>
                </div>
                <div class="leaderboard-stats">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">🏅</div>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo $totalSitinsAllTime; ?></span>
                            <span class="stat-label">Total Sit-ins</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">⏱️</div>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo $totalSessionsUsed; ?></span>
                            <span class="stat-label">Sessions Used</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #6366f1, #4f46e5);">👥</div>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo $totalStudentsSitin; ?></span>
                            <span class="stat-label">Unique Students</span>
                        </div>
                    </div>
                </div>
                <div class="leaderboard-list">
                    <?php $rank = 1; ?>
                    <?php if ($leaderboardResult && $leaderboardResult->num_rows > 0): ?>
                        <?php while ($row = $leaderboardResult->fetch_assoc()): ?>
                            <div class="leaderboard-item <?php echo $rank <= 3 ? 'top-' . $rank : ''; ?>">
                                <div class="rank-badge">
                                    <?php if ($rank === 1): ?><span class="medal gold">🥇</span>
                                    <?php elseif ($rank === 2): ?><span class="medal silver">🥈</span>
                                    <?php elseif ($rank === 3): ?><span class="medal bronze">🥉</span>
                                    <?php else: ?><span class="rank-num"><?php echo $rank; ?></span><?php endif; ?>
                                </div>
                                <div class="student-info">
                                    <span class="student-name"><?php echo htmlspecialchars($row['student_name']); ?></span>
                                    <span class="student-id"><?php echo htmlspecialchars($row['id_number']); ?></span>
                                </div>
                                <div class="student-stats">
                                    <div class="stat-pill">
                                        <span class="pill-value"><?php echo $row['sitin_count']; ?></span>
                                        <span class="pill-label">sit-ins</span>
                                    </div>
                                    <div class="stat-pill sessions">
                                        <span class="pill-value"><?php echo $row['total_sessions']; ?></span>
                                        <span class="pill-label">sessions</span>
                                    </div>
                                </div>
                            </div>
                            <?php $rank++; ?>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-data">No sit-in data yet. Start a sit-in to see the leaderboard!</div>
                    <?php endif; ?>
                </div>
            </section>

        <!-- SEARCH MODAL TABLE - CORRECTED -->
    <!-- ✅ 100% WORKING SEARCH MODAL -->
    <div class="admin-modal" id="searchModal" style="display:none;">
        <div class="admin-modal-content" style="max-width: 1000px; width:95%;">
            <span class="close-modal" onclick="closeFeature('searchModal')">&times;</span>
            <h3>🔍 Search Students</h3>

            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search by ID/Name"
                    value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">Search</button>
            </form>

            <div class="table-wrap" style="max-height:400px; overflow:auto;">
                <table>
                    <thead>
                <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Course</th>
                <th>Year</th>
                <th>Email</th>
                <th>Sessions Left</th>  <!-- ✅ NEW COLUMN -->
                <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
    <?php 
    // RELOAD STUDENT RESULTS FOR SEARCH MODAL
    // ✅ FIXED SEARCH QUERY - INCLUDES remaining_sessions
if ($search !== '') {
    $stmt = $conn->prepare("SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address, COALESCE(remaining_sessions, 28) as remaining_sessions FROM students WHERE id_number LIKE ? OR last_name LIKE ? OR first_name LIKE ?");
    $like = "%{$search}%";
    $stmt->bind_param('sss', $like, $like, $like);
    $stmt->execute();
    $searchResult = $stmt->get_result();
} else {
    $searchResult = $conn->query("SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address, COALESCE(remaining_sessions, 28) as remaining_sessions FROM students ORDER BY last_name, first_name LIMIT 50");
}

    if ($searchResult && $searchResult->num_rows > 0): ?>
        <?php while ($student = $searchResult->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($student['id_number']); ?></td>
                <td><?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name']); ?></td>
                <td><?php echo htmlspecialchars($student['course']); ?></td>
                <td><?php echo htmlspecialchars($student['year_level']); ?></td>
                <td><?php echo htmlspecialchars($student['email']); ?></td>
<td style="font-weight:600; color:#10b981;">
    <?php echo $student['remaining_sessions'] ?? 28; ?>
</td>
<td style="display:flex; gap:5px;">
    <button class="sitin-btn" type="button" 
        onclick="document.getElementById('sitInFormModal').style.display='block'; 
                 document.getElementById('sitin_id').value='<?php echo addslashes($student['id_number']); ?>'; 
                 document.getElementById('sitin_name').value='<?php echo addslashes($student['first_name'].' '.$student['last_name']); ?>'; 
                 document.body.style.overflow='hidden';">
        🚀 Sit-in
    </button>
    <button type="button" class="edit-btn" 
        onclick="openEditStudentModal('<?php echo addslashes($student['id_number']); ?>', '<?php echo addslashes($student['last_name']); ?>', '<?php echo addslashes($student['first_name']); ?>', '<?php echo addslashes($student['middle_name'] ?? ''); ?>', '<?php echo addslashes($student['course']); ?>', '<?php echo addslashes($student['year_level']); ?>', '<?php echo addslashes($student['email']); ?>', '<?php echo addslashes($student['address'] ?? ''); ?>')">
        ✏️ Edit
    </button>
    <button type="button" class="delete-btn" onclick="confirmDeleteStudent('<?php echo addslashes($student['id_number']); ?>')">
        🗑️ Delete
    </button>
</td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="7" style="text-align:center; color:#666;">No students found. Try different search term.</td></tr>
    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <div class="admin-modal" id="currentSitInModal" style="display:none;">
        <div class="admin-modal-content" style="max-width:1000px; width:95%;">
            <span class="close-modal" onclick="closeFeature('currentSitInModal')">&times;</span>
            <h3>🕒 Current Sit-in (<?php echo $sitinRes ? $sitinRes->num_rows : 0; ?> Active)</h3>
            <?php if (isset($_GET['session_ended'])): ?>
                <div style="background:#d1fae5; color:#065f46; padding:12px; border-radius:8px; margin-bottom:15px; border-left:4px solid #10b981; font-weight:500;">
                    ✅ Session ended successfully! 1 session deducted from student.
                </div>
            <?php endif; ?>
            <div class="table-wrap" style="max-height:400px; overflow:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Sit ID</th>
                            <th>ID Number</th>
                            <th>Name</th>
                            <th>Purpose</th>
                            <th>Lab</th>
                            <th>Sessions Left</th>
                            <th>Status</th>
                            <th>Time</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
    <?php 
    $sitinResModal = $conn->query("
        SELECT sr.*, COALESCE(s.remaining_sessions, 28) as remaining_sessions 
        FROM sitin_reservations sr 
        LEFT JOIN students s ON sr.id_number = s.id_number 
        WHERE sr.status = 'approved'
        ORDER BY sr.created_at DESC
    ");
    ?>
    <?php if ($sitinResModal && $sitinResModal->num_rows > 0): ?>
        <?php while ($sit = $sitinResModal->fetch_assoc()): ?>
            <tr>
                <td><?php echo $sit['id']; ?></td>
                <td><?php echo htmlspecialchars($sit['id_number']); ?></td>
                <td><?php echo htmlspecialchars($sit['student_name']); ?></td>
                <td><?php echo htmlspecialchars($sit['purpose']); ?></td>
                <td><?php echo htmlspecialchars($sit['lab']); ?></td>
                <td><?php echo $sit['remaining_sessions']; ?></td>
                <td><span style="color: green;">Active</span></td>
                <td><?php echo date('M j, Y g:i A', strtotime($sit['created_at'])); ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="logout_student_id" value="<?php echo $sit['id']; ?>">
                        <button type="submit" class="end-btn" onclick="return confirm('End this sit-in session?')">End</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="9" style="text-align:center; color:#666;">No active sit-in sessions</td></tr>
    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="admin-modal" id="homeModal" style="display:none;">
        <div class="admin-modal-content">
            <span class="close-modal" onclick="closeFeature('homeModal')">&times;</span>
            <h3>Home</h3>
            <p>Welcome to the admin dashboard. Use menu buttons to work with reports and data.</p>
        </div>
    </div>

    <div class="admin-modal" id="leaderboardModal" style="display:none;">
        <div class="admin-modal-content" style="max-width:700px; width:95%;">
            <span class="close-modal" onclick="closeFeature('leaderboardModal')">&times;</span>
            <div style="text-align:center; margin-bottom:30px;">
                <h3 style="font-size:28px; margin-bottom:8px;">🏆 Leaderboard</h3>
                <p style="color:#64748b;">Top 10 students with the most sit-ins</p>
            </div>
            <div class="leaderboard-stats" style="margin-bottom:30px;">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">🏅</div>
                    <div class="stat-info">
                        <span class="stat-value"><?php echo $totalSitinsAllTime; ?></span>
                        <span class="stat-label">Total Sit-ins</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">⏱️</div>
                    <div class="stat-info">
                        <span class="stat-value"><?php echo $totalSessionsUsed; ?></span>
                        <span class="stat-label">Sessions Used</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #6366f1, #4f46e5);">👥</div>
                    <div class="stat-info">
                        <span class="stat-value"><?php echo $totalStudentsSitin; ?></span>
                        <span class="stat-label">Unique Students</span>
                    </div>
                </div>
            </div>
            <div class="leaderboard-list">
                <?php $rank = 1; ?>
                <?php if ($leaderboardResult && $leaderboardResult->num_rows > 0): ?>
                    <?php while ($row = $leaderboardResult->fetch_assoc()): ?>
                        <div class="leaderboard-item <?php echo $rank <= 3 ? 'top-' . $rank : ''; ?>">
                            <div class="rank-badge">
                                <?php if ($rank === 1): ?><span class="medal gold">🥇</span>
                                <?php elseif ($rank === 2): ?><span class="medal silver">🥈</span>
                                <?php elseif ($rank === 3): ?><span class="medal bronze">🥉</span>
                                <?php else: ?><span class="rank-num"><?php echo $rank; ?></span><?php endif; ?>
                            </div>
                            <div class="student-info">
                                <span class="student-name"><?php echo htmlspecialchars($row['student_name']); ?></span>
                                <span class="student-id"><?php echo htmlspecialchars($row['id_number']); ?></span>
                            </div>
                            <div class="student-stats">
                                <div class="stat-pill">
                                    <span class="pill-value"><?php echo $row['sitin_count']; ?></span>
                                    <span class="pill-label">sit-ins</span>
                                </div>
                                <div class="stat-pill sessions">
                                    <span class="pill-value"><?php echo $row['total_sessions']; ?></span>
                                    <span class="pill-label">sessions</span>
                                </div>
                            </div>
                        </div>
                        <?php $rank++; ?>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-data">No sit-in data yet. Start a sit-in to see the leaderboard!</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ✅ FIXED MODERN SIT-IN FORM -->
    <div class="admin-modal" id="sitInFormModal" style="display:none;">
        <div class="sitin-form-modern" style="max-width:500px; width:95%; margin:5% auto; background:white; border-radius:20px; box-shadow:0 20px 60px rgba(0,0,0,0.3); padding:30px; position:relative;">
            
            <!-- Header -->
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:30px;">
                <div style="display:flex; align-items:center; gap:15px;">
                    <div style="font-size:32px;">🎯</div>
                    <h3 style="margin:0; font-size:24px; font-weight:700; color:#1f2937;">Start Sit-in</h3>
                </div>
                <span class="close-modal" onclick="closeSitInForm()" style="font-size:28px; cursor:pointer; color:#9ca3af; padding:10px; border-radius:50%; transition:all 0.3s;" onmouseover="this.style.background='#f3f4f6'; this.style.color='#374151'" onmouseout="this.style.background='transparent'; this.style.color='#9ca3af'">&times;</span>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="start_sitin">
                <!-- Student Info -->
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:30px; padding:20px; background:linear-gradient(135deg,#f8fafc,#e2e8f0); border-radius:16px;">
                    <div>
                        <label style="display:block; font-size:13px; font-weight:600; color:#64748b; margin-bottom:8px; text-transform:uppercase;">ID Number</label>
                        <input type="text" id="sitin_id" name="id_number" readonly style="width:100%; padding:15px; border:none; background:transparent; font-size:18px; font-weight:700; color:#1e293b;">
                    </div>
                    <div>
                        <label style="display:block; font-size:13px; font-weight:600; color:#64748b; margin-bottom:8px; text-transform:uppercase;">Student Name</label>
                        <input type="text" id="sitin_name" name="student_name" readonly style="width:100%; padding:15px; border:none; background:transparent; font-size:18px; font-weight:700; color:#1e293b;">
                    </div>
                </div>

                <!-- Form Fields -->
                <div style="margin-bottom:25px;">
                    <label style="display:block; margin-bottom:10px; font-weight:600; color:#374151; font-size:15px;">Purpose <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="purpose" placeholder="e.g., Lab Activity, Project Work" required 
                        style="width:100%; padding:16px 20px; border:2px solid #e5e7eb; border-radius:12px; font-size:16px; transition:all 0.3s; box-sizing:border-box;"
                        onfocus="this.style.borderColor='#6366f1'; this.style.boxShadow='0 0 0 3px rgba(99,102,241,0.1)'"
                        onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:30px;">
                    <div>
                        <label style="display:block; margin-bottom:10px; font-weight:600; color:#374151; font-size:15px;">Lab <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="lab" placeholder="e.g., Lab 101, CS Lab" required 
                            style="width:100%; padding:16px 20px; border:2px solid #e5e7eb; border-radius:12px; font-size:16px; transition:all 0.3s; box-sizing:border-box;"
                            onfocus="this.style.borderColor='#6366f1'; this.style.boxShadow='0 0 0 3px rgba(99,102,241,0.1)'"
                            onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:10px; font-weight:600; color:#374151; font-size:15px;">Sessions <span style="color:#ef4444;">*</span></label>
                        <input type="number" name="remaining_sessions" value="1" min="1" max="10" required 
                            style="width:100%; padding:16px 20px; border:2px solid #e5e7eb; border-radius:12px; font-size:18px; font-weight:600; color:#059669; transition:all 0.3s; box-sizing:border-box;"
                            onfocus="this.style.borderColor='#10b981'; this.style.boxShadow='0 0 0 3px rgba(16,185,129,0.1)'"
                            onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                    </div>
                </div>

                <!-- Buttons -->
                <div style="display:flex; justify-content:flex-end; gap:15px;">
                    <button type="button" onclick="closeSitInForm()" 
                            style="padding:16px 32px; border:none; border-radius:12px; background:#f3f4f6; color:#6b7280; font-size:16px; font-weight:600; cursor:pointer; transition:all 0.3s; flex:1; max-width:150px;"
                            onmouseover="this.style.background='#e5e7eb'; this.style.transform='translateY(-2px)'"
                            onmouseout="this.style.background='#f3f4f6'; this.style.transform='translateY(0)'">
                        Cancel
                    </button>
                    <button type="submit" name="start_sitin" 
                            style="padding:16px 32px; border:none; border-radius:12px; background:linear-gradient(135deg,#10b981,#059669); color:white; font-size:16px; font-weight:600; cursor:pointer; transition:all 0.3s; flex:1; max-width:180px; display:flex; align-items:center; justify-content:center; gap:10px;"
                            onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 10px 25px rgba(16,185,129,0.4)'"
                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(16,185,129,0.2)'">
                        ✅ Start Sit-in
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div class="admin-modal" id="recordsModal" style="display:none;">
        <div class="admin-modal-content" style="max-width:1000px; width:95%;">
            <span class="close-modal" onclick="closeFeature('recordsModal')">&times;</span>
            <h3>📜 Sit-in History</h3>
            <div style="display:flex; gap:8px; margin-bottom:15px; margin-left:auto;">
                <button type="button" onclick="exportRecords('csv')" class="export-btn csv">📄 CSV</button>
                <button type="button" onclick="exportRecords('excel')" class="export-btn excel">📊 Excel</button>
                <button type="button" onclick="exportRecords('pdf')" class="export-btn pdf">🖨️ Print</button>
            </div>
            <div class="table-wrap" style="max-height:400px; overflow:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID Number</th>
                            <th>Name</th>
                            <th>Purpose</th>
                            <th>Lab</th>
                            <th>Sessions Used</th>
                            <th>Time Out</th>
                        </tr>
                    </thead>
                    <tbody>
                <?php   
                $history = $conn->query("
                    SELECT * FROM sitin_history 
                    ORDER BY time_out DESC LIMIT 100
                ");
                ?>
                <?php if ($history && $history->num_rows > 0): ?>
                    <?php while ($record = $history->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['id_number']); ?></td>
                            <td><?php echo htmlspecialchars($record['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['purpose']); ?></td>
                            <td><?php echo htmlspecialchars($record['lab']); ?></td>
                            <td><?php echo $record['sessions_used']; ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($record['time_out'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center; color:#666;">No sit-in records found.</td></tr>
                <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="admin-modal" id="reportsModal" style="display:none;">
        <div class="admin-modal-content" style="max-width:1200px; width:95%;">
            <span class="close-modal" onclick="closeFeature('reportsModal')">&times;</span>
            <h3>📊 Sit-in Reports</h3>
            
            <?php
            $report_search = isset($_GET['report_search']) ? trim($_GET['report_search']) : '';
            $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
            $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-t');
            $report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'summary';
            
            $date_from_safe = $conn->real_escape_string($date_from);
            $date_to_safe = $conn->real_escape_string($date_to);
            ?>
            
            <form method="GET" class="search-form" style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:20px;">
                <input type="hidden" name="report_search" value="<?php echo htmlspecialchars($report_search); ?>">
                <div style="display:flex; align-items:center; gap:5px;">
                    <label style="font-weight:600;">From:</label>
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div style="display:flex; align-items:center; gap:5px;">
                    <label style="font-weight:600;">To:</label>
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <button type="submit">Filter</button>
                <div class="export-buttons" style="display:flex; gap:8px; margin-left:auto;">
                    <button type="button" onclick="exportReport('csv')" class="export-btn csv">📄 CSV</button>
                    <button type="button" onclick="exportReport('excel')" class="export-btn excel">📊 Excel</button>
                    <button type="button" onclick="exportReport('pdf')" class="export-btn pdf">🖨️ Print</button>
                </div>
            </form>
            
            <?php
            $total_sessions = 0;
            $total_students_report = 0;
            
            $summaryQuery = "SELECT 
                COUNT(*) as total_sitins,
                SUM(sessions_used) as total_sessions,
                COUNT(DISTINCT id_number) as unique_students,
                COUNT(DISTINCT lab) as labs_used
            FROM sitin_history 
            WHERE time_out BETWEEN '$date_from_safe 00:00:00' AND '$date_to_safe 23:59:59'";
            $summaryResult = $conn->query($summaryQuery);
            $summary = $summaryResult ? $summaryResult->fetch_assoc() : null;
            
            $byStudentQuery = "SELECT 
                id_number,
                student_name,
                COUNT(*) as sitin_count,
                SUM(sessions_used) as total_sessions
            FROM sitin_history 
            WHERE time_out BETWEEN '$date_from_safe 00:00:00' AND '$date_to_safe 23:59:59'
            GROUP BY id_number, student_name
            ORDER BY sitin_count DESC";
            $byStudentResult = $conn->query($byStudentQuery);
            
            $byLabQuery = "SELECT 
                lab,
                COUNT(*) as sitin_count,
                SUM(sessions_used) as total_sessions
            FROM sitin_history 
            WHERE time_out BETWEEN '$date_from_safe 00:00:00' AND '$date_to_safe 23:59:59'
            GROUP BY lab
            ORDER BY sitin_count DESC";
            $byLabResult = $conn->query($byLabQuery);
            
            $byCourseQuery = "SELECT 
                s.course,
                COUNT(sh.id_number) as sitin_count,
                SUM(sh.sessions_used) as total_sessions
            FROM sitin_history sh
            LEFT JOIN students s ON sh.id_number = s.id_number
            WHERE sh.time_out BETWEEN '$date_from_safe 00:00:00' AND '$date_to_safe 23:59:59'
            GROUP BY s.course
            ORDER BY sitin_count DESC";
            $byCourseResult = $conn->query($byCourseQuery);
            ?>
            
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:15px; margin-bottom:20px;">
                <div style="background:linear-gradient(135deg, #667eea, #764ba2); color:white; padding:20px; border-radius:12px; text-align:center;">
                    <div style="font-size:28px; font-weight:700;"><?php echo $summary ? $summary['total_sitins'] : 0; ?></div>
                    <div style="font-size:14px; opacity:0.9;">Total Sit-ins</div>
                </div>
                <div style="background:linear-gradient(135deg, #10b981, #059669); color:white; padding:20px; border-radius:12px; text-align:center;">
                    <div style="font-size:28px; font-weight:700;"><?php echo $summary ? ($summary['total_sessions'] ?? 0) : 0; ?></div>
                    <div style="font-size:14px; opacity:0.9;">Sessions Used</div>
                </div>
                <div style="background:linear-gradient(135deg, #f59e0b, #d97706); color:white; padding:20px; border-radius:12px; text-align:center;">
                    <div style="font-size:28px; font-weight:700;"><?php echo $summary ? $summary['unique_students'] : 0; ?></div>
                    <div style="font-size:14px; opacity:0.9;">Unique Students</div>
                </div>
                <div style="background:linear-gradient(135deg, #ef4444, #dc2626); color:white; padding:20px; border-radius:12px; text-align:center;">
                    <div style="font-size:28px; font-weight:700;"><?php echo $summary ? $summary['labs_used'] : 0; ?></div>
                    <div style="font-size:14px; opacity:0.9;">Labs Used</div>
                </div>
            </div>
            
            <div class="table-wrap" style="max-height:400px; overflow:auto;">
                <h4 style="margin:15px 0 10px; color:#374151; font-size:18px;">🏆 Top Student Sit-iners</h4>
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>ID Number</th>
                            <th>Name</th>
                            <th>Sit-ins</th>
                            <th>Sessions Used</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $topStudentsQuery = "SELECT 
                            id_number,
                            student_name,
                            COUNT(*) as sitin_count,
                            SUM(sessions_used) as total_sessions
                        FROM sitin_history 
                        WHERE time_out BETWEEN '$date_from_safe 00:00:00' AND '$date_to_safe 23:59:59'
                        GROUP BY id_number, student_name
                        ORDER BY sitin_count DESC
                        LIMIT 10";
                        $topStudentsResult = $conn->query($topStudentsQuery);
                        $rank = 1;
                        ?>
                        <?php if ($topStudentsResult && $topStudentsResult->num_rows > 0): ?>
                            <?php while ($row = $topStudentsResult->fetch_assoc()): ?>
                                <tr<?php if ($rank === 1): ?> style="background:#fef3c7; font-weight:700;"<?php elseif ($rank === 2): ?> style="background:#f1f5f9;"<?php elseif ($rank === 3): ?> style="background:#fff7ed;"<?php endif; ?>>
                                    <td>
                                        <?php if ($rank === 1): ?>🥇
                                        <?php elseif ($rank === 2): ?>🥈
                                        <?php elseif ($rank === 3): ?>🥉
                                        <?php else: echo $rank; endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['id_number']); ?></td>
                                    <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                    <td><?php echo $row['sitin_count']; ?></td>
                                    <td><?php echo $row['total_sessions']; ?></td>
                                </tr>
                                <?php $rank++; ?>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center; color:#666;">No student data found for selected period.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <h4 style="margin:20px 0 10px; color:#374151;">By Student</h4>
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>ID Number</th>
                            <th>Name</th>
                            <th>Sit-ins</th>
                            <th>Sessions Used</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $byStudentResult->data_seek(0);
                        $rank = 1;
                        ?>
                        <?php if ($byStudentResult && $byStudentResult->num_rows > 0): ?>
                            <?php while ($row = $byStudentResult->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $rank++; ?></td>
                                    <td><?php echo htmlspecialchars($row['id_number']); ?></td>
                                    <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                    <td><?php echo $row['sitin_count']; ?></td>
                                    <td><?php echo $row['total_sessions']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center; color:#666;">No student data found for selected period.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <h4 style="margin:20px 0 10px; color:#374151;">By Lab</h4>
                <table>
                    <thead>
                        <tr>
                            <th>Lab</th>
                            <th>Sit-ins</th>
                            <th>Sessions Used</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($byLabResult && $byLabResult->num_rows > 0): ?>
                            <?php while ($row = $byLabResult->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['lab']); ?></td>
                                    <td><?php echo $row['sitin_count']; ?></td>
                                    <td><?php echo $row['total_sessions']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align:center; color:#666;">No lab data found for selected period.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <h4 style="margin:20px 0 10px; color:#374151;">By Course</h4>
                <table>
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Sit-ins</th>
                            <th>Sessions Used</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($byCourseResult && $byCourseResult->num_rows > 0): ?>
                            <?php while ($row = $byCourseResult->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['course'] ?: 'N/A'); ?></td>
                                    <td><?php echo $row['sitin_count']; ?></td>
                                    <td><?php echo $row['total_sessions']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align:center; color:#666;">No course data found for selected period.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="admin-modal" id="announcementModal" style="display:none;">
        <div class="admin-modal-content" style="max-width:600px; width:95%;">
            <span class="close-modal" onclick="closeFeature('announcementModal')">&times;</span>
            <h3>📢 Post Announcement</h3>
            <?php if (isset($_GET['announcement_posted'])): ?>
                <div style="background:#d1fae5; color:#065f46; padding:12px; border-radius:8px; margin-bottom:15px; border-left:4px solid #10b981; font-weight:500;">
                    ✅ Announcement posted successfully!
                </div>
            <?php endif; ?>
            <form method="POST" class="profile-update-form">
                <input type="hidden" name="action" value="create_announcement" />
                <label>Title</label>
                <input type="text" name="title" placeholder="Announcement title" required>
                <label>Message</label>
                <textarea name="message" rows="5" placeholder="Write your announcement..." required style="width:100%; padding:12px; border:1px solid #ddd; border-radius:8px; font-family:inherit;"></textarea>
                <button type="submit" style="margin-top:15px;">📢 Post Announcement</button>
            </form>
            
            <h4 style="margin-top:30px; margin-bottom:15px; color:#374151;">📋 Past Announcements</h4>
            <?php
            $pastAnnouncements = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 20");
            ?>
            <div style="max-height:300px; overflow:auto; border:1px solid #e5e7eb; border-radius:8px;">
                <?php if ($pastAnnouncements && $pastAnnouncements->num_rows > 0): ?>
                    <?php while ($a = $pastAnnouncements->fetch_assoc()): ?>
                        <div style="padding:15px; border-bottom:1px solid #e5e7eb; background:#f9fafb;">
                            <div style="font-weight:600; color:#1f2937; margin-bottom:5px;"><?php echo htmlspecialchars($a['title']); ?></div>
                            <div style="color:#6b7280; font-size:14px;"><?php echo htmlspecialchars($a['message']); ?></div>
                            <div style="color:#9ca3af; font-size:12px; margin-top:8px;"><?php echo date('M j, Y g:i A', strtotime($a['created_at'])); ?></div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="padding:20px; text-align:center; color:#6b7280;">No announcements yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="admin-modal" id="feedbackModal" style="display:none;">
        <div class="admin-modal-content" style="max-width:1000px; width:95%;">
            <span class="close-modal" onclick="closeFeature('feedbackModal')">&times;</span>
            <h3>💬 Student Feedback Reports</h3>
            
            <?php
            $feedbackSearch = isset($_GET['feedback_search']) ? trim($_GET['feedback_search']) : '';
            $filterRating = isset($_GET['filter_rating']) ? intval($_GET['filter_rating']) : 0;
            
            $feedbackWhere = "1=1";
            $params = [];
            $types = "";
            
            if ($feedbackSearch !== '') {
                $feedbackWhere .= " AND (f.id_number LIKE ? OR f.comment LIKE ?)";
                $like = "%{$feedbackSearch}%";
                $params[] = $like;
                $params[] = $like;
                $types .= "ss";
            }
            if ($filterRating > 0) {
                $feedbackWhere .= " AND f.rating >= ?";
                $params[] = $filterRating;
                $types .= "i";
            }
            
            $feedbackCountQuery = "SELECT COUNT(*) as total, AVG(f.rating) as avg_rating, SUM(f.rating) as sum_rating FROM feedback f WHERE $feedbackWhere";
            if ($types) {
                $stmt = $conn->prepare($feedbackCountQuery);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $countResult = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            } else {
                $countResult = $conn->query($feedbackCountQuery)->fetch_assoc();
            }
            
            $feedbackQuery = "SELECT f.*, sh.student_name, sh.lab, sh.purpose 
                FROM feedback f 
                LEFT JOIN sitin_history sh ON f.history_id = sh.id
                WHERE $feedbackWhere 
                ORDER BY f.id DESC 
                LIMIT 100";
            if ($types) {
                $stmt = $conn->prepare($feedbackQuery);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $feedbackResult = $stmt->get_result();
                $stmt->close();
            } else {
                $feedbackResult = $conn->query($feedbackQuery);
            }
            ?>
            
            <form method="GET" class="search-form" style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:20px;">
                <input type="text" name="feedback_search" placeholder="Search by ID or comment" value="<?php echo htmlspecialchars($feedbackSearch); ?>">
                <select name="filter_rating" style="padding:10px; border:1px solid #ddd; border-radius:6px;">
                    <option value="0" <?php if ($filterRating === 0) echo 'selected'; ?>>All Ratings</option>
                    <option value="3" <?php if ($filterRating === 3) echo 'selected'; ?>>3+ Stars</option>
                    <option value="4" <?php if ($filterRating === 4) echo 'selected'; ?>>4+ Stars</option>
                    <option value="5" <?php if ($filterRating === 5) echo 'selected'; ?>>5 Stars</option>
                </select>
                <button type="submit">Filter</button>
            </form>
            
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:15px; margin-bottom:20px;">
                <div style="background:linear-gradient(135deg, #6366f1, #4f46e5); color:white; padding:20px; border-radius:12px; text-align:center;">
                    <div style="font-size:28px; font-weight:700;"><?php echo $countResult['total']; ?></div>
                    <div style="font-size:14px; opacity:0.9;">Total Feedback</div>
                </div>
                <div style="background:linear-gradient(135deg, #f59e0b, #d97706); color:white; padding:20px; border-radius:12px; text-align:center;">
                    <div style="font-size:28px; font-weight:700;"><?php echo $countResult['avg_rating'] ? number_format($countResult['avg_rating'], 1) : '0'; ?></div>
                    <div style="font-size:14px; opacity:0.9;">Avg Rating</div>
                </div>
                <div style="background:linear-gradient(135deg, #10b981, #059669); color:white; padding:20px; border-radius:12px; text-align:center;">
                    <div style="font-size:28px; font-weight:700;"><?php echo $countResult['sum_rating'] ?: 0; ?></div>
                    <div style="font-size:14px; opacity:0.9;">Total Stars</div>
                </div>
            </div>
            
            <div class="table-wrap" style="max-height:400px; overflow:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ID Number</th>
                            <th>Name</th>
                            <th>Lab</th>
                            <th>Purpose</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($feedbackResult && $feedbackResult->num_rows > 0): ?>
                            <?php while ($row = $feedbackResult->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['id_number']); ?></td>
                                    <td><?php echo htmlspecialchars($row['student_name'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['lab'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['purpose'] ?: 'N/A'); ?></td>
                                    <td>
                                        <?php for($r=1;$r<=5;$r++): ?>
                                            <span style="color:<?php echo $r <= $row['rating'] ? '#f59e0b' : '#ddd'; ?>;">⭐</span>
                                        <?php endfor; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['comment'] ?: '-'); ?></td>
                                    <td><?php echo $row['created_at'] ? date('M j, Y', strtotime($row['created_at'])) : '-'; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8" style="text-align:center; color:#666;">No feedback found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="admin-modal" id="reservationModal" style="display:none;">
        <div class="admin-modal-content" style="max-width:1100px; width:95%;">
            <span class="close-modal" onclick="closeFeature('reservationModal')">&times;</span>
            <h3>🖥️ Reservation Management</h3>
            
            <div style="display:flex; gap:10px; margin-bottom:20px; border-bottom:2px solid #e5e7eb; padding-bottom:10px;">
                <button type="button" onclick="showResTab('computerControl')" id="resTabComputer" style="padding:10px 20px; border:none; border-radius:8px; background:#6366f1; color:white; cursor:pointer; font-weight:600;">Computer Control</button>
                <button type="button" onclick="showResTab('reservationRequest')" id="resTabRequest" style="padding:10px 20px; border:none; border-radius:8px; background:#e5e7eb; color:#374151; cursor:pointer; font-weight:600;">Reservation Requests</button>
                <button type="button" onclick="showResTab('reservationLogs')" id="resTabLogs" style="padding:10px 20px; border:none; border-radius:8px; background:#e5e7eb; color:#374151; cursor:pointer; font-weight:600;">Logs</button>
            </div>
            
            <!-- COMPUTER CONTROL -->
            <div id="resComputerControl" style="display:block;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                    <div>
                        <h4 style="margin-bottom:15px;">➕ Add Computer</h4>
                        <form method="POST" style="background:#f9fafb; padding:20px; border-radius:12px;">
                            <input type="hidden" name="action" value="add_computer">
                            <label style="display:block; margin-bottom:5px; font-weight:600;">Lab Name</label>
                            <input type="text" name="lab_name" placeholder="e.g., Lab 101, CS Lab" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; margin-bottom:15px;">
                            <label style="display:block; margin-bottom:5px; font-weight:600;">Computer Name/Number</label>
                            <input type="text" name="computer_name" placeholder="e.g., PC-01, PC-02" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; margin-bottom:15px;">
                            <button type="submit" style="padding:10px 20px; background:#10b981; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">Add Computer</button>
                        </form>
                    </div>
                    <div>
                        <h4 style="margin-bottom:15px;">📊 Computers by Lab</h4>
                        <?php
                        $computersByLab = $conn->query("SELECT lab_name, COUNT(*) as total, SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available, SUM(CASE WHEN status = 'in_use' THEN 1 ELSE 0 END) as in_use FROM computers GROUP BY lab_name");
                        ?>
                        <div style="max-height:250px; overflow:auto;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Lab</th>
                                        <th>Total</th>
                                        <th>Available</th>
                                        <th>In Use</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($computersByLab && $computersByLab->num_rows > 0): ?>
                                        <?php while ($row = $computersByLab->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['lab_name']); ?></td>
                                                <td><?php echo $row['total']; ?></td>
                                                <td style="color:#10b981;"><?php echo $row['available']; ?></td>
                                                <td style="color:#ef4444;"><?php echo $row['in_use']; ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" style="text-align:center; color:#666;">No computers added yet.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <h4 style="margin:20px 0 15px;">🖥️ All Computers</h4>
                <?php
                $allComputers = $conn->query("SELECT * FROM computers ORDER BY lab_name, computer_name");
                ?>
                <div style="max-height:200px; overflow:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Lab</th>
                                <th>Computer</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($allComputers && $allComputers->num_rows > 0): ?>
                                <?php while ($c = $allComputers->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $c['id']; ?></td>
                                        <td><?php echo htmlspecialchars($c['lab_name']); ?></td>
                                        <td><?php echo htmlspecialchars($c['computer_name']); ?></td>
                                        <td>
                                            <?php if ($c['status'] === 'available'): ?><span style="color:#10b981;">Available</span>
                                            <?php elseif ($c['status'] === 'in_use'): ?><span style="color:#ef4444;">In Use</span>
                                            <?php else: ?><span style="color:#f59e0b;">Maintenance</span><?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="delete_computer">
                                                <input type="hidden" name="computer_id" value="<?php echo $c['id']; ?>">
                                                <button type="submit" onclick="return confirm('Delete this computer?')" style="background:#ef4444; color:white; border:none; padding:5px 10px; border-radius:4px; cursor:pointer;">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align:center; color:#666;">No computers found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- RESERVATION REQUESTS -->
            <div id="resReservationRequest" style="display:none;">
                <?php
                $pendingReservations = $conn->query("SELECT * FROM sitin_reservations WHERE status = 'pending' ORDER BY created_at DESC");
                $approvedReservations = $conn->query("SELECT * FROM sitin_reservations WHERE status = 'approved' ORDER BY created_at DESC");
                $today = date('Y-m-d');
                ?>
                <h4 style="margin-bottom:15px;">⏳ Pending Requests (<?php echo $pendingReservations ? $pendingReservations->num_rows : 0; ?>)</h4>
                <div style="max-height:200px; overflow:auto; margin-bottom:20px;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>ID Number</th>
                                <th>Name</th>
                                <th>Lab</th>
                                <th>Purpose</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($pendingReservations && $pendingReservations->num_rows > 0): ?>
                                <?php while ($r = $pendingReservations->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $r['id']; ?></td>
                                        <td><?php echo htmlspecialchars($r['id_number']); ?></td>
                                        <td><?php echo htmlspecialchars($r['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($r['lab']); ?></td>
                                        <td><?php echo htmlspecialchars($r['purpose']); ?></td>
                                        <td><?php echo $r['date'] ? date('M j, Y', strtotime($r['date'])) : '-'; ?></td>
                                        <td><?php echo $r['time_in'] ? date('g:i A', strtotime($r['time_in'])) : '-'; ?></td>
                                        <td style="display:flex; gap:5px;">
                                            <form method="POST">
                                                <input type="hidden" name="action" value="approve_sitin">
                                                <input type="hidden" name="sitin_id" value="<?php echo $r['id']; ?>">
                                                <button type="submit" style="background:#10b981; color:white; border:none; padding:5px 10px; border-radius:4px; cursor:pointer;">Approve</button>
                                            </form>
                                            <form method="POST">
                                                <input type="hidden" name="action" value="reject_sitin">
                                                <input type="hidden" name="sitin_id" value="<?php echo $r['id']; ?>">
                                                <button type="submit" style="background:#ef4444; color:white; border:none; padding:5px 10px; border-radius:4px; cursor:pointer;">Reject</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="8" style="text-align:center; color:#666;">No pending requests.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <h4 style="margin-bottom:15px;">✅ Approved / Active (<?php echo $approvedReservations ? $approvedReservations->num_rows : 0; ?>)</h4>
                <div style="max-height:200px; overflow:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>ID Number</th>
                                <th>Name</th>
                                <th>Lab</th>
                                <th>Purpose</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($approvedReservations && $approvedReservations->num_rows > 0): ?>
                                <?php while ($r = $approvedReservations->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $r['id']; ?></td>
                                        <td><?php echo htmlspecialchars($r['id_number']); ?></td>
                                        <td><?php echo htmlspecialchars($r['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($r['lab']); ?></td>
                                        <td><?php echo htmlspecialchars($r['purpose']); ?></td>
                                        <td><?php echo $r['date'] ? date('M j, Y', strtotime($r['date'])) : '-'; ?></td>
                                        <td><?php echo $r['time_in'] ? date('g:i A', strtotime($r['time_in'])) : '-'; ?></td>
                                        <td>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="logout_student_id" value="<?php echo $r['id']; ?>">
                                                <button type="submit" style="background:#6366f1; color:white; border:none; padding:5px 10px; border-radius:4px; cursor:pointer;">End</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="8" style="text-align:center; color:#666;">No approved reservations.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- RESERVATION LOGS -->
            <div id="resReservationLogs" style="display:none;">
                <?php
                $reservationLogs = $conn->query("SELECT * FROM sitin_reservations ORDER BY created_at DESC LIMIT 100");
                $pendingCount = $conn->query("SELECT COUNT(*) as cnt FROM sitin_reservations WHERE status = 'pending'")->fetch_assoc()['cnt'];
                $approvedCount = $conn->query("SELECT COUNT(*) as cnt FROM sitin_reservations WHERE status = 'approved'")->fetch_assoc()['cnt'];
                $completedCount = $conn->query("SELECT COUNT(*) as cnt FROM sitin_reservations WHERE status = 'completed'")->fetch_assoc()['cnt'];
                $rejectedCount = $conn->query("SELECT COUNT(*) as cnt FROM sitin_reservations WHERE status = 'rejected'")->fetch_assoc()['cnt'];
                ?>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(120px, 1fr)); gap:15px; margin-bottom:20px;">
                    <div style="background:#f59e0b; color:white; padding:15px; border-radius:10px; text-align:center;">
                        <div style="font-size:24px; font-weight:700;"><?php echo $pendingCount; ?></div>
                        <div style="font-size:12px;">Pending</div>
                    </div>
                    <div style="background:#10b981; color:white; padding:15px; border-radius:10px; text-align:center;">
                        <div style="font-size:24px; font-weight:700;"><?php echo $approvedCount; ?></div>
                        <div style="font-size:12px;">Approved</div>
                    </div>
                    <div style="background:#6366f1; color:white; padding:15px; border-radius:10px; text-align:center;">
                        <div style="font-size:24px; font-weight:700;"><?php echo $completedCount; ?></div>
                        <div style="font-size:12px;">Completed</div>
                    </div>
                    <div style="background:#ef4444; color:white; padding:15px; border-radius:10px; text-align:center;">
                        <div style="font-size:24px; font-weight:700;"><?php echo $rejectedCount; ?></div>
                        <div style="font-size:12px;">Rejected</div>
                    </div>
                </div>
                
                <h4 style="margin-bottom:15px;">📜 Reservation History</h4>
                <div style="max-height:350px; overflow:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>ID Number</th>
                                <th>Name</th>
                                <th>Lab</th>
                                <th>Purpose</th>
                                <th>Date</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($reservationLogs && $reservationLogs->num_rows > 0): ?>
                                <?php while ($r = $reservationLogs->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $r['id']; ?></td>
                                        <td><?php echo htmlspecialchars($r['id_number']); ?></td>
                                        <td><?php echo htmlspecialchars($r['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($r['lab']); ?></td>
                                        <td><?php echo htmlspecialchars($r['purpose']); ?></td>
                                        <td><?php echo $r['date'] ? date('M j, Y', strtotime($r['date'])) : '-'; ?></td>
                                        <td><?php echo $r['time_in'] ? date('g:i A', strtotime($r['time_in'])) : '-'; ?></td>
                                        <td><?php echo $r['time_out'] ? date('g:i A', strtotime($r['time_out'])) : '-'; ?></td>
                                        <td>
                                            <?php if ($r['status'] === 'pending'): ?><span style="color:#f59e0b; font-weight:600;">Pending</span>
                                            <?php elseif ($r['status'] === 'approved'): ?><span style="color:#10b981; font-weight:600;">Approved</span>
                                            <?php elseif ($r['status'] === 'completed'): ?><span style="color:#6366f1; font-weight:600;">Completed</span>
                                            <?php elseif ($r['status'] === 'rejected'): ?><span style="color:#ef4444; font-weight:600;">Rejected</span>
                                            <?php else: ?><span style="color:#6b7280;"><?php echo ucfirst($r['status']); ?></span><?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="9" style="text-align:center; color:#666;">No reservation logs found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-modal" id="profileModal" style="display:none;">
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

    <!-- Edit Student Modal -->
    <div class="admin-modal" id="editStudentModal" style="display:none;">
        <div class="admin-modal-content" style="max-width:500px; width:95%;">
            <span class="close-modal" onclick="closeFeature('editStudentModal')">&times;</span>
            <h3>✏️ Edit Student</h3>
            <form method="POST" class="profile-update-form">
                <input type="hidden" name="action" value="update_student" />
                <input type="hidden" name="original_id_number" id="edit_original_id_number" />
                <input type="hidden" name="id_number" id="edit_id_number" />
                <label>ID Number</label>
                <input type="text" id="edit_id_number_display" onchange="document.getElementById('edit_id_number').value = this.value" />
                <label>Last Name</label>
                <input type="text" name="last_name" id="edit_last_name" required>
                <label>First Name</label>
                <input type="text" name="first_name" id="edit_first_name" required>
                <label>Middle Name</label>
                <input type="text" name="middle_name" id="edit_middle_name">
                <label>Course</label>
                <input type="text" name="course" id="edit_course" required>
                <label>Year Level</label>
                <input type="number" name="year_level" id="edit_year_level" required>
                <label>Email</label>
                <input type="email" name="email" id="edit_email" required>
                <label>Address</label>
                <input type="text" name="address" id="edit_address">
                <button type="submit" style="margin-top:15px;">💾 Save Changes</button>
            </form>
        </div>
    </div>



    <script>
    // ✅ FIXED JavaScript Functions
    function openFeature(modalId) {
        // Close ALL modals first
        document.querySelectorAll('.admin-modal').forEach(function(modal) {
            modal.style.display = 'none';
        });
        
        // Open the requested modal
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    }

    function closeSitInForm() {
        document.getElementById('sitInFormModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function closeFeature(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }

    function showResTab(tabName) {
        document.getElementById('resComputerControl').style.display = 'none';
        document.getElementById('resReservationRequest').style.display = 'none';
        document.getElementById('resReservationLogs').style.display = 'none';
        
        document.getElementById('resTabComputer').style.background = '#e5e7eb';
        document.getElementById('resTabComputer').style.color = '#374151';
        document.getElementById('resTabRequest').style.background = '#e5e7eb';
        document.getElementById('resTabRequest').style.color = '#374151';
        document.getElementById('resTabLogs').style.background = '#e5e7eb';
        document.getElementById('resTabLogs').style.color = '#374151';
        
        if (tabName === 'computerControl') {
            document.getElementById('resComputerControl').style.display = 'block';
            document.getElementById('resTabComputer').style.background = '#6366f1';
            document.getElementById('resTabComputer').style.color = 'white';
        } else if (tabName === 'reservationRequest') {
            document.getElementById('resReservationRequest').style.display = 'block';
            document.getElementById('resTabRequest').style.background = '#6366f1';
            document.getElementById('resTabRequest').style.color = 'white';
        } else if (tabName === 'reservationLogs') {
            document.getElementById('resReservationLogs').style.display = 'block';
            document.getElementById('resTabLogs').style.background = '#6366f1';
            document.getElementById('resTabLogs').style.color = 'white';
        }
    }

    function openEditStudentModal(id, lastName, firstName, middleName, course, yearLevel, email, address) {
        document.getElementById('edit_original_id_number').value = id;
        document.getElementById('edit_id_number').value = id;
        document.getElementById('edit_id_number_display').value = id;
        document.getElementById('edit_last_name').value = lastName;
        document.getElementById('edit_first_name').value = firstName;
        document.getElementById('edit_middle_name').value = middleName;
        document.getElementById('edit_course').value = course;
        document.getElementById('edit_year_level').value = yearLevel;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_address').value = address;
        openFeature('editStudentModal');
    }

    function confirmDeleteStudent(id) {
        if (confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="action" value="delete_student" /><input type="hidden" name="id_number" value="' + id + '" />';
            document.body.appendChild(form);
            form.submit();
        }
    }

    function exportReport(format) {
        const urlParams = new URLSearchParams(window.location.search);
        const dateFrom = urlParams.get('date_from') || '<?php echo date('Y-m-01'); ?>';
        const dateTo = urlParams.get('date_to') || '<?php echo date('Y-m-t'); ?>';
        
        let exportUrl = 'admin_dashboard.php?export=' + format + '&date_from=' + dateFrom + '&date_to=' + dateTo;
        
        window.open(exportUrl, '_blank');
    }

    function exportRecords(format) {
        let exportUrl = 'admin_dashboard.php?export=' + format;
        window.open(exportUrl, '_blank');
    }

    // Auto-open search modal if search exists
    <?php if (!empty($search)) : ?>
    document.addEventListener('DOMContentLoaded', function() {
        openFeature('searchModal');
    });
    <?php endif; ?>
    </script>

    <script src="js/script.js"></script> 




    </body>
    </html>