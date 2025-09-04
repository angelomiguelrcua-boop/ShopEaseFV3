<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'ecommerce_db');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = "";

header("Content-Type: application/json");

// Count aisles
$aisle_count = $conn->query("SELECT COUNT(*) as total FROM aisles")->fetch_assoc()['total'];

// Count products
$product_count = $conn->query("SELECT COUNT(*) as total FROM products")->fetch_assoc()['total'];

// Count admins
$admin_count = $conn->query("SELECT COUNT(*) as total FROM admins WHERE active = 1")->fetch_assoc()['total'];

echo json_encode([
    "aisles" => $aisle_count,
    "products" => $product_count,
    "admins" => $admin_count
]);
