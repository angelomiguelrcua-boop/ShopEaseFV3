<?php
// BACKUP COPY OF ORIGINAL DASHBOARD.PHP FOR REFERENCE
// This file preserves the original dashboard functionality before modernization
session_start();

// Error reporting for development
ini_set("display_errors", 1);
error_reporting(E_ALL);

// -------------------------
// ✅ DATABASE CONNECTION
// -------------------------
$conn = new mysqli('localhost', 'root', '', 'ecommerce_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// (Rest of original code would be here - this is just the backup header)
?>