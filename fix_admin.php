<?php
/**
 * Fix admin_dashboard.php to properly handle student photos
 * Changes:
 * 1. Add photo column to student search queries
 * 2. Update edit student modal to include photo upload
 * 3. Update openEditStudentModal JS function to handle photo preview
 */

$file = 'admin_dashboard.php';
$content = file_get_contents($file);

// ============================================================
// FIX 1: Add photo column to main student search (around line 398)
// ============================================================
$content = str_replace(
    '$stmt = $conn->prepare("SELECT id_number, last_name, first_name, middle_name, course, year_level, email, COALESCE(remaining_sessions, 30) as remaining_sessions FROM students WHERE id_number LIKE ? OR last_name LIKE ? OR first_name LIKE ?");',
    '$stmt = $conn->prepare("SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address, photo, COALESCE(remaining_sessions, 30) as remaining_sessions FROM students WHERE id_number LIKE ? OR last_name LIKE ? OR first_name LIKE ?");',
    $content
);

// ============================================================
// FIX 2: Add photo column to main student listing (around line 404)
// ============================================================
$content = str_replace(
    '$result = $conn->query("SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address FROM students ORDER BY last_name, first_name");',
    '$result = $conn->query("SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address, photo FROM students ORDER BY last_name, first_name");',
    $content
);

// ============================================================
// FIX 3: Add photo column to search modal query (around line 569)
// ============================================================
$content = str_replace(
    '$stmt = $conn->prepare("SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address, COALESCE(remaining_sessions, 30) as remaining_sessions FROM students WHERE id_number LIKE ? OR last_name LIKE ? OR first_name LIKE ?");',
    '$stmt = $conn->prepare("SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address, photo, COALESCE(remaining_sessions, 30) as remaining_sessions FROM students WHERE id_number LIKE ? OR last_name LIKE ? OR first_name LIKE ?");',
    $content
);

// ============================================================
// FIX 4: Add photo column to search modal else query (around line 575)
// ============================================================
$content = str_replace(
    '$searchResult = $conn->query("SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address, COALESCE(remaining_sessions, 30) as remaining_sessions FROM students ORDER BY last_name, first_name LIMIT 50");',
    '$searchResult = $conn->query("SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address, photo, COALESCE(remaining_sessions, 30) as remaining_sessions FROM students ORDER BY last_name, first_name LIMIT 50");',
    $content
);

// ============================================================
// FIX 5: Update openEditStudentModal function to accept photo param
// ============================================================
$content = str_replace(
    'function openEditStudentModal(id, lastName, firstName, middleName, course, yearLevel, email, address) {',
    'function openEditStudentModal(id, lastName, firstName, middleName, course, yearLevel, email, address, photo) {',
    $content
);

// ============================================================
// FIX 6: Add photo preview setup in openEditStudentModal
// ============================================================
$jsAddition = <<<'JSEOF'
        document.getElementById('edit_existing_photo').value = photo || '';
        
        // Set photo preview
        const photoPreview = document.getElementById('edit_photo_preview');
        const removeBtn = document.getElementById('edit_remove_photo_btn');
        if (photo && photo.trim() !== '') {
            photoPreview.src = 'uploads/' + photo + '?' + new Date().getTime();
            removeBtn.style.display = 'inline-block';
        } else {
            photoPreview.src = 'https://via.placeholder.com/120?text=No+Photo';
            removeBtn.style.display = 'none';
        }
JSEOF;

$content = str_replace(
    '        document.getElementById(\'edit_address\').value = address;',
    '        document.getElementById(\'edit_address\').value = address;' . "\n" . $jsAddition,
    $content
);

file_put_contents($file, $content);
echo "Applied fixes to admin_dashboard.php\n";
?>