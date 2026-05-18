<?php
/**
 * Shopping Cart Handler
 *
 * Processes the "Press to Buy" form submission from the shop page.
 * Reads the selected quantities, stores them in the session, and
 * redirects to the cart page for checkout.
 */
session_start();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../cart.html');
    exit;
}

// Require login to proceed with purchase
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.html');
    exit;
}

// Read the submitted quantity array (quantity[product_id] => count)
// and store it in the session for the cart page to consume.
if (isset($_POST['quantity']) && is_array($_POST['quantity'])) {
    // Filter out zero-quantity items, keep only positive values
    $pending = [];
    foreach ($_POST['quantity'] as $product_id => $qty) {
        $qty = (int)$qty;
        if ($qty > 0) {
            $pending[(int)$product_id] = $qty;
        }
    }
    $_SESSION['pending_quantities'] = $pending;
}

// Redirect to the cart page
header('Location: ../cart.html');
exit;
?>
