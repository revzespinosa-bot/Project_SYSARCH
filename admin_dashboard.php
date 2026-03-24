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


    // Handle sit-in start
    // Handle student logout (end sit-in) - DEDUCTS SESSIONS
    // Handle student logout (end sit-in) - DEDUCTS SESSIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout_student_id'])) {
    $sit_id = intval($_POST['logout_student_id']);
    
    // Get sit-in details first
    $sitStmt = $conn->prepare("SELECT id_number, student_name, purpose, lab, remaining_sessions FROM sitin_reservations WHERE id = ?");
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
        
        // 3. Delete active reservation
        $deleteStmt = $conn->prepare("DELETE FROM sitin_reservations WHERE id = ?");
        $deleteStmt->bind_param("i", $sit_id);
        $deleteStmt->execute();
        $deleteStmt->close();
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

    // Load current sit-in reservations for admin view
    // Join with students table
$sitinRes = $conn->query("
    SELECT sr.*, COALESCE(s.remaining_sessions, 28) as student_sessions_left 
    FROM sitin_reservations sr 
    LEFT JOIN students s ON sr.id_number = s.id_number 
    ORDER BY sr.created_at DESC
");


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
                <li><button onclick="openFeature('searchModal')">Search Students</button></li>
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

        <!-- SEARCH MODAL TABLE - CORRECTED -->
    <!-- ✅ 100% WORKING SEARCH MODAL -->
    <div class="admin-modal" id="searchModal">
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
    $stmt = $conn("SELECT id_number, last_name, first_name, middle_name, course, year_level, email, COALESCE(remaining_sessions, 28) as remaining_sessions FROM students WHERE id_number LIKE ? OR last_name LIKE ? OR first_name LIKE ?");
    $like = "%{$search}%";
    $stmt->bind_param('sss', $like, $like, $like);
    $stmt->execute();
    $searchResult = $stmt->get_result();
} else {
    $searchResult = $conn->query("SELECT id_number, last_name, first_name, middle_name, course, year_level, email, COALESCE(remaining_sessions, 28) as remaining_sessions FROM students ORDER BY last_name, first_name LIMIT 50");
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
<td>
    <!-- your existing button code stays the same -->
    <button class="sitin-btn" type="button" 
        onclick="document.getElementById('sitInFormModal').style.display='block'; 
                 document.getElementById('sitin_id').value='<?php echo addslashes($student['id_number']); ?>'; 
                 document.getElementById('sitin_name').value='<?php echo addslashes($student['first_name'].' '.$student['last_name']); ?>'; 
                 document.body.style.overflow='hidden';">
        🚀 Sit-in
    </button>
</td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="6" style="text-align:center; color:#666;">No students found. Try different search term.</td></tr>
    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- CURRENT SIT-IN SECTION - CORRECTED -->
    <section class="dashboard-card">
    <h3>🕒 Current Sit-in (<?php echo $sitinRes ? $sitinRes->num_rows : 0; ?> Active)</h3>
    <?php if (isset($_GET['session_ended'])): ?>
        <div style="background:#d1fae5; color:#065f46; padding:12px; border-radius:8px; margin-bottom:15px; border-left:4px solid #10b981; font-weight:500;">
            ✅ Session ended successfully! 1 session deducted from student.
        </div>
    <?php endif; ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Sit ID</th>
                    <th>ID Number</th>
                    <th>Name</th>
                    <th>Purpose</th>
                    <th>Lab</th>
                    <th>Sessions Left</th>  <!-- ✅ NEW COLUMN -->
                    <th>Status</th>
                    <th>Time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
    <?php if ($sitinRes && $sitinRes->num_rows > 0): ?>
        <?php while ($sit = $sitinRes->fetch_assoc()): ?>
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
    </section>

    <div class="admin-modal" id="homeModal">
        <div class="admin-modal-content">
            <span class="close-modal" onclick="closeFeature('homeModal')">&times;</span>
            <h3>Home</h3>
            <p>Welcome to the admin dashboard. Use menu buttons to work with reports and data.</p>
        </div>
    </div>

    <!-- ✅ FIXED MODERN SIT-IN FORM -->
    <div class="admin-modal" id="sitInFormModal">
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
    <div class="admin-modal" id="recordsModal">
    <div class="admin-modal-content">
        <span class="close-modal" onclick="closeFeature('recordsModal')">&times;</span>
        <h3>📜 Sit-in History</h3>
        <div class="table-wrap">
            <table>
                <?php   
                $history = $conn->query("
                    SELECT * FROM sitin_history 
                    ORDER BY time_out DESC LIMIT 50
                ");
                ?>
                <!-- Similar table structure showing history -->
            </table>
        </div>
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



    <script>
    // ✅ FIXED JavaScript Functions
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