<?php
session_start();
$conn = new mysqli("localhost", "root", "", "ecommerce_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// get latest 20 logs
$sql = "SELECT a.username, l.action, l.timestamp 
        FROM activity_log l 
        JOIN admins a ON l.admin_id = a.id 
        ORDER BY l.timestamp DESC 
        LIMIT 20";
$result = $conn->query($sql);

$logs = [];
while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}

header("Content-Type: application/json");
echo json_encode($logs);
?>
