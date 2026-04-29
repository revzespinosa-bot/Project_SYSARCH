<?php
// Complete fix for admin_dashboard.php
$file = 'admin_dashboard.php';
$content = file_get_contents($file);

// ============================================================
// Apply all fixes using str_replace which is safer
// ============================================================

// Fix 1: Main student search - add photo and address
$content = str_replace(
    'SELECT id_number, last_name, first_name, middle_name, course, year_level, email, COALESCE',
    'SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address, photo, COALESCE',
    $content
);

// Fix 2: Main student listing - add photo
$content = str_replace(
    'SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address FROM students ORDER BY last_name, first_name',
    'SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address, photo FROM students ORDER BY last_name, first_name',
    $content
);

// Fix 3: Search modal query - add photo
$content = str_replace(
    'SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address, COALESCE',
    'SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address, photo, COALESCE',
    $content
);

// Fix 4: Search modal else query - add photo  
$old_else = 'SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address, COALESCE(remaining_sessions, 30) as remaining_sessions FROM students ORDER BY last_name, first_name LIMIT 50';
$new_else = 'SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address, photo, COALESCE(remaining_sessions, 30) as remaining_sessions FROM students ORDER BY last_name, first_name LIMIT 50';
$content = str_replace($old_else, $new_else, $content);

// Fix 5: Update openEditStudentModal signature
$content = str_replace(
    'function openEditStudentModal(id, lastName, firstName, middleName, course, yearLevel, email, address) {',
    'function openEditStudentModal(id, lastName, firstName, middleName, course, yearLevel, email, address, photo) {',
    $content
);

// Fix 6: Add photo handling after address line in openEditStudentModal
$photo_js = '        document.getElementById(\'edit_existing_photo\').value = photo || \'\';
        
        // Set photo preview
        const photoPreview = document.getElementById(\'edit_photo_preview\');
        const removeBtn = document.getElementById(\'edit_remove_photo_btn\');
        if (photo && photo.trim() !== \'\') {
            photoPreview.src = \'uploads/\' + photo + \'?\' + new Date().getTime();
            removeBtn.style.display = \'inline-block\';
        } else {
            photoPreview.src = \'https://via.placeholder.com/120?text=No+Photo\';
            removeBtn.style.display = \'none\';
        }';

$content = str_replace(
    "        document.getElementById('edit_address').value = address;\n        openFeature('editStudentModal');",
    "        document.getElementById('edit_address').value = address;\n" . $photo_js . "\n        openFeature('editStudentModal');",
    $content
);

// Fix 7: Update the edit modal form to include photo upload
$old_modal_form = '<form method="POST" class="profile-update-form">
                <input type="hidden" name="action" value="update_student" />
                <input type="hidden" name="original_id_number" id="edit_original_id_number" />
                <input type="hidden" name="id_number" id="edit_id_number" />';

$new_modal_form = '<form method="POST" class="profile-update-form" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_student" />
                <input type="hidden" name="original_id_number" id="edit_original_id_number" />
                <input type="hidden" name="id_number" id="edit_id_number" />
                <input type="hidden" name="existing_photo" id="edit_existing_photo" value="" />
                <div style="display:flex; justify-content:center; margin-bottom:20px;">
                    <div style="text-align:center;">
                        <img id="edit_photo_preview" src="https://via.placeholder.com/120?text=No+Photo" alt="Student Photo" style="width:120px; height:120px; border-radius:50%; object-fit:cover; border:3px solid #e5e7eb; margin-bottom:10px;">
                        <div style="margin-top:5px;">
                            <button type="button" onclick="document.getElementById(\'edit_photo\').click()" style="background:#3b82f6; color:white; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; font-size:12px;">📷 Change Photo</button>
                            <input type="file" name="photo" id="edit_photo" style="display:none;" accept="image/*" onchange="previewEditPhoto(this)">
                            <button type="button" onclick="removeEditPhoto()" id="edit_remove_photo_btn" style="background:#ef4444; color:white; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; font-size:12px; display:none; margin-left:5px;">Remove</button>
                        </div>
                    </div>
                </div>';

$content = str_replace($old_modal_form, $new_modal_form, $content);

// Fix 8: Update the edit button in search modal to pass photo parameter
// This is a bit complex - let me find the exact pattern
$edit_btn_pattern = 'onclick="openEditStudentModal(';
$pos = strpos($content, $edit_btn_pattern);
if ($pos !== false) {
    // Find all occurrences and update them
    $content = preg_replace_callback(
        '/onclick="openEditStudentModal\(\'([^\']+)\', \'([^\']+)\', \'([^\']+)\', \'([^\']+)\', \'([^\']+)\', \'([^\']+)\', \'([^\']+)\', \'([^\']+)\'\)"/',
        function($matches) {
            return 'onclick="openEditStudentModal(\'' . $matches[1] . '\', \'' . $matches[2] . '\', \'' . $matches[3] . '\', \'' . $matches[4] . '\', \'' . $matches[5] . '\', \'' . $matches[6] . '\', \'' . $matches[7] . '\', \'' . $matches[8] . '\', \'\')"';
        },
        $content
    );
}

// Fix 9: Add JavaScript helper functions for photo preview
$js_helpers = '    <script>
    function previewEditPhoto(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById(\'edit_photo_preview\').src = e.target.result;
                document.getElementById(\'edit_remove_photo_btn\').style.display = \'inline-block\';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    function removeEditPhoto() {
        document.getElementById(\'edit_photo\').value = \'\';
        document.getElementById(\'edit_existing_photo\').value = \'\';
        document.getElementById(\'edit_photo_preview\').src = \'https://via.placeholder.com/120?text=No+Photo\';
        document.getElementById(\'edit_remove_photo_btn\').style.display = \'none\';
    }';

// Insert before the existing script closing if not already there
if (strpos($content, 'function previewEditPhoto') === false) {
    $content = str_replace(
        '    <script>',
        $js_helpers,
        $content
    );
}

file_put_contents($file, $content);
echo "Admin dashboard fixed successfully!\n";
?>