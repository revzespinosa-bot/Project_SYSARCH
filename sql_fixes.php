<?php
$content = file_get_contents('admin_dashboard.php');

// Fix main search query - add address and photo before COALESCE
$content = str_replace(
    'SELECT id_number, last_name, first_name, middle_name, course, year_level, email, COALESCE(remaining_sessions, 30) as remaining_sessions FROM students WHERE id_number LIKE ? OR last_name LIKE ? OR first_name LIKE ?',
    'SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address, photo, COALESCE(remaining_sessions, 30) as remaining_sessions FROM students WHERE id_number LIKE ? OR last_name LIKE ? OR first_name LIKE ?',
    $content
);

// Fix main else query - add photo
$content = str_replace(
    'SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address FROM students ORDER BY last_name, first_name',
    'SELECT id_number, last_name, first_name, middle_name, course, year_level, email, address, photo FROM students ORDER BY last_name, first_name',
    $content
);

// Fix search modal query - already has address, add photo
$content = str_replace(
    'email, address, COALESCE',
    'email, address, photo, COALESCE',
    $content
);

// Fix search modal else query - already has address, add photo  
$content = str_replace(
    'email, address, COALESCE(remaining_sessions, 30) as remaining_sessions FROM students ORDER BY last_name, first_name LIMIT 50',
    'email, address, photo, COALESCE(remaining_sessions, 30) as remaining_sessions FROM students ORDER BY last_name, first_name LIMIT 50',
    $content
);

file_put_contents('admin_dashboard.php', $content);
echo 'SQL fixes applied' . "\n";
?>