<?php
/**
 * Remove from Cart — AJAX endpoint
 *
 * Removes a product entirely from the logged-in user's cart.
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

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
    exit;
}

$stmt = $conn->prepare('DELETE FROM cart WHERE user_id = ? AND perfume_id = ?');
$stmt->bind_param('ii', $user_id, $product_id);
$stmt->execute();

echo json_encode(['success' => true]);
?>
