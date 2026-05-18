<?php
/**
 * Get Product — AJAX endpoint
 *
 * Returns a single product from the `perfumes` table by ID.
 * Returns JSON: { success: bool, product?: { id, name, description, price, image, category }, error?: string }
 */
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
    exit;
}

$stmt = $conn->prepare('SELECT id, name, description, price, image, category FROM perfumes WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        'success' => true,
        'product' => [
            'id'          => (int)$row['id'],
            'name'        => $row['name'],
            'description' => $row['description'] ?? '',
            'price'       => (float)$row['price'],
            'image'       => preg_replace('#^(\.\./)+#', '', $row['image'] ?? ''),
            'category'    => $row['category'] ?? ''
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Product not found']);
}
?>
