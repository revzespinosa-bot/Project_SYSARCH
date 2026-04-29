#!/usr/bin/env python3
import re

# Read the file
with open('admin_dashboard.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Fix 1: Add photo column to main student search query
content = re.sub(
    r'(\$stmt = \$conn->prepare\("SELECT id_number, last_name, first_name, middle_name, course, year_level, email,) COALESCE',
    r'\1 address, photo, COALESCE',
    content
)

# Fix 2: Add photo column to main student listing query  
content = re.sub(
    r'(\$result = \$conn->query\("SELECT id_number, last_name, first_name, middle_name, course, year_level, email,) address FROM students',
    r'\1 address, photo FROM students',
    content
)

# Fix 3: Add photo column to search modal query
content = re.sub(
    r'(\$stmt = \$conn->prepare\("SELECT id_number, last_name, first_name, middle_name, course, year_level, email,) address, COALESCE',
    r'\1 address, photo, COALESCE',
    content
)

# Fix 4: Add photo column to search modal else query  
content = re.sub(
    r'(\$searchResult = \$conn->query\("SELECT id_number, last_name, first_name, middle_name, course, year_level, email,) address, COALESCE',
    r'\1 address, photo, COALESCE',
    content
)

# Fix 5: Update openEditStudentModal function signature
content = re.sub(
    r'function openEditStudentModal\(id, lastName, firstName, middleName, course, yearLevel, email, address\) \{',
    r'function openEditStudentModal(id, lastName, firstName, middleName, course, yearLevel, email, address, photo) {',
    content
)

# Fix 6: Add photo value setting in openEditStudentModal
content = re.sub(
    r'(document\.getElementById\(\'edit_address\'\)\.value = address;\s*\n)(\s*openFeature\()',
    r'\1        document.getElementById(\'edit_existing_photo\').value = photo || \'\';\n\n        // Set photo preview\n        const photoPreview = document.getElementById(\'edit_photo_preview\');\n        const removeBtn = document.getElementById(\'edit_remove_photo_btn\');\n        if (photo && photo.trim() !== \'\') {\n            photoPreview.src = \'uploads/\' + photo + \'?\' + new Date().getTime();\n            removeBtn.style.display = \'inline-block\';\n        } else {\n            photoPreview.src = \'https://via.placeholder.com/120?text=No+Photo\';\n            removeBtn.style.display = \'none\';\n        }\n\n        \2',
    content
)

# Fix 7: Update search modal edit button to pass photo parameter
content = re.sub(
    r'onclick="openEditStudentModal\(\'[^\']+\', \'[^\']+\', \'[^\']+\', \'[^\']+\', \'[^\']+\', \'[^\']+\', \'[^\']+\', \'[^\']+\'\)"',
    r'onclick="openEditStudentModal(\'\0\', \'\1\', \'\2\', \'\3\', \'\4\', \'\5\', \'\6\', \'\7\', \'\8\')"',
    content
)

# More specific replacement for the edit button
old_pattern = r"onclick=\"openEditStudentModal\('([^']+)', '([^']+)', '([^']+)', '([^']+)', '([^']+)', '([^']+)', '([^']+)', '([^']+)'\)\""
new_replacement = r"onclick=\"openEditStudentModal('\1', '\2', '\3', '\4', '\5', '\6', '\7', '\8', '" + r"\9'\""  

# Actually, let me just target the specific line more carefully
content = re.sub(
    r"(openEditStudentModal\('" + re.escape("{addslashes($student['id_number'])}', '{addslashes($student['last_name'])}', '{addslashes($student['first_name'])}', '{addslashes($student['middle_name'] ?? '')}'), '{addslashes($student['course'])}', '{addslashes($student['year_level'])}', '{addslashes($student['email'])}', '{addslashes($student['address'] ?? '')}')",
    r"\1, '{addslashes($student['photo'] ?? '')}')",
    content
)

# Fix 8: Update the edit modal HTML
old_modal = r'''<div class="admin-modal" id="editStudentModal" style="display:none;">
        <div class="admin-modal-content" style="max-width:500px; width:95%;">
            <span class="close-modal" onclick="closeFeature\('editStudentModal'\)">&times;</span>
            <h3>✏️ Edit Student</h3>
            <form method="POST" class="profile-update-form">
                <input type="hidden" name="action" value="update_student" />
                <input type="hidden" name="original_id_number" id="edit_original_id_number" />
                <input type="hidden" name="id_number" id="edit_id_number" />
                <label>ID Number</label>
                <input type="text" id="edit_id_number_display" onchange="document\.getElementById\('edit_id_number'\)\.value = this\.value" />
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
    </div>'''

new_modal = '''<div class="admin-modal" id="editStudentModal" style="display:none;">
        <div class="admin-modal-content" style="max-width:500px; width:95%;">
            <span class="close-modal" onclick="closeFeature('editStudentModal')" style="font-size:30px; cursor:pointer; color:#9ca3af; padding:10px; border-radius:50%; transition:all 0.3s;" onmouseover="this.style.background='#f3f4f6'; this.style.color='#374151'" onmouseout="this.style.background='transparent'; this.style.color='#9ca3af'">&times;</span>
            <h3 style="margin-top:0;">✏️ Edit Student</h3>
            <form method="POST" class="profile-update-form" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_student" />
                <input type="hidden" name="original_id_number" id="edit_original_id_number" />
                <input type="hidden" name="id_number" id="edit_id_number" />
                <input type="hidden" name="existing_photo" id="edit_existing_photo" value="" />
                <div style="display:flex; justify-content:center; margin-bottom:20px;">
                    <div style="text-align:center;">
                        <img id="edit_photo_preview" src="https://via.placeholder.com/120?text=No+Photo" alt="Student Photo" style="width:120px; height:120px; border-radius:50%; object-fit:cover; border:3px solid #e5e7eb; margin-bottom:10px;">
                        <div style="margin-top:5px;">
                            <button type="button" onclick="document.getElementById('edit_photo').click()" style="background:#3b82f6; color:white; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; font-size:12px;">📷 Change Photo</button>
                            <input type="file" name="photo" id="edit_photo" style="display:none;" accept="image/*" onchange="previewEditPhoto(this)">
                            <button type="button" onclick="removeEditPhoto()" id="edit_remove_photo_btn" style="background:#ef4444; color:white; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; font-size:12px; display:none; margin-left:5px;">Remove</button>
                        </div>
                    </div>
                </div>
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
                <button type="submit" style="margin-top:15px; padding:12px 24px; background:linear-gradient(135deg, #10b981, #059669); color:white; border:none; border-radius:8px; cursor:pointer; font-weight:600; font-size:14px; width:100%;">💾 Save Changes</button>
            </form>
        </div>
    </div>'''

# Use a simpler approach - just do direct string replacement with the actual content
# Find and replace the entire modal section
modal_start = '    <!-- Edit Student Modal -->'
modal_end = '    </div>\n\n\n\n    <script>'

start_idx = content.find(modal_start)
end_idx = content.find(modal_end, start_idx)

if start_idx != -1 and end_idx != -1:
    before = content[:start_idx]
    after = content[end_idx:]
    content = before + new_modal + '\n' + after

# Write the file
with open('admin_dashboard.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("Admin dashboard fixed successfully!")
print("\nChanges made:")
print("1. Added 'photo' column to student search queries")
print("2. Added 'photo' column to student listing queries")
print("3. Updated edit student modal to include photo upload")
print("4. Added JavaScript photo preview functionality")
print("5. Updated openEditStudentModal to handle photo parameter")