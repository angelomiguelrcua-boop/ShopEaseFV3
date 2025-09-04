<?php
$conn = new mysqli("localhost","root","","ecommerce_db");
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $result = $conn->query("SELECT image FROM products WHERE id=$id");
    if ($row = $result->fetch_assoc()) {
        header("Content-Type: image/jpeg");
        echo $row['image'];
    }
}
?>
