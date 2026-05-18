<?php
/**
 * Get Cart Count — AJAX endpoint
 *
 * Returns the total number of items (sum of quantities) in the
 * logged-in user's cart. Used by the navbar cart badge.
 * Returns JSON: { count: int }
 */
session_start();
header('Content-Type: application/json');

require_once 'db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['count' => 0]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare('SELECT COALESCE(SUM(quantity), 0) AS count FROM cart WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

echo json_encode(['count' => (int)$row['count']]);
?>
