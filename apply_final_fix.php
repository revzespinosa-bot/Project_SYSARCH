<?php
// Complete fix for admin_dashboard.php
ob_start();
include 'admin_dashboard.php.backup';
$content = ob_get_clean();

// Or read it directly
$content = file_get_contents('admin_dashboard.php.backup');

// Apply fixes
$fixes = [
    // Fix 1: Main student search
    'SELECT id_number, last_name, first_name, middle_name, course, year_level, email, COALESCE' => 
    'SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address, photo, COALESCE',
    
    // Fix 2: Main student listing
    'SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address FROM students ORDER BY last_name, first_name' => 
    'SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address, photo FROM students ORDER BY last_name, first_name',
    
    // Fix 3 & 4: Search modal queries (both need photo)
    'SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address, COALESCE' => 
    'SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address, photo, COALESCE',
];

foreach ($fixes as $old => $new) {
    $content = str_replace($old, $new, $content);
}

// Fix 5: Update openEditStudentModal signature
$content = str_replace(
    'function openEditStudentModal(id, lastName, firstName, middleName, course, yearLevel, email, address) {',
    'function openEditStudentModal(id, lastName, firstName, middleName, course, yearLevel, email, address, photo) {',
    $content
);

// Fix 6: Add photo JS after address line
$photo_handling = '        document.getElementById(\'edit_address\').value = address;' . "\n" .
'        document.getElementById(\'edit_existing_photo\').value = photo || \'\';' . "\n" .
'        ' . "\n" .
'        // Set photo preview' . "\n" .
'        const photoPreview = document.getElementById(\'edit_photo_preview\');' . "\n" .
'        const removeBtn = document.getElementById(\'edit_remove_photo_btn\');' . "\n" .
'        if (photo && photo.trim() !== \'\') {' . "\n" .
'            photoPreview.src = \'uploads/\' + photo + \'?\' + new Date().getTime();' . "\n" .
'            removeBtn.style.display = \'inline-block\';' . "\n" .
'        } else {' . "\n" .
'            photoPreview.src = \'https://via.placeholder.com/120?text=No+Photo\';' . "\n" .
'            removeBtn.style.display = \'none\';' . "\n" .
'        }' . "\n" .
'        ';

$content = str_replace(
    "        document.getElementById('edit_address').value = address;\n        openFeature('editStudentModal');",
    $photo_handling . "        openFeature('editStudentModal');",
    $content
);

// Fix 7: Update edit student modal form
$old_form = '<form method="POST" class="profile-update-form">' . "\n" .
                '<input type="hidden" name="action" value="update_student" />' . "\n" .
                '<input type="hidden" name="original_id_number" id="edit_original_id_number" />' . "\n" .
                '<input type="hidden" name="id_number" id="edit_id_number" />';

$new_form = '<form method="POST" class="profile-update-form" enctype="multipart/form-data">' . "\n" .
                '<input type="hidden" name="action" value="update_student" />' . "\n" .
                '<input type="hidden" name="original_id_number" id="edit_original_id_number" />' . "\n" .
                '<input type="hidden" name="id_number" id="edit_id_number" />' . "\n" .
                '<input type="hidden" name="existing_photo" id="edit_existing_photo" value="" />' . "\n" .
                '<div style="display:flex; justify-content:center; margin-bottom:20px;">' . "\n" .
                    '<div style="text-align:center;">' . "\n" .
                        '<img id="edit_photo_preview" src="https://via.placeholder.com/120?text=No+Photo" alt="Student Photo" style="width:120px; height:120px; border-radius:50%; object-fit:cover; border:3px solid #e5e7eb; margin-bottom:10px;">' . "\n" .
                        '<div style="margin-top:5px;">' . "\n" .
                            '<button type="button" onclick="document.getElementById(\'edit_photo\').click()" style="background:#3b82f6; color:white; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; font-size:12px;">📷 Change Photo</button>' . "\n" .
                            '<input type="file" name="photo" id="edit_photo" style="display:none;" accept="image/*" onchange="previewEditPhoto(this)">' . "\n" .
                            '<button type="button" onclick="removeEditPhoto()" id="edit_remove_photo_btn" style="background:#ef4444; color:white; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; font-size:12px; display:none; margin-left:5px;">Remove</button>' . "\n" .
                        '</div>' . "\n" .
                    '</div>' . "\n" .
                '</div>';

$content = str_replace($old_form, $new_form, $content);

// Fix 8: Update edit button to pass photo parameter - more careful
$edit_pattern = '/onclick="openEditStudentModal\(\'([^\']+)\', \'([^\']+)\', \'([^\']+)\', \'([^\']+)\', \'([^\']+)\', \'([^\']+)\', \'([^\']+)\', \'([^\']+)\'\)"/';

$content = preg_replace_callback($edit_pattern, function($matches) {
    return 'onclick="openEditStudentModal(\'' . $matches[1] . '\', \'' . $matches[2] . '\', \'' . $matches[3] . '\', \'' . $matches[4] . '\', \'' . $matches[5] . '\', \'' . $matches[6] . '\', \'' . $matches[7] . '\', \'' . $matches[8] . '\', \'\')"';
}, $content);

// Fix 9: Add JS helper functions
$js_funcs = '<script>' . "\n" .
'    function previewEditPhoto(input) {' . "\n" .
'        if (input.files && input.files[0]) {' . "\n" .
'            const reader = new FileReader();' . "\n" .
'            reader.onload = function(e) {' . "\n" .
'                document.getElementById(\'edit_photo_preview\').src = e.target.result;' . "\n" .
'                document.getElementById(\'edit_remove_photo_btn\').style.display = \'inline-block\';' . "\n" .
'            }' . "\n" .
'            reader.readAsDataURL(input.files[0]);' . "\n" .
'        }' . "\n" .
'    }' . "\n" .
'    ' . "\n" .
'    function removeEditPhoto() {' . "\n" .
'        document.getElementById(\'edit_photo\').value = \'\';' . "\n" .
'        document.getElementById(\'edit_existing_photo\').value = \'\';' . "\n" .
'        document.getElementById(\'edit_photo_preview\').src = \'https://via.placeholder.com/120?text=No+Photo\';' . "\n" .
'        document.getElementById(\'edit_remove_photo_btn\').style.display = \'none\';' . "\n" .
'    }';

if (strpos($content, 'function previewEditPhoto') === false) {
    $content = str_replace('<script>', $js_funcs, $content);
}

file_put_contents('admin_dashboard.php', $content);
echo "Fixes applied successfully!\n";
?>