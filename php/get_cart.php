<?php
/**
 * Get Cart — AJAX endpoint
 *
 * Returns the logged-in user's cart items joined with product data.
 * Returns JSON: { cart: [ { id, cart_item_id, name, price, image, quantity }, ... ] }
 */
session_start();
header('Content-Type: application/json');

require_once 'db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['cart' => []]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Join cart with perfumes to get name, price, image for each item
$sql = 'SELECT c.perfume_id, c.id AS cart_item_id, c.quantity,
               p.name, p.price, p.image
        FROM cart c
        JOIN perfumes p ON c.perfume_id = p.id
        WHERE c.user_id = ?
        ORDER BY c.id ASC';

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$cart = [];
while ($row = $result->fetch_assoc()) {
    $cart[] = [
        'id'           => (int)$row['perfume_id'],
        'cart_item_id' => (int)$row['cart_item_id'],
        'name'         => $row['name'],
        'price'        => (float)$row['price'],
        'image'        => preg_replace('#^(\.\./)+#', '', $row['image'] ?? ''),
        'quantity'     => (int)$row['quantity']
    ];
}

echo json_encode(['cart' => $cart]);
?>
