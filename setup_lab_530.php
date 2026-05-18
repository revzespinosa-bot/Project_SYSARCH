<?php
/**
 * Setup Lab 530 with 50 seats
 * This script populates the computers table with Lab 530 data
 */

include "db.php";

// Check if computers table exists
$tableExists = $conn->query("SHOW TABLES LIKE 'computers'");
if ($tableExists->num_rows === 0) {
    die("Computers table does not exist.");
}

// Check if Lab 530 already exists
$checkLab = $conn->query("SELECT COUNT(*) as cnt FROM computers WHERE lab_name = '530'");
$result = $checkLab->fetch_assoc();

if ($result['cnt'] > 0) {
    echo "Lab 530 already has " . $result['cnt'] . " seats. Skipping setup.<br>";
    
    // Optional: Show current count
    if ($result['cnt'] < 50) {
        echo "Adding remaining seats to reach 50...<br>";
        
        // Get existing seats
        $existing = $conn->query("SELECT computer_name FROM computers WHERE lab_name = '530' ORDER BY computer_name");
        $existingNames = [];
        while ($row = $existing->fetch_assoc()) {
            $existingNames[] = $row['computer_name'];
        }
        
        // Add missing seats
        $stmt = $conn->prepare("INSERT INTO computers (lab_name, computer_name, status) VALUES ('530', ?, 'available')");
        $count = 0;
        
        for ($i = 1; $i <= 50; $i++) {
            $seatName = 'PC-' . str_pad($i, 2, '0', STR_PAD_LEFT);
            if (!in_array($seatName, $existingNames)) {
                $stmt->bind_param("s", $seatName);
                if ($stmt->execute()) {
                    $count++;
                }
            }
        }
        
        $stmt->close();
        echo "Added " . $count . " new seats to Lab 530.<br>";
    } else {
        echo "Lab 530 is complete with 50 seats.<br>";
    }
} else {
    echo "Creating Lab 530 with 50 seats...<br>";
    
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
    
    echo "✅ Successfully added " . $successCount . " seats to Lab 530!<br>";
}

// Verify final count
$verify = $conn->query("SELECT COUNT(*) as total FROM computers WHERE lab_name = '530'");
$verifyResult = $verify->fetch_assoc();
echo "Lab 530 now has " . $verifyResult['total'] . " total seats.<br>";

// Show seat list
echo "<br><strong>Lab 530 Seats:</strong><br>";
$seats = $conn->query("SELECT computer_name, status FROM computers WHERE lab_name = '530' ORDER BY computer_name");
echo "<table border='1' style='border-collapse:collapse;'>";
echo "<tr><th>Seat</th><th>Status</th></tr>";
$count = 0;
while ($row = $seats->fetch_assoc()) {
    $count++;
    echo "<tr><td>" . htmlspecialchars($row['computer_name']) . "</td><td>" . htmlspecialchars($row['status']) . "</td></tr>";
}
echo "</table>";
echo "<p>Total verified: " . $count . " seats</p>";

$conn->close();
?>
