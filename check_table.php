<?php
$conn = mysqli_connect('localhost', 'root', '', 'sitin_system');
$result = mysqli_query($conn, 'DESCRIBE students');
while($row = mysqli_fetch_assoc($result)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
mysqli_close($conn);
?>