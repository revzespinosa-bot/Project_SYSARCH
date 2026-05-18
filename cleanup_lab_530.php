<?php
/**
 * Cleanup and fix Lab 530 to have exactly 50 unique seats
 */

include "db.php";

echo "Cleaning up Lab 530...<br>";

// Delete all Lab 530 entries to start fresh
$deleteResult = $conn->query("DELETE FROM computers WHERE lab_name = '530'");
if ($deleteResult) {
    echo "Deleted existing Lab 530 entries.<br>";
}

// Insert exactly 50 seats with proper naming
$stmt = $conn->prepare("INSERT INTO computers (lab_name, computer_name, status) VALUES ('530', ?, 'available')");

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$successCount = 0;

for ($i = 1; $i <= 50; $i++) {
    $seatName = 'PC-' . str_pad($i, 2, '0', STR_PAD_LEFT);
    
    $stmt->bind_param("s", $seatName);
    
    if ($stmt->execute()) {
        $successCount++;
    } else {
        echo "Error adding seat " . $seatName . ": " . $stmt->error . "<br>";
    }
}

$stmt->close();

echo "✅ Successfully created " . $successCount . " seats for Lab 530!<br>";

// Verify final count
$verify = $conn->query("SELECT COUNT(*) as total FROM computers WHERE lab_name = '530'");
$verifyResult = $verify->fetch_assoc();
echo "Lab 530 verification: " . $verifyResult['total'] . " total seats (expected: 50)<br>";

if ($verifyResult['total'] === 50) {
    echo "✅ Lab 530 is now properly configured with exactly 50 seats!<br>";
} else {
    echo "⚠️ Lab 530 has " . $verifyResult['total'] . " seats (expected 50)<br>";
}

$conn->close();
?>
