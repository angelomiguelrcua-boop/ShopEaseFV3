<?php
// recipient_autocomplete.php
$conn = new mysqli('localhost', 'root', '', 'ecommerce_db');
$q = $_GET['q'] ?? '';

if ($q !== '') {
    // Search for users whose names contain $q, limit results
    $stmt = $conn->prepare("SELECT id, full_name, email FROM users WHERE full_name LIKE ? OR email LIKE ? LIMIT 8");
    $like = "%$q%";
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
    $suggestions = [];
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = [
            'id' => $row['id'],
            'name' => $row['full_name'],
            'email' => $row['email']
        ];
    }
    header('Content-Type: application/json');
    echo json_encode($suggestions);
    exit;
}
?>