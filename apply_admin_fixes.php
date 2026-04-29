<?php
// Fix admin_dashboard.php - Add photo support for student management

$file = 'admin_dashboard.php';
$content = file_get_contents($file);

// Fix 1: Add photo column to student search query (line 398 area)
$content = preg_replace(
    '/\$stmt = \$conn->prepare\("SELECT id_number, last_name, first_name, middle_name, course, year_level, email, COALESCE\(remaining_sessions, 30\) as remaining_sessions FROM students WHERE id_number LIKE \? OR last_name LIKE \? OR first_name LIKE \?"\);/',
    '$stmt = $conn->prepare("SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address, photo, COALESCE(remaining_sessions, 30) as remaining_sessions FROM students WHERE id_number LIKE ? OR last_name LIKE ? OR first_name LIKE ?");',
    $content
);

// Fix 2: Add photo column to student listing query (line 404 area)
$content = preg_replace(
    '/\$result = \$conn->query\("SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address FROM students ORDER BY last_name, first_name"\);/',
    '$result = $conn->query("SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address, photo FROM students ORDER BY last_name, first_name");',
    $content
);

// Fix 3: Add photo column to search modal query (line 672 area)
$content = preg_replace(
    '/\$stmt = \$conn->prepare\("SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address, COALESCE\(remaining_sessions, 30\) as remaining_sessions FROM students WHERE id_number LIKE \? OR last_name LIKE \? OR first_name LIKE \?"\);/',
    '$stmt = $conn->prepare("SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address, photo, COALESCE(remaining_sessions, 30) as remaining_sessions FROM students WHERE id_number LIKE ? OR last_name LIKE ? OR first_name LIKE ?");',
    $content
);

// Fix 4: Add photo column to search modal else query (line 678 area)
$content = preg_replace(
    '/\$searchResult = \$conn->query\("SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address, COALESCE\(remaining_sessions, 30\) as remaining_sessions FROM students ORDER BY last_name, first_name LIMIT 50"\);/',
    '$searchResult = $conn->query("SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address, photo, COALESCE(remaining_sessions, 30) as remaining_sessions FROM students ORDER BY last_name, first_name LIMIT 50");',
    $content
);

// Fix 5: Update openEditStudentModal to accept photo parameter and add photo to form
$content = preg_replace(
    '/function openEditStudentModal\(id, lastName, firstName, middleName, course, yearLevel, email, address\) \{/',
    'function openEditStudentModal(id, lastName, firstName, middleName, course, yearLevel, email, address, photo) {',
    $content
);

// Fix 6: Update edit button onclick to pass photo parameter
$content = preg_replace(
    "/onclick=\"openEditStudentModal\('" . preg_quote("\\', '", "/") . "[^']*'\"\)/",
    "onclick=\"openEditStudentModal('" . addcslashes("' + addslashes($student['id_number']) . '","')
)", 
    $content
);

// Fix 7: Add hidden field for existing photo in update form
$content = preg_replace(
    '/<input type="hidden" name="original_id_number" id="edit_original_id_number" \/>/',
    '<input type="hidden" name="original_id_number" id="edit_original_id_number" />
                <input type="hidden" name="id_number" id="edit_id_number" />',
    $content
);

// Fix 8: Update UPDATE statement to preserve photo when not changing it
$content = preg_replace(
    '/\$updateStmt = \$conn->prepare\("UPDATE students SET id_number=\?\\, last_name=\?\\, first_name=\?\\, middle_name=\?\\, course=\\?\\, year_level=\?\\, email=\?\\, address=\\? WHERE id_number=\?"\);/',
    '$updateStmt = $conn->prepare("UPDATE students SET id_number=?, last_name=?, first_name=?, middle_name=?, course=?, year_level=?, email=?, address=? WHERE id_number=?");',
    $content
);

file_put_contents($file, $content);
echo "Fixes applied successfully!\n";
?>