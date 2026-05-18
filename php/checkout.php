<?php
/**
 * Checkout — AJAX endpoint
 *
 * Accepts a JSON body with cart items, creates an order in the orders
 * table, inserts order_items records, and clears the user's MySQL cart.
 * Returns JSON: { success: bool, order_id?: int, error?: string }
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

$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid user']);
    exit;
}

// Parse JSON body
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['items']) || !is_array($input['items']) || count($input['items']) === 0) {
    echo json_encode(['success' => false, 'error' => 'No items provided']);
    exit;
}

$items = $input['items'];

// Validate items
foreach ($items as $item) {
    $id       = (int)($item['id'] ?? 0);
    $quantity = (int)($item['quantity'] ?? 0);
    $price    = (float)($item['price'] ?? 0);
    if ($id <= 0 || $quantity <= 0 || $price < 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid item data']);
        exit;
    }
}

// Auto-create order_items table if it does not exist
$conn->query("
    CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        perfume_id INT NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (perfume_id) REFERENCES perfumes(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
");

// Calculate total
$total = 0;
foreach ($items as $item) {
    $total += (float)($item['price'] ?? 0) * (int)($item['quantity'] ?? 0);
}

// Begin transaction
$conn->begin_transaction();

try {
    // Insert order
    $stmt = $conn->prepare('INSERT INTO orders (user_id, total) VALUES (?, ?)');
    $stmt->bind_param('id', $user_id, $total);
    $stmt->execute();
    $order_id = $conn->insert_id;
    $stmt->close();

    // Insert order items
    $stmt = $conn->prepare('INSERT INTO order_items (order_id, perfume_id, quantity, price) VALUES (?, ?, ?, ?)');
    foreach ($items as $item) {
        $perfume_id = (int)$item['id'];
        $quantity   = (int)$item['quantity'];
        $price      = (float)$item['price'];
        $stmt->bind_param('iiid', $order_id, $perfume_id, $quantity, $price);
        $stmt->execute();
    }
    $stmt->close();

    // Clear the user's MySQL cart
    $stmt = $conn->prepare('DELETE FROM cart WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->close();

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success'  => true,
        'order_id' => $order_id
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
