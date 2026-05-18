<?php
/**
 * Update Cart Quantity — AJAX endpoint
 *
 * Updates the quantity of a product in the logged-in user's cart.
 * If quantity is 0, removes the item entirely.
 * Returns JSON: { success: bool }
 */
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'POST required']);
    exit;
}

require_once 'db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id   = (int)($_SESSION['user_id'] ?? 0);
$product_id = (int)($_POST['product_id'] ?? 0);
$quantity   = (int)($_POST['quantity'] ?? 0);

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
    exit;
}

if ($quantity < 1) {
    // Quantity dropped to zero — remove the item
    $stmt = $conn->prepare('DELETE FROM cart WHERE user_id = ? AND perfume_id = ?');
    $stmt->bind_param('ii', $user_id, $product_id);
} else {
    $stmt = $conn->prepare('UPDATE cart SET quantity = ? WHERE user_id = ? AND perfume_id = ?');
    $stmt->bind_param('iii', $quantity, $user_id, $product_id);
}
$stmt->execute();

echo json_encode(['success' => true]);
?>
