<?php
$conn = new mysqli('localhost', 'root', '', 'ecommerce_db');
$id = $_POST['id'] ?? null;
$promo_status = $_POST['promo_status'] ?? null;
if ($id && $promo_status !== null) {
    $stmt = $conn->prepare("UPDATE products SET promo_status=? WHERE id=?");
    $stmt->bind_param('si', $promo_status, $id);
    $success = $stmt->execute();
    echo json_encode(['success' => $success]);
} else {
    echo json_encode(['success' => false]);
}
?>