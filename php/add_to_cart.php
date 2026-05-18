<?php
/**
 * Add to Cart — AJAX endpoint
 *
 * Adds a product to the logged-in user's cart in MySQL.
 * If the product already exists in the cart, increments the quantity.
 * Returns JSON: { success: bool, count: int, error?: string }
 */
session_start();
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'POST required']);
    exit;
}

require_once 'db.php';

// Check authentication
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

// Check if the product already exists in this user's cart
$stmt = $conn->prepare('SELECT id, quantity FROM cart WHERE user_id = ? AND perfume_id = ?');
$stmt->bind_param('ii', $user_id, $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Existing item — increment quantity by 1
    $row = $result->fetch_assoc();
    $new_qty = $row['quantity'] + 1;
    $stmt = $conn->prepare('UPDATE cart SET quantity = ? WHERE id = ?');
    $stmt->bind_param('ii', $new_qty, $row['id']);
    $stmt->execute();
} else {
    // New item — insert with quantity 1
    $stmt = $conn->prepare('INSERT INTO cart (user_id, perfume_id, quantity) VALUES (?, ?, 1)');
    $stmt->bind_param('ii', $user_id, $product_id);
    $stmt->execute();
}

// Return the updated total item count for the navbar badge
$stmt = $conn->prepare('SELECT COALESCE(SUM(quantity), 0) AS count FROM cart WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$count_result = $stmt->get_result();
$count_row = $count_result->fetch_assoc();

echo json_encode([
    'success' => true,
    'count'   => (int)$count_row['count']
]);
?>
